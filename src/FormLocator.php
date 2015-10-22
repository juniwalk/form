<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use JuniWalk\Forms\Exceptions\FormNotFoundException;
use Nette\DI\Container;
use Nette\Forms\IFormRenderer;
use Nette\Localization\ITranslator;

final class FormLocator
{
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
     * Instance of DI container.
     * @var Container
     */
    private $di;

    /**
     * Name of the extension in configuration.
     * @var string
     */
    private $name;


    /**
     * Collect dependencies of the factory class.
     * @param string         $name        Extension name
     * @param Container      $di          DI Container
     * @param ITranslator    $translator  Translator
     * @param IFormRenderer  $renderer    Form renderer
     */
    public function __construct($name, Container $di, ITranslator $translator = null, IFormRenderer $renderer = null)
    {
        $this->translator = $translator;
        $this->renderer = $renderer;
        $this->name = $name;
        $this->di = $di;
    }


    /**
     * Find desired form component factory.
     * @param  string  $form    Form name
     * @param  array   $params  Service parameters
     * @return Form
     * @throws FormNotFoundException
     */
    public function find($form, $params = null)
    {
        // Get the full name of the form service
        $name = sprintf('%s.%s', $this->name, $form);

        // If there is more than 2 arguments
        if (func_num_args() > 2) {
            // Get the parameters without the form name
            $params = array_slice(func_get_args(), 1);
        }

        // If there is no such form registered
        if (!$this->di->hasService($name)) {
            throw new FormNotFoundException($form);
        }

        // Get the instance of the service factory
        $service = $this->di->getService($name);

        // If there is a list of parameters
        if ($params && is_array($params)) {
            return $this->create($service, $params);
        }

        return $service;
    }


    /**
     * Find desired form component factory.
     * @param  object  $service  Service factory
     * @param  array   $params   Parameters
     * @return Form
     */
    private function create($service, array $params)
    {
        // Create instance of the Form component using service factory
        $form = call_user_func_array([$service, 'create'], $params);

        // If the Form instance is invalid
        if (!$form instanceof \Nette\Forms\Form) {
            return $form;
        }

        // Assign default translator and renderer
        $form->setTranslator($this->translator);
        $form->setRenderer($this->renderer);

        return $form;
    }
}
