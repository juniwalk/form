<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\Tests\Files;

final class Translator implements \Nette\Localization\ITranslator
{
	/**
	 * @var string[]
	 */
	private $messages = [
		'test.form.csrf' => 'Token has expired, submit form again.',
		'test.form.name' => 'Name',
		'test.form.submit' => 'Submit',
	];


	/**
	 * @param  string  $message
	 * @param  int|NULL  $count
	 * @return string
	 */
	function translate($message, ?int $count = NULL) : string
	{
		return $this->messages[$message] ?? $message;
	}
}
