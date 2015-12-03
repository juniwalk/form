<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms\DI;

final class FormsExtension extends \Nette\DI\CompilerExtension
{
	/**
	 * Register extension into DI container.
	 */
	public function loadConfiguration()
	{
		// Register all provided forms into DI container
		foreach ($this->getConfig() as $name => $interface) {
			$this->getContainerBuilder()->addDefinition($this->prefix($name))
				->setImplement($interface);
		}

		// Register factory class into DI container
		$this->getContainerBuilder()->addDefinition($this->prefix('locator'))
			->setClass('JuniWalk\Forms\FormLocator', [$this->name]);
	}
}
