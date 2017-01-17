<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\AbstractForm;
use JuniWalk\Form\Controls;
use JuniWalk\Form\FormFactory;
use Nette\Forms\Container as Form;
use Nette\PhpGenerator\ClassType;

final class FormExtension extends \Nette\DI\CompilerExtension
{
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('formFactory'))
			->setClass(FormFactory::class);

		foreach ($this->findByType(AbstractForm::class) as $def) {
			$def->addSetup('setFormFactory');
		}
	}


	/**
	 * @param ClassType  $class
	 */
	public function afterCompile(ClassType $class)
	{
		$init = $class->getMethods()['initialize'];
		$init->addBody(__CLASS__.'::registerControls();');
	}


	public static function registerControls()
	{
		Form::extensionMethod('addDateTime', function (Form $form, string $name, string $label = NULL) {
			return $form[$name] = new Controls\DateTimePicker($label);
		});
	}


	/**
	 * @param  string  $type
	 * @return ServiceDefinition[]
	 */
	private function findByType(string $type) : array
	{
		$builder = $this->getContainerBuilder();
		$type = ltrim($type, '\\');

		return array_filter($builder->getDefinitions(), function ($def) use ($type) {
			return is_a($def->getClass(), $type, TRUE) || is_a($def->getImplement(), $type, TRUE);
		});
	}
}
