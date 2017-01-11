<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\Controls;
use Nette\Forms\Container;
use Nette\Localization\ITranslator;
use Nette\PhpGenerator\ClassType;

final class FormExtension extends \Nette\DI\CompilerExtension
{
	/** @var ServiceDefinition[] */
	private $forms = [];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->getConfig() as $name => $interface) {
			$this->forms[$name] = $builder->addDefinition($this->prefix($name))
				->setImplement($interface);
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		if ($translator = $builder->getByType(ITranslator::class)) {
			$translator = '@'.$translator;
		}

		foreach ($this->forms as $form) {
			$form->addSetup('setTranslator', [$translator]);
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
		Container::extensionMethod('addDateTime', function (Container $container, $name, $label = NULL) {
			return $container[$name] = new Controls\DateTimePicker($label);
		});
	}
}
