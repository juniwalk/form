<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use Nette\Forms\IFormRenderer;
use Nette\Localization\ITranslator;

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

		if ($renderer = $builder->getByType(IFormRenderer::class)) {
			$renderer = '@'.$renderer;
		}

		foreach ($this->forms as $form) {
			$form->addSetup('setTranslator', [$translator]);
			$form->addSetup('setRenderer', [$renderer]);
		}
	}
}
