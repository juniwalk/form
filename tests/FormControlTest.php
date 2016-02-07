<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Tests;

use JuniWalk\Tests\Helpers\Form;
use JuniWalk\Tests\Helpers\Translator;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Http\RequestFactory;

final class FormControlTest extends \PHPUnit_Framework_TestCase
{
	/** @var Nette\Http\Request */
	private $request;


	public function testIntegrity()
	{
		$form = $this->createForm();

		$this->assertInstanceOf('Nette\Forms\Form', $form->getForm());
		$this->assertInstanceOf('Nette\Forms\IFormRenderer', $form->getRenderer());
		$this->assertInstanceOf('Nette\Localization\ITranslator', $form->getTranslator());
	}


	public function testSubmitSuccess()
	{
		$form = $this->createForm()->setDefaults('Martin');
		$form->onSuccess[] = function ($form, $data) {
			$this->assertArrayHasKey('name', $form);
			$this->assertSame('Martin', $data->name);
		};

		$form->fireEvents();
	}


	public function testSubmitError()
	{
		$form = $this->createForm(TRUE);
		$form->onError[] = function ($form) {
			$this->assertArrayHasKey('name', $form);
			$this->assertNotEmpty($form->getErrors());
		};

		$form->fireEvents();
	}


	protected function setUp()
	{
		$this->request = (new RequestFactory)->createHttpRequest();
		$_SERVER['REQUEST_METHOD'] = 'POST';
	}


	/**
	 * @param  bool  $protected
	 * @return Form
	 */
	private function createForm($protected = FALSE)
	{
		$form = new Form($this, $this->request);
		$form->setTranslator(new Translator($this));
		$form->setRenderer(new DefaultFormRenderer);

		if (!$protected) {
			$form->disableProtection();
		}

		return $form;
	}
}
