<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\AbstractForm;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator\ClassType;

use function array_filter;
use function is_a;

final class FormExtension extends CompilerExtension
{
	public function beforeCompile(): void
	{
		$definitions = $this->getContainerBuilder()->getDefinitions();
		$definitions = array_filter($definitions, static function($stmt): bool {
			if (is_a($stmt->getType() ?? '', AbstractForm::class, true)) {
				return true;
			}

			if (!$stmt instanceof FactoryDefinition) {
				return false;
			}

			return is_a($stmt->getResultType() ?? '', AbstractForm::class, true);
		});

		foreach ($definitions as $stmt) {
			if ($stmt instanceof FactoryDefinition) {
				$stmt = $stmt->getResultDefinition();
			}

			if (!$stmt instanceof ServiceDefinition) {
				continue;
			}

			$stmt->addSetup('setHttpRequest');
			$stmt->addSetup('setTranslator');
		}
	}


	public function afterCompile(ClassType $class): void
	{
		$init = $class->getMethods()['initialize'];
		$init->addBody(ControlFactory::class.'::registerControls();');
	}
}
