<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate;
use Nette\Forms\IFormRenderer;
use Nette\Localization\ITranslator;

/**
 * @method void onSuccess(Form $form, ArrayHash $data)
 * @method void onBeforeRender(self $self, ITemplate $template)
 */
abstract class FormControl extends \Nette\Application\UI\Control
{
	/**
	 * Types of the template files.
	 * @var string[]
	 */
	const TPL_ERRORS = 'errors';
	const TPL_FORM = 'form';


	/** @var callable[] */
	public $onSuccess = [];

	/** @var callable[] */
	public $onBeforeRender = [];

	/** @var string[] */
	private $templateFile = [
		self::TPL_ERRORS => NULL,
		self::TPL_FORM => NULL,
	];


	/**
	 * @param  ITranslator  $translator
	 * @return static
	 */
	public function setTranslator(ITranslator $translator = NULL)
	{
		$this->getForm()->setTranslator($translator);
		return $this;
	}


	/**
	 * @return ITranslator
	 */
	public function getTranslator()
	{
		return $this->getForm()->getTranslator();
	}


	/**
	 * @param  IFormRenderer  $renderer
	 * @return static
	 */
	public function setRenderer(IFormRenderer $renderer = NULL)
	{
		$this->getForm()->setRenderer($renderer);
		return $this;
	}


	/**
	 * @return IFormRenderer
	 */
	public function getRenderer()
	{
		return $this->getForm()->getRenderer();
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
	 * @param  string  $type
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function setTemplateFile($path = NULL, $type = self::TPL_FORM)
	{
		if (!array_key_exists($type, $this->templateFile)) {
			throw new \InvalidArgumentException($type);
		}

		$this->templateFile[$type] = $path;
		return $this;
	}


	public function render()
	{
		$template = $this->createTemplate();

		if (!$file = $this->templateFile[self::TPL_FORM]) {
			$file = __DIR__.'/templates/form.latte';
		}

		$template->setFile($file);
		$template->render();
	}


	public function renderErrors()
	{
		$template = $this->createTemplate();

		if (!$file = $this->templateFile[self::TPL_ERRORS]) {
			$file = __DIR__.'/templates/errors.latte';
		}

		$template->setFile($file);
		$template->render();
	}


	/**
	 * @return ITemplate
	 */
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->setTranslator($this->getTranslator());

		if ($this->onBeforeRender) {
			$this->onBeforeRender($this, $template);
		}

		$template->add('form', $this->getForm());

		return $template;
	}


	/**
	 * @param  string  $name
	 * @return Form
	 */
	final protected function createForm($name)
	{
		$form = new Form($this, $name);
		$form->addProtection();

		// Primary event handler, called internally
		$form->onSuccess[] = function ($form, $data) {
			$this->handleSuccess($form, $data);
		};

		// Secondary event handler, proceed to presenter
		$form->onSuccess[] = function ($form, $data) {
			$this->onSuccess($form, $data);
			$this->redrawControl('errors');
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
	 * @param  Form       $form
	 * @param  ArrayHash  $data
	 * @return void
	 */
	abstract protected function handleSuccess($form, $data);
}
