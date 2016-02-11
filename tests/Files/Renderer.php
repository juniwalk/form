<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Form\Tests\Files;

use Nette;

final class Renderer implements Nette\Forms\IFormRenderer
{
	/**
	 * @param  Nette\Forms\Form  $form
	 * @return string
	 */
	function render(Nette\Forms\Form $form)
	{
		return $form->render();
	}
}
