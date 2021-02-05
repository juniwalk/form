<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;

/**
 * @method void onBeforeRender(self $self, ITemplate $template)
 * @method void onSuccess(Form $form, ArrayHash $data)
 * @method void onError(Form $form)
 */
abstract class AbstractForm extends Control
{
	/** @var ITranslator|null */
	private $translator;

	/** @var string */
	private $templateFile;

	/** @var string */
	private $layout = 'card';

	/** @var callable[] */
	public $onBeforeRender = [];

	/** @var callable[] */
	public $onSuccess = [];

	/** @var callable[] */
	public $onError = [];


	/**
	 * @return Form
	 */
	public function getForm(): Form
	{
		return $this->getComponent('form');
	}


	/**
	 * @param  ITranslator|null  $translator
	 * @return void
	 */
	public function setTranslator(ITranslator $translator = null): void
    {
        $this->translator = $translator;
    }


	/**
	 * @return ITranslator|null
	 */
	public function getTranslator(): ?ITranslator
	{
		return $this->translator;
	}


	/**
	 * @param  string  $layout
	 * @return void
	 */
	public function setLayout(string $layout): void
	{
		$this->layout = $layout;
	}


	/**
	 * @return string
	 */
	public function getLayout(): string
	{
		return $this->layout;
	}


	/**
	 * @return string
	 * @internal
	 */
	public function getLayoutPath(): string
	{
		return __DIR__.'/templates/@layout-'.$this->layout.'.latte';
	}


	/**
	 * @param  string  $file
	 * @return void
	 */
	public function setTemplateFile(string $file): void
	{
		$this->templateFile = $file;
	}


	/**
	 * @return void
	 */
	public function render(): void
	{
		$template = $this->createTemplate();
		$template->setTranslator($this->translator);
		$template->setFile($this->templateFile);
		$template->add('layout', $this->layout);
		$template->add('form', $this->getForm());

		if (!empty($this->onBeforeRender)) {
			$this->onBeforeRender($this, $template);
		}

		$template->render();
	}


	/**
	 * @return void
	 */
	public function renderModal(): void
	{
		$this->setLayout('modal');
		$this->render();
	}


	/**
	 * @param  string  $name
	 * @return Form
	 */
	protected function createComponentForm(string $name): Form
	{
		$form = new Form;
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


	/**
	 * @param  Form  $form
	 * @param  ArrayHash  $data
	 * @return void
	 */
	protected function handleSuccess(Form $form, ArrayHash $data): void
	{
	}
}
