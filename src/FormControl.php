<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use JuniWalk\Forms\Exceptions\InvalidFormControl;
use Nette\Application\UI\Form;
use Nette\Forms\IFormRenderer;
use Nette\Localization\ITranslator;

abstract class FormControl extends \Nette\Application\UI\Control
{
	/**
	 * Form's onSuccess event.
	 * @var callable[]
	 */
	public $onSuccess = [];

	/**
	 * Path to a template file.
	 * @var string
	 */
	protected $templateFile;


	/**
	 * Sets translate adapter.
	 * @param  ITranslator  $translator  Translator instance
	 * @return static
	 */
	public function setTranslator(ITranslator $translator = NULL)
	{
		$this->getForm()->setTranslator($translator);
		return $this;
	}


	/**
	 * Returns translate adapter.
	 * @return ITranslator
	 */
	public function getTranslator()
	{
		return $this->getForm()->getTranslator();
	}


	/**
	 * Sets form renderer.
	 * @param  IFormRenderer  $renderer  Renderer instance
	 * @return static
	 */
	public function setRenderer(IFormRenderer $renderer = NULL)
	{
		$this->getForm()->setRenderer($renderer);
		return $this;
	}


	/**
	 * Returns form renderer.
	 * @return IFormRenderer
	 */
	public function getRenderer()
	{
		return $this->getForm()->getRenderer();
	}


	/**
	 * Get the instance of the Form.
	 * @return Form
	 */
	public function getForm()
	{
		return $this->getComponent('form');
	}


	/**
	 * Set each Form control as enabled or disabled.
	 * @param  bool  $value  Disabled value
	 * @return static
	 */
	public function setDisabled($value)
	{
		$form = $this->getForm();

		foreach ($form->getComponents() as $control) {
			$control->setDisabled($value);
		}

		return $this;
	}


	/**
	 * Set attribute to the form element.
	 * @param  string  $key    Attribute name
	 * @param  mixed   $value  New value
	 * @return static
	 */
	public function setAttribute($key, $value)
	{
		$this->getForm()->getElementPrototype()
			->setAttribute($key, $value);

		return $this;
	}


	/**
	 * Get value of the form element attribute.
	 * @param  string  $key  Attribute name
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		return $this->getForm()->getElementPrototype()
			->getAttribute($key);
	}


	/**
	 * Set defautl values of the Form.
	 * @param  array  $values
	 * @return static
	 */
	public function setDefaults(array $values)
	{
		$this->getForm()->setDefaults($values);
		return $this;
	}


	/**
	 * Adds global error message.
	 * @param  object  $control  Control from the form
	 * @param  string  $message  Error message
	 * @param  array   $params   Message parameters
	 * @return void
	 * @throws InvalidFormControl
	 */
	public function addError($control, $message, array $params = [])
	{
		// If the control is not object or there is no addError method to be used
		if (!is_object($control) || !method_exists($control, 'addError')) {
			throw new InvalidFormControl;
		}

		// Get the instance of the translator
		$translator = $this->getTranslator();

		// If there is translator available
		if ($translator && $translator instanceof ITranslator) {
			$message = $translator->translate($message, $params);
		}

		return $control->addError($message);
	}


	/**
	 * Set custom template file for the form.
	 * @param  string  $path  Path to template
	 * @return static
	 */
	public function setTemplateFile($path = NULL)
	{
		$this->templateFile = $path;
		return $this;
	}


	/**
	 * Control renderer.
	 */
	public function render()
	{
		$template = $this->createTemplate();
		$template->setTranslator($this->getTranslator());
		$template->setFile($this->templateFile ?: __DIR__.'/Form.latte');

		return $template->render();
	}


	/**
	 * Separate renderer just for Form errors.
	 */
	public function renderErrors()
	{
		echo $this->getForm()->render('errors');
	}


	/**
	 * Create internal instance of the Form component.
	 * @param  string  $name  Component name
	 * @return Form
	 */
	protected function createComponentForm($name)
	{
		// Prepare Form control instance
		$form = new Form($this, $name);
		$form->addProtection();

		// Primary onSuccess event, invokes internal handler
		$form->onSuccess[] = function ($form, $data) {
			$this->handleSuccess($form, $data);
		};

		// Secondary onSuccess event, invokes userland handlers
		$form->onSuccess[] = function ($form, $data) {
			$this->onSuccess($form, $data, $this);
		};

		return $form;
	}


	/**
	 * Handle onSuccess event of the form.
	 * @param  Form   $form  Form instance
	 * @param  mixed  $data  Submitted data
	 * @return void
	 */
	protected function handleSuccess($form, $data)
	{
	}
}
