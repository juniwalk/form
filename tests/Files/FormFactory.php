<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\Tests\Files;

interface FormFactory
{
	/**
	 * @return Form
	 */
	public function create() : Form;
}
