<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate;
use Nette\Http\IRequest as HttpRequest;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;
use Nette\Utils\Callback;
use ReflectionClass;

/**
 * @method void onBeforeRender(self $self, ITemplate $template)
 * @method void onSuccess(Form $form, ArrayHash $data)
 * @method void onError(Form $form)
 */
abstract class AbstractForm extends Control
{
	protected ?ITranslator $translator;
	protected ?HttpRequest $httpRequest;
	protected string $templateFile;
	protected string $layout = 'card';

	/** @var callable[] */
	public array $onBeforeRender = [];
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


	public function setLayout(string $layout): void
	{
		$this->layout = $layout;
	}


	public function getLayout(): string
	{
		return $this->layout;
	}


	public function getLayoutPath(): string
	{
		return __DIR__.'/templates/@layout-'.$this->layout.'.latte';
	}


	public function setTemplateFile(string $file): void
	{
		$this->templateFile = $file;
	}


	public function render(): void
	{
		$template = $this->createTemplate();
		$template->add('layout', $this->layout);
		$template->add('form', $this->getForm());

		if (!empty($this->onBeforeRender)) {
			$this->onBeforeRender($this, $template);
		}

		$template->render();
	}


	public function renderModal(): void
	{
		$this->setLayout('modal');
		$this->render();
	}


	/**
	 * @throws InvalidArgumentException
	 * @throws InvalidStateException
	 */
	public function handleSearch(string $type): void
	{
		if (!$this->httpRequest) {
			throw new InvalidStateException('HttpRequest has not been set, please call setHttpRequest method.');
		}

		$term = $this->httpRequest->getQuery('term') ?? '';
		$page = $this->httpRequest->getQuery('page') ?? 0;

		$callback = Callback::check([$this, 'search'.$type]);
		$items = $callback((string) $term, (int) $page);

		$this->getPresenter()->sendJson([
			'results' => array_values($items),
			'pagination' => [
				'more' => !empty($items),
			],
		]);
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


	protected function createComponentForm(string $name): Form
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

		$form->onError[] = function(Form $form) {
			$this->onError($form);
			$this->redrawControl('errors');
		};

		$form->onSuccess[] = function(Form $form, ArrayHash $data) {
			$this->handleSuccess($form, $data);
		};

		$form->onSuccess[] = function(Form $form, ArrayHash $data) {
			$this->onSuccess($form, $data);
			$this->redrawControl('form');
		};

		return $form;
	}
}
