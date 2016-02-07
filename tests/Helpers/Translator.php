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

final class Translator implements \Nette\Localization\ITranslator
{
	/** @var TestCase */
	private $test;

	/** @var string[] */
	private $messages = [
		'test.form.csrf' => 'Token has expired, submit form again.',
		'test.form.name' => 'Name',
		'test.form.submit' => 'Submit',
	];


	/**
	 * @param TestCase  $test
	 */
	public function __construct(TestCase $test)
	{
		$this->test = $test;
	}


	/**
	 * @param  string  $message
	 * @param  int     $count
	 * @return string
	 */
	function translate($message, $count = NULL)
	{
		$this->test->assertArrayHasKey($message, $this->messages);

		if (!$this->messages[$message]) {
			return $message;
		}

		return $this->messages[$message];
	}
}
