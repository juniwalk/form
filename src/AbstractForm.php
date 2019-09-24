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
	/** @var FormFactory */
	private $formFactory;

	/** @var string */
	private $templateFile;

	/** @var callable[] */
	public $onBeforeRender = [];

	/** @var callable[] */
	public $onSuccess = [];

	/** @var callable[] */
	public $onError = [];


	/**
	 * @param FormFactory $formFactory
	 */
	public function setFormFactory(FormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}


	/**
	 * @return Form
	 */
	public function getForm(): Form
	{
		return $this->getComponent('form');
	}


	/**
	 * @return ITranslator|null
	 */
	public function getTranslator(): ?ITranslator
	{
		return $this->formFactory->getTranslator();
	}


	/**
	 * @param  string|null  $file
	 * @return void
	 */
	public function setTemplateFile(string $file = null): void
	{
		$this->templateFile = $file;
	}


	public function render()
	{
		$template = $this->createTemplate();
		$template->setTranslator($this->getTranslator());
		$template->setFile(__DIR__.'/templates/form.latte');

		if ($this->templateFile) {
			$template->setFile($this->templateFile);
		}

		$template->add('form', $this->getForm());

		if (!empty($this->onBeforeRender)) {
			$this->onBeforeRender($this, $template);
		}

		$template->render();
	}


	/**
	 * @param  string  $name
	 * @return Form
	 */
	protected function createComponentForm(string $name): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->onError[] = function (Form $form) {
			$this->onError($form);
			$this->redrawControl('errors');
		};

		$form->onSuccess[] = function (Form $form, ArrayHash $data) {
			$this->handleSuccess($form, $data);
		};

		$form->onSuccess[] = function (Form $form, ArrayHash $data) {
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
