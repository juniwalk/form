<?php

/**
 * TEST: Basic functionality of FormControl.
 * @testCase
 *
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\Tests\Cases;

use JuniWalk\Form\Tests\Files\FormFactory;
use Nette\Localization\ITranslator;
use Tester\Assert;

require __DIR__.'/../bootstrap.php';

final class FormControlTest extends \Tester\TestCase
{
	/** @var Form */
	private $form;


	public function testIntegrity()
	{
		$form = $this->createForm();
		Assert::type(ITranslator::class, $form->getTranslator());
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
			->getByType(FormFactory::class)
			->create();
	}
}

(new FormControlTest)->run();
