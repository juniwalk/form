<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\AbstractForm;
use JuniWalk\Form\Controls;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\Forms\Container as Form;
use Nette\PhpGenerator\ClassType;

final class FormExtension extends CompilerExtension
{
	/**
	 * @return void
	 */
	public function beforeCompile(): void
	{
		foreach ($this->findByType(AbstractForm::class) as $def) {
			if ($def instanceof FactoryDefinition) {
				$def = $def->getResultDefinition();
			}

			$def->addSetup('setHttpRequest');
			$def->addSetup('setTranslator');
		}
	}


	/**
	 * @param  ClassType  $class
	 * @return void
	 */
	public function afterCompile(ClassType $class): void
	{
		$init = $class->getMethods()['initialize'];
		$init->addBody(__CLASS__.'::registerControls();');
	}


	/**
	 * @return void
	 */
	public static function registerControls(): void
	{
		Form::extensionMethod('addDateTime', function(Form $form, string $name, string $label = null) {
			return $form[$name] = new Controls\DateTimePicker($label);
		});
	}


	/**
	 * @param  string  $type
	 * @return Definition[]
	 */
	private function findByType(string $type): iterable
	{
		$definitions = $this->getContainerBuilder()
			->getDefinitions();

		return array_filter($definitions, function($def) use ($type): bool {
			return is_a($def->getType(), $type, true) || ($def instanceof FactoryDefinition && is_a($def->getResultType(), $type, true));
		});
	}
}
