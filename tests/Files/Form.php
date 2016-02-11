<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Form\Tests\Files;

final class Form extends \JuniWalk\Form\FormControl
{
	/**
	 * @param  string  $name
	 * @return static
	 */
	public function setDefaults($name)
	{
		$this->getForm()->setDefaults([
			'name' => $name,
		]);

		return $this;
	}


	public function fireEvents()
	{
		$form = $this->getForm();
		$form->setSubmittedBy($form['submit']);
		$form->fireEvents();
	}


	public function disableProtection()
	{
		$form = $this->getForm();
		unset($form[$form::PROTECTOR_ID]);
	}


	/**
	 * @param  string  $name
	 * @return Form
	 */
	protected function createComponentForm($name)
	{
		$form = $this->createForm($name, 'test.form.csrf');
		$form->addText('name', 'test.form.name');
		$form->addSubmit('submit', 'test.form.submit');

		return $form;
	}


	/**
	 * @param  Form       $form
	 * @param  ArrayHash  $data
	 */
	protected function handleSuccess($form, $data)
	{
		// Internal onSuccess handler
	}
}
