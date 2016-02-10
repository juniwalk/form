<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Tests\Files;

use Nette\Forms\Form as NetteForm;
use Nette\Http\Request;
use Tester\Assert;

final class Form extends \JuniWalk\Form\FormControl
{
	/** @var Request */
	private $request;


	/**
	 * @param TestCase  $test
	 * @param Request   $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}


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
		$form->httpRequest = $this->request;

		$form->fireEvents();
	}


	public function disableProtection()
	{
		unset($this['form'][NetteForm::PROTECTOR_ID]);
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
	 * @return void
	 */
	protected function handleSuccess($form, $data)
	{
		Assert::true(TRUE);
	}
}
