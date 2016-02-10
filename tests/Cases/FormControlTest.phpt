<?php

/**
 * TEST: Basic functionality of FormControl.
 * @testCase
 *
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Tests;

use JuniWalk\Tests\Files\Form;
use JuniWalk\Tests\Files\Translator;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__.'/../bootstrap.php';

final class FormControlTest extends \Tester\TestCase
{
	/** @var Nette\Http\Request */
	private $request;


	public function testIntegrity()
	{
		$form = $this->createForm();

		Assert::type('Nette\Forms\Form', $form->getForm());
		Assert::type('Nette\Forms\IFormRenderer', $form->getRenderer());
		Assert::type('Nette\Localization\ITranslator', $form->getTranslator());
	}


	public function testSubmitSuccess()
	{
		$form = $this->createForm()->setDefaults('Martin');
		$form->onSuccess[] = function ($form, $data) {
			Assert::same('Martin', $data->name);
			Assert::true(isset($form['name']));
		};

		$form->fireEvents();
	}


	public function testSubmitError()
	{
		$form = $this->createForm(TRUE);
		$form->onError[] = function ($form) {
			Assert::false(empty($form->getErrors()));
			Assert::true(isset($form['name']));
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
		$form = new Form($this->request);
		$form->setTranslator(new Translator);
		$form->setRenderer(new DefaultFormRenderer);

		if (!$protected) {
			$form->disableProtection();
		}

		return $form;
	}
}

(new FormControlTest)->run();
