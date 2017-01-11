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

namespace JuniWalk\Form\Tests\Cases;

use JuniWalk\Form\Tests\Files\IFormFactory;
use Tester\Assert;

require __DIR__.'/../bootstrap.php';

final class FormControlTest extends \Tester\TestCase
{
	/** @var Form */
	private $form;


	public function testIntegrity()
	{
		$form = $this->createForm();
		Assert::type('Nette\Localization\ITranslator', $form->getTranslator());
	}


	public function testEventHandler()
	{
		$form = $this->createForm();
		$form->disableProtection();
		$form->setDefaults('Martin');

		$form->onSuccess[] = function ($form, $data) {
			Assert::same('Martin', $data->name);
			$form->addError('test.form.csrf');
		};

		$form->onError[] = function ($form) {
			Assert::false(empty($form->getErrors()));
		};

		$form->fireEvents();
	}


	/**
	 * @return Form
	 */
	private function createForm()
	{
		if (isset($this->form)) {
			return $this->form;
		}

		return $this->form = createContainer()
			->getByType(IFormFactory::class)
			->create();
	}
}

(new FormControlTest)->run();
