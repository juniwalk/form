<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Contributte\Translation\Wrappers\Message;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueException;
use JuniWalk\Form\Attributes\PreventLeavingWhenDirty;
use JuniWalk\Form\Enums\Layout;
use JuniWalk\Form\SearchPayload;
use JuniWalk\ORM\Exceptions\EntityNotFoundException;
use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Format;
use JuniWalk\Utils\Interfaces\EventAutoWatch;
use JuniWalk\Utils\Interfaces\EventHandler;
use JuniWalk\Utils\Interfaces\Modal;
use JuniWalk\Utils\Interfaces\ModalHandler;
use JuniWalk\Utils\Strings;
use JuniWalk\Utils\Traits\Events;
use JuniWalk\Utils\Traits\RedirectAjaxHandler;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\IRequest as HttpRequest;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Localization\Translator;
use Nette\Utils\ArrayHash;
use ReflectionClass;
use Stringable;
use Throwable;
use Tracy\Debugger;

/**
 * @property callable[] $onRender
 * @property callable[] $onValidate
 * @property callable[] $onSuccess
 * @property callable[] $onError
 */
abstract class AbstractForm extends Control implements Modal, EventHandler, EventAutoWatch
{
	use Events, RedirectAjaxHandler;

	protected Layout $layout = Layout::Card;
	protected HttpRequest $httpRequest;
	protected Translator $translator;
	protected ?string $templateFile = null;
	protected bool $isModalOpen = false;


	public function setHttpRequest(HttpRequest $httpRequest): void
	{
		$this->httpRequest = $httpRequest;
	}


	public function setLayout(string|Layout $layout): void
	{
		if (!$layout instanceof Layout) {
			$layout = Layout::make($layout, true);
		}

		/** @var Layout $layout */
		$this->layout = $layout;
	}


	public function getLayout(): Layout
	{
		return $this->layout;
	}


	public function getLayoutPath(): string
	{
		return $this->layout->path(__DIR__);
	}


	public function setModalOpen(bool $open): void
	{
		$this->isModalOpen = $open;
	}


	public function setTemplateFile(?string $file): void
	{
		$this->templateFile = $file ?? null;
	}


	public function getTemplateFile(): string
	{
		if (isset($this->templateFile)) {
			return $this->templateFile;
		}

		$class = new ReflectionClass($this);
		$shortName = $class->getShortName();
		$fileName = $class->getFilename();

		return sprintf('%s/templates/%s.latte', dirname($fileName ?: ''), $shortName);
	}


	public function isPreventLeavingWhenDirty(): bool
	{
		$attributes = (new ReflectionClass($this))
			->getAttributes(PreventLeavingWhenDirty::class);

		if (empty($attributes)) {
			return false;
		}

		$preventLeaving = $attributes[0]->newInstance();
		return $preventLeaving->for($this->layout);
	}


	public function setTranslator(Translator $translator): void
	{
		$this->translator = $translator;
	}


	public function getTranslator(): ?Translator
	{
		return $this->translator ?? null;
	}


	public function getForm(): Form
	{
		return $this->getComponent('form');
	}


	/**
	 * @param array<string, string> $pages
	 */
	public function findRedirectPage(array $pages, string $default = 'default'): string
	{
		$form = $this->getForm();

		foreach ($pages as $control => $page) {
			if (!$button = $form->getComponent($control, false)) {
				continue;
			}

			if (!$button instanceof SubmitButton) {
				continue;
			}

			if (!$button->isSubmittedBy()) {
				continue;
			}

			return $page;
		}

		return $default;
	}


	/**
	 * @throws InvalidArgumentException
	 */
	public function handleSearch(string $type, ?int $maxResults = null, ?string $term = null, ?int $page = null): void
	{
		$search = new SearchPayload($page, $maxResults);
		$method = 'search'.Strings::firstUpper($type);
		$form = $this->getForm();

		try {
			if (!method_exists($this, $method)) {
				throw new InvalidArgumentException('Search method '.$method.' is not implemented.');
			}

			$result = $this->$method($term ?? '', $search);
			$search->addItems($result ?? []);

		} catch (Throwable $e) {
			$form->addError($e->getMessage());
			Debugger::log($e);

			// todo: If error displaying is solved in future
			// todo: remove this statement as it would trigger Tracy
			throw $e;
		}

		$this->getPresenter()->sendJson($search);
	}


	/**
	 * @throws InvalidArgumentException
	 * @throws InvalidStateException
	 */
	public function handleRefresh(string $type, mixed $value = null): void
	{
		$method = 'refresh'.Strings::firstUpper($type);
		$form = $this->getForm();

		if (!isset($this->httpRequest)) {
			throw new InvalidStateException('HttpRequest has not been set, please call setHttpRequest method.');
		}

		foreach ($this->getComponents(true, ChoiceControl::class) as $field) {
			/** @var ChoiceControl $field */
			$field->checkDefaultValue(false);
		}

		/** @var array{_layout_: string} */
		$data = $this->httpRequest->getPost();
		$form->setValues($data);

		try {
			if (!method_exists($this, $method)) {
				throw new InvalidArgumentException('Refresh method '.$method.' is not implemented.');
			}

			$redraw = $this->$method($form, $data, $value);

		} catch (AbortException) {
		} catch (Throwable $e) {
			$form->addError($e->getMessage());
			Debugger::log($e);

		}

		$this->redrawControl('form', $redraw ?? true);
		$this->setLayout($data['_layout_']);

		if ($this->layout === Layout::Modal) {
			$this->setModalOpen(true);
		}

		$this->redirect('this');
	}


	public function renderAccordion(string $container): void
	{
		$this->setLayout(Layout::Accordion);
		$this->when('render', fn($x, $t) => $t->setParameters([
			'container' => $container,
		]));

		$this->render();
	}


	public function renderModal(bool $keyboard = false, bool|string $backdrop = 'static'): void
	{
		$this->setLayout(Layout::Modal);
		$this->when('render', fn($x, $t) => $t->setParameters([
			'modalOptions' => [
				'data-bs-backdrop' => Format::stringify($backdrop),
				'data-backdrop' => Format::stringify($backdrop),
				'data-bs-keyboard' => Format::stringify($keyboard),
				'data-keyboard' => Format::stringify($keyboard),
			],
		]));

		$this->render();
	}


	public function renderBare(): void
	{
		$this->setLayout(Layout::Bare);
		$this->render();
	}


	public function render(): void
	{
		if (!$this->isModalOpen && $this->layout == Layout::Modal) {
			return;
		}

		/** @var DefaultTemplate */
		$template = $this->getTemplate();
		$template->setFile($this->getTemplateFile());
		$template->setTranslator($this->getTranslator());

		$this->trigger('render', $this, $template);

		$form = $this->getForm()->setDefaults([
			'_layout_' => $this->layout->value,
		]);

		$template->setParameters([
			'layout' => $this->layout,
			'form' => $form,

			'formOptions' => [
				'data-check-dirty' => $this->isPreventLeavingWhenDirty(),
				'data-form-name' => $this->lookupPath(throw: false) ?? $this->getName(),
			],
		]);

		$template->render();
	}


	protected function createComponentForm(): Form
	{
		$form = new Form;
		$form->setTranslator($this->getTranslator());
		$form->addHidden('_layout_');
		$form->addProtection();

		$form->onValidate[] = function(Form $form, ArrayHash $data): void {	// @phpstan-ignore assign.propertyType
			$this->setLayout($data->_layout_);

			$this->catch($this->handleValidate(...), $form, $data);
			$this->trigger('validate', $form, $data, $this);
		};

		// ? Method handleSuccess has to be called in separate event for proper error handling
		$form->onSuccess[] = function(Form $form, ArrayHash $data): void {	// @phpstan-ignore assign.propertyType
			$this->catch($this->handleSuccess(...), $form, $data);
		};

		$form->onSuccess[] = function(Form $form, ArrayHash $data): void {	// @phpstan-ignore assign.propertyType
			$this->trigger('success', $form, $data, $this);
			$this->redrawControl();
			$form->reset();
		};

		$form->onError[] = function(Form $form): void {
			$this->trigger('error', $form, $this);
			$this->redrawControl();
			$this->reopenModal();
		};

		return $form;
	}


	protected function handleValidate(Form $form, ArrayHash $data): void {}
	protected function handleSuccess(Form $form, ArrayHash $data): void {}


	/**
	 * @param callable(?string): ?string $callback
	 * @param array<string, string> $fieldMap
	 */
	protected function handleUniqueConstraintViolation(
		UniqueException $e,
		?callable $callback = null,
		array $fieldMap = [],
	): void {
		$callback ??= fn() => null;
		$form = $this->getForm();

		$matched = Strings::match($e->getMessage(), '/\((?<field>[^\)]+)\)=\((?<value>[^\)]+)\)/i');
		$defaultMessage = $callback(null) ?? $e->getMessage();

		if (empty($matched)) {
			$form->addError($defaultMessage);
			return;
		}

		$fields = array_fill_keys(Strings::split($matched['field'], '/,\s/'), null);
		$fields = Arrays::walk($fields, fn($value, $field) =>
			yield $fieldMap[$field] ?? Format::camelCase($field) => $form->getComponent($field, false)?->getValue() ?? $value
		);

		$fieldKey = implode('-', array_keys($fields));
		$message = $callback($fieldKey) ?? $defaultMessage;
		$message = new Message($message, $fields);

		foreach ($fields as $field => $value) {
			if (!$control = $form->getComponent($field, false)) {
				continue;
			}

			/** @var BaseControl $control */
			$control->addError($message);
		}

		if (!$form->hasErrors()) {
			$form->addError($message);
		}
	}


	/**
	 * ? Used to translate prompts when translator is removed from ChoiceControl
	 * @param array<string, mixed> $params
	 */
	protected function translate(string $message, array $params = []): string|Stringable
	{
		if (!isset($this->translator)) {
			return $message;
		}

		return $this->translator->translate($message, $params);
	}


	private function catch(callable $method, Form $form, ArrayHash $data): void
	{
		try {
			$method($form, $data);

		} catch (EntityNotFoundException $e) {
			if (!$control = $form->getComponent($e->getEntityName(), false)) {
				throw $e;
			}

			/** @var BaseControl $control */
			$control->addError(new Message('web.message.entity-not-found', [
				'field' => $e->getEntityName(),
				'id' => $e->getId(),
			]));

		} catch (ForbiddenRequestException) {
			$form->addError('web.message.permission-denied');

		} catch (Throwable $e) {
			$form->addError('web.message.something-went-wrong');
			Debugger::log($e);
		}
	}


	private function reopenModal(): void
	{
		$presenter = $this->getPresenterIfExists();

		if ($this->layout <> Layout::Modal ||
			!$presenter instanceof ModalHandler ||
			!$presenter->isAjax()) {
			return;
		}

		$presenter->openModal($this);
	}
}
