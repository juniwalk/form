<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\AbstractForm;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
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
		$init->addBody(ControlFactory::class.'::registerControls();');
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
