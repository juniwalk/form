<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\AbstractForm;
use JuniWalk\Form\Controls;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\Forms\Container as Form;
use Nette\PhpGenerator\ClassType;

final class FormExtension extends CompilerExtension
{
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


	public function afterCompile(ClassType $class): void
	{
		$init = $class->getMethods()['initialize'];
		$init->addBody(__CLASS__.'::registerControls();');
	}


	public static function registerControls(): void
	{
		Form::extensionMethod('addDateTime', function(
			Form $form,
			string $name,
			string $label = null,
		) {
			return $form[$name] = new Controls\DateTimePicker($label);
		});

		Form::extensionMethod('addSelectEnum', function(
			Form $form,
			string $name,
			string $label = null,
			?array $items = null,
			?int $size = null,
		) {
			return $form[$name] = (new Controls\SelectBoxEnum($label, $items))
				->setHtmlAttribute('size', $size > 1 ? $size : null);
		});

		Form::extensionMethod('addRadioEnum', function(
			Form $form,
			string $name,
			string $label = null,
			?array $items = null,
		) {
			return $form[$name] = new Controls\RadioListEnum($label, $items);
		});
	}


	private function findByType(string $type): array
	{
		$definitions = $this->getContainerBuilder()
			->getDefinitions();

		return array_filter($definitions, function($def) use ($type): bool {
			return is_a($def->getType(), $type, true) || ($def instanceof FactoryDefinition && is_a($def->getResultType(), $type, true));
		});
	}
}
