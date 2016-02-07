<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Tests\Helpers;

use PHPUnit_Framework_TestCase as TestCase;
use Nette\Forms\Form;
use Nette\Http\Request;

final class Form extends \JuniWalk\Form\FormControl
{
	/** @var TestCase */
	private $test;

	/** @var Request */
	private $request;


	/**
	 * @param TestCase  $test
	 * @param Request   $request
	 */
	public function __construct(TestCase $test, Request $request)
	{
		$this->test = $test;
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
		unset($this['form'][Form::PROTECTOR_ID]);
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
		$this->test->assertTrue(TRUE);
	}
}
