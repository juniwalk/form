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
 * @method void onSuccess(Form $form, ArrayHash $data)
 * @method void onError(Form $form)
 */
abstract class AbstractForm extends Control
{
	protected ?ITranslator $translator;
	protected ?HttpRequest $httpRequest;
	protected Layout $layout = Layout::Card;
	protected string $templateFile;

	/** @var callable[] */
	public array $onRender = [];
	public array $onSuccess = [];
	public array $onError = [];


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


	public function setTranslator(ITranslator $translator = null): void
	{
		$this->translator = $translator;
	}


	public function getTranslator(): ?ITranslator
	{
		return $this->translator;
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


	public function setTemplateFile(string $file): void
	{
		$this->templateFile = $file;
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


	public function render(): void
	{
		$template = $this->createTemplate();
		$template->add('form', $this->getForm());

		if (!empty($this->onRender)) {
			$this->onRender($this, $template);
		}

		if (!isset($template->layout)) {
			$template->add('layout', $this->layout);
		}

		$template->render();
	}


	public function renderModal(): void
	{
		$this->setLayout(Layout::Modal);
		$this->render();
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

		$query = $this->httpRequest->getQuery('term') ?: '';
		$page = (int) $this->httpRequest->getQuery('page') ?: 1;

		if (!method_exists($this, $search)) {
			throw new InvalidArgumentException('Search method '.$search.' is not implemented.');
		}

		$result = $this->$search($query, $page -1);

		$this->getPresenter()->sendJson(
			new SearchPayload($result)
		);
	}


	protected function createTemplate(): ITemplate
	{
		if (!isset($this->templateFile)) {
			$rc = new ReflectionClass($this);
			$this->templateFile = sprintf(
				'%s/templates/%s.latte',
				dirname($rc->getFilename()),
				$rc->getShortName()
			);
		}

		$template = parent::createTemplate();
		$template->setTranslator($this->translator);
		$template->setFile($this->templateFile);

		return $template;
	}


	protected function createComponentForm(): Form
	{
		return $this->createForm();
	}


	protected function handleSuccess(Form $form, ArrayHash $data): void
	{
	}


	protected function createForm(string $class = Form::class): Form
	{
		$form = new $class;
		$form->setTranslator($this->translator);
		$form->addProtection();

		$form->onSuccess[] = $this->handleSuccess(...);
		$form->onSuccess[] = function(Form $form, ArrayHash $data) {
			$this->onSuccess($form, $data);
			$this->redrawControl();
			$form->reset();
		};

		$form->onError[] = function(Form $form) {
			$this->onError($form);
			$this->redrawControl();
			$form->setSubmittedBy(null);
		};

		return $form;
	}
}
