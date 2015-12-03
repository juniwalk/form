<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use JuniWalk\Forms\Exceptions\FormNotFound;
use Nette\DI\Container;
use Nette\DI\MissingServiceException;
use Nette\Forms\Form;
use Nette\Forms\IFormRenderer;
use Nette\Localization\ITranslator;

final class FormLocator
{
	/**
	 * Name of the extension in configuration.
	 * @var string
	 */
	private $name;

	/**
	 * Instance of DI container.
	 * @var Container
	 */
	private $serviceLocator;

	/**
	 * Instance of Translator class.
	 * @var ITranslator
	 */
	private $translator;

	/**
	 * Instance of the Form renderer class.
	 * @var IFormRenderer
	 */
	private $renderer;


	/**
	 * Collect dependencies of the factory class.
	 * @param string         $name        Extension name
	 * @param Container      $locator     DI Container
	 * @param ITranslator    $translator  Translator
	 * @param IFormRenderer  $renderer    Form renderer
	 */
	public function __construct($name, Container $locator, ITranslator $translator = NULL, IFormRenderer $renderer = NULL)
	{
		$this->name = $name;
		$this->serviceLocator = $locator;
		$this->translator = $translator;
		$this->renderer = $renderer;
	}


	/**
	 * Find desired form component factory.
	 * @param  string  $form    Form name
	 * @param  array   $params  Service parameters
	 * @return Form
	 * @throws FormNotFound
	 */
	public function find($form, $params = NULL)
	{
		$name = sprintf('%s.%s', $this->name, $form);

		if (func_num_args() > 2) {
			$params = array_slice(func_get_args(), 1);
		}

		try {
			$service = $this->serviceLocator->getService($name);

		} catch (MissingServiceException $e) {
			throw new FormNotFound($form, 0, $e);
		}

		return $this->create($service, $params);
	}


	/**
	 * Find desired form component factory.
	 * @param  object  $service  Service factory
	 * @param  array   $params   Parameters
	 * @return object
	 */
	private function create($service, array $params = NULL)
	{
		if (!method_exists($service, 'create')) {
			return $service;
		}

		$form = call_user_func_array([$service, 'create'], $params);

		if (!$form instanceof Form && !$form instanceof FormControl) {
			return $form;
		}

		$form->setTranslator($this->translator);
		$form->setRenderer($this->renderer);

		return $form;
	}
}
