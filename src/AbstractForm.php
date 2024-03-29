<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Contributte\Translation\Wrappers\Message;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueException;
use JuniWalk\Form\Enums\Layout;
use JuniWalk\Form\Tools\SearchPayload;
use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Format;
use JuniWalk\Utils\Strings;
use JuniWalk\Utils\UI\Modal;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate as Template;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\SubmitterControl;
use Nette\Http\IRequest as HttpRequest;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Localization\Translator;
use Nette\Utils\ArrayHash;
use ReflectionClass;
use Throwable;
use Tracy\Debugger;

/**
 * @method void onRender(self $self, Template $template)
 * @method void onValidate(Form $form, ArrayHash $data, self $self)
 * @method void onSuccess(Form $form, ArrayHash $data, self $self)
 * @method void onError(Form $form, self $self)
 */
abstract class AbstractForm extends Control implements Modal
{
	protected ?Translator $translator;
	protected ?HttpRequest $httpRequest;
	protected Layout $layout = Layout::Card;
	protected ?string $templateFile = null;
	protected bool $isModalOpen = false;

	/** @var callable[] */
	public array $onRender = [];
	public array $onValidate = [];
	public array $onSuccess = [];
	public array $onError = [];


	public function setModalOpen(bool $open): void
	{
		$this->isModalOpen = $open;
	}


	public function getForm(): Form
	{
		return $this->getComponent('form');
	}


	public function setHttpRequest(HttpRequest $httpRequest): void
	{
		$this->httpRequest = $httpRequest;
	}


	public function getHttpRequest(): ?HttpRequest
	{
		return $this->httpRequest;
	}


	public function setLayout(Layout $layout): void
	{
		$this->layout = $layout;
	}


	public function getLayout(): Layout
	{
		return $this->layout;
	}


	public function getLayoutPath(): string
	{
		return __DIR__.'/templates/@layout-'.$this->layout->value.'.latte';
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

		$rc = new ReflectionClass($this);
		return sprintf('%s/templates/%s.latte',
			dirname($rc->getFilename()),
			$rc->getShortName()
		);
	}


	public function setTranslator(Translator $translator = null): void
	{
		$this->translator = $translator;
	}


	public function getTranslator(): ?Translator
	{
		return $this->translator;
	}


	public function findRedirectPage(array $pages, string $default = 'default'): string
	{
		$form = $this->getForm();

		foreach ($pages as $control => $page) {
			if (!$button = $form->getComponent($control, false)) {
				continue;
			}

			if (!$button instanceof SubmitterControl) {
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

		foreach ($this->getComponents(true, ChoiceControl::class) as $field) {
			$field->checkDefaultValue(false);
		}

		if (!$this->httpRequest) {
			throw new InvalidStateException('HttpRequest has not been set, please call setHttpRequest method.');
		}

		$formData = (array) $this->httpRequest->getPost() ?: [];
		$form->setValues($formData);

		try {
			if (!method_exists($this, $method)) {
				throw new InvalidArgumentException('Refresh method '.$method.' is not implemented.');
			}

			$redraw = $this->$method($form, $formData, $value);

		} catch (AbortException) {
		} catch (Throwable $e) {
			$form->addError($e->getMessage());
			Debugger::log($e);
		}

		$this->setLayout(Layout::from($formData['_layout_']));
		$this->redrawControl('form', $redraw ?? true);
		$this->isModalOpen = true;
		$this->redirect('this');
	}


	public function redirect(string $dest, mixed ...$args): void
	{
		$presenter = $this->getPresenter();

		if ($presenter->isAjax()) {
			$presenter->payload->postGet = true;
			$presenter->payload->url = $this->link($dest, ...$args);
			return;
		}

		parent::redirect($dest, ...$args);
	}


	public function renderAccordion(string $container): void
	{
		$this->setLayout(Layout::Accordion);
		$this->onRender[] = fn($self, $template) => $template->setParameters([
			'container' => $container,
		]);

		$this->render();
	}


	public function renderModal(bool $keyboard = false, bool|string $backdrop = 'static'): void
	{
		$this->setLayout(Layout::Modal);
		$this->onRender[] = fn($self, $template) => $template->setParameters([
			'modalOptions' => [
				'data-backdrop' => Format::stringify($backdrop),
				'data-keyboard' => Format::stringify($keyboard),
			],
		]);

		$this->render();
	}


	public function render(): void
	{
		if (!$this->isModalOpen && $this->layout == Layout::Modal) {
			return;
		}

		$template = $this->createTemplate();
		$template->setTranslator($this->translator);

		ksort($this->onRender);
		$this->onRender($this, $template);

		$form = $this->getForm()->setDefaults([
			'_layout_' => $this->layout->value,
		]);

		$templateFile = $template->getFile() ?? $this->getTemplateFile();
		$template->render($templateFile, [
			'layout' => $this->layout,
			'form' => $form,
		]);
	}


	/**
	 * @internal
	 */
	protected function findSubmitButton(): ?SubmitterControl
	{
		$buttons = $this->getComponents(true, SubmitterControl::class);

		if (!$buttons = iterator_to_array($buttons)) {
			return null;
		}

		return $buttons['submit'] ?? current($buttons);
	}


	protected function createComponentForm(): Form
	{
		$form = new Form;
		$form->setTranslator($this->translator);
		$form->addHidden('_layout_');
		$form->addProtection();

		$form->onValidate[] = function(Form $form, ArrayHash $data): void {
			$this->setLayout(Layout::from($data->_layout_));

			$this->handleValidate($form, $data);
			$this->onValidate($form, $data, $this);
		};

		$form->onSuccess[] = $this->handleSuccess(...);
		$form->onSuccess[] = function(Form $form, ArrayHash $data): void {
			$this->onSuccess($form, $data, $this);
			$this->redrawControl();
			$form->reset();
		};

		$form->onError[] = function(Form $form): void {
			$this->onError($form, $this);
			$this->redrawControl();
		};

		return $form;
	}


	protected function handleValidate(Form $form, ArrayHash $data): void
	{
	}


	protected function handleSuccess(Form $form, ArrayHash $data): void
	{
	}


	protected function handleUniqueConstraintViolation(UniqueException $e, callable $callback = null, array $fieldMap = []): void
	{
		$callback = $callback ?? fn() => null;
		$form = $this->getForm();

		$fields = Strings::match($e->getMessage(), '/\((?<field>[^\)]+)\)=\((?<value>[^\)]+)\)/i');
		$defaultMessage = $callback(null) ?? $e->getMessage();

		if (empty($fields)) {
			$form->addError($defaultMessage);
			return;
		}

		$fields = array_combine(
			Strings::split($fields['field'], '/,\s?/'),
			Strings::split($fields['value'], '/,\s?/')
		);

		$fields = Arrays::walk($fields, fn($value, $field) =>
			yield $fieldMap[$field] ?? Format::camelCase($field) => $value
		);

		$fieldKey = implode('-', array_keys($fields));
		$message = $callback($fieldKey) ?? $defaultMessage;
		$message = new Message($message, $fields);

		foreach ($fields as $field => $value) {
			if (!$form->getComponent($field, false)) {
				continue;
			}

			$form[$field]->addError($message);
		}

		if (!$form->hasErrors()) {
			$form->addError($message);
		}
	}


	/**
	 * ? Used to translate prompts when translator is removed from ChoiceControl
	 */
	protected function translate(string $message, array $params = []): string
	{
		return $this->translator?->translate($message, $params) ?? $message;
	}
}
