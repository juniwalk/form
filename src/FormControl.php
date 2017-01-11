<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate;
use Nette\Forms\IFormRenderer;
use Nette\Localization\ITranslator;

/**
 * @method void onSuccess(Form $form, ArrayHash $data)
 * @method void onError(Form $form)
 * @method void onBeforeRender(self $self, ITemplate $template)
 */
abstract class FormControl extends \Nette\Application\UI\Control
{
	/** @var callable[] */
	public $onSuccess = [];

	/** @var callable[] */
	public $onError = [];

	/** @var callable[] */
	public $onBeforeRender = [];

	/** @var ITranslator */
	private $translator;

	/** @var string */
	private $errorTemplate;

	/** @var string */
	private $formTemplate;


	/**
	 * @param  ITranslator  $translator
	 * @return static
	 */
	public function setTranslator(ITranslator $translator = NULL)
	{
		$this->translator = $translator;
		return $this;
	}


	/**
	 * @return ITranslator
	 */
	public function getTranslator()
	{
		return $this->translator;
	}


	/**
	 * @return Form
	 */
	public function getForm()
	{
		return $this->getComponent('form');
	}


	/**
	 * @param  string  $path
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function setFormTemplate($path = NULL)
	{
		$this->formTemplate = $path;
		return $this;
	}


	/**
	 * @param  string  $path
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function setErrorTemplate($path = NULL)
	{
		$this->errorTemplate = $path;
		return $this;
	}


	public function render()
	{
		$template = $this->createTemplate();
		$template->setFile($this->formTemplate ?: __DIR__.'/templates/form.latte');
		$template->render();
	}


	public function renderErrors()
	{
		$template = $this->createTemplate();
		$template->setFile($this->errorTemplate ?: __DIR__.'/templates/errors.latte');
		$template->render();
	}


	/**
	 * @return ITemplate
	 */
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->setTranslator($translator = $this->getTranslator());

		$template->add('form', $form = $this->getForm());
		$form->setTranslator($translator);

		if (!empty($this->onBeforeRender)) {
			$this->onBeforeRender($this, $template);
		}

		return $template;
	}


	/**
	 * @param  string  $name
	 * @param  string  $csrfMessage
	 * @return Form
	 */
	final protected function createForm($name, $csrfMessage = NULL)
	{
		$form = new Form($this, $name);
		$form->addProtection($csrfMessage);

		$form->onError[] = function ($form) {
			$this->onError($form);
			$this->redrawControl('errors');
		};

		$form->onSuccess[] = function ($form, $data) {
			$this->handleSuccess($form, $data);
		};

		$form->onSuccess[] = function ($form, $data) {
			$this->onSuccess($form, $data);
			$this->redrawControl('form');
		};

		return $form;
	}


	/**
	 * @param  string  $name
	 * @return Form
	 */
	abstract protected function createComponentForm($name);


	/**
	 * @param Form       $form
	 * @param ArrayHash  $data
	 */
	protected function handleSuccess($form, $data)
	{

	}
}
