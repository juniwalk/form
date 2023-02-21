<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use JuniWalk\Form\Enums\Layout;
use JuniWalk\Form\Tools\SearchPayload;
use JuniWalk\Utils\Strings;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\IRequest as HttpRequest;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;
use ReflectionClass;

/**
 * @method void onRender(self $self, ITemplate $template)
 * @method void onValidate(Form $form, ArrayHash $data, self $self)
 * @method void onSuccess(Form $form, ArrayHash $data, self $self)
 * @method void onError(Form $form, self $self)
 */
abstract class AbstractForm extends Control
{
	protected ?ITranslator $translator;
	protected ?HttpRequest $httpRequest;
	protected Layout $layout = Layout::Card;
	protected string $formClass = Form::class;
	protected string $templateFile;

	/** @var callable[] */
	public array $onRender = [];
	public array $onValidate = [];
	public array $onSuccess = [];
	public array $onError = [];


	public function setFormClass(string $formClass): void
	{
		$this->formClass = $formClass;
	}


	public function getFormClass(): string
	{
		return $this->formClass;
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


	public function setTranslator(ITranslator $translator = null): void
	{
		$this->translator = $translator;
	}


	public function getTranslator(): ?ITranslator
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
	 * @throws InvalidStateException
	 */
	public function handleSearch(string $type): void
	{
		$search = 'search'.Strings::firstUpper($type);

		if (!$this->httpRequest) {
			throw new InvalidStateException('HttpRequest has not been set, please call setHttpRequest method.');
		}

		$query = (string) $this->httpRequest->getQuery('term') ?: '';
		$page = (int) $this->httpRequest->getQuery('page') ?: 1;

		if (!method_exists($this, $search)) {
			throw new InvalidArgumentException('Search method '.$search.' is not implemented.');
		}

		$result = $this->$search($query, $page -1);

		$this->getPresenter()->sendJson(
			new SearchPayload($result)
		);
	}


	public function renderAccordion(): void
	{
		$this->setLayout(Layout::Accordion);
		$this->render();
	}


	public function renderModal(): void
	{
		$this->setLayout(Layout::Modal);
		$this->render();
	}


	public function render(): void
	{
		$template = $this->createTemplate();
		$template->setTranslator($this->translator);

		$this->onRender($this, $template);
		$form = $this->getForm()->setDefaults([
			'__layout' => $this->layout->value,
		]);

		$templateFile = $template->getFile() ?? $this->getTemplateFile();
		$template->render($templateFile, [
			'layout' => $this->layout,
			'form' => $form,
		]);
	}


	protected function createComponentForm(): Form
	{
		$form = new $this->formClass;
		$form->setTranslator($this->translator);
		$form->addHidden('__layout');
		$form->addProtection();

		$form->onValidate[] = function(Form $form, ArrayHash $data): void {
			if ($layout = Layout::tryMake($data->__layout ?? '')) {
				$this->setLayout($layout);
			}

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
			$form->setSubmittedBy(null);
		};

		return $form;
	}


	protected function handleValidate(Form $form, ArrayHash $data): void
	{
	}


	protected function handleSuccess(Form $form, ArrayHash $data): void
	{
	}
}
