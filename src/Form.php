<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use Nette\ComponentModel\IContainer;
use Nette\Localization\ITranslator;

class Form extends \Nette\Application\UI\Form
{
    /**
     * Collect dependencies of the Form component.
     * @param IContainer  $parent  Parent container
     * @param string      $name    Component name
     */
    public function __construct(IContainer $parent, $name)
    {
        // Call parent constructor with params
        parent::__construct($parent, $name);

        // Add internal success handler into event listeners
        $this->onSuccess[] = function($form, $data) {
            $this->handleSuccess($form, $data);
        };
    }


    /**
     * Adds global error message.
     * @param string  $message  Error message
     */
    public function addError($message, array $params = [])
    {
        // If there is translator defined in the Form
        if ($this->translator instanceof ITranslator) {
            $message = $this->translator->translate($message, $params);
        }

        return parent::addError($message);
    }


    /**
     * Disable whole Form component.
     * @param  bool  $value  Disabled value
     * @return static
     */
    public function setDisabled($value)
    {
        foreach ($this->getComponents() as $control) {
            $control->setDisabled($value);
        }

        return $this;
    }


    /**
     * Set attribute to the form element.
     * @param  string  $key    Attribute name
     * @param  mixed   $value  New value
     * @return static
     */
    public function setAttribute($key, $value)
    {
        $this->getElementPrototype()->setAttribute($key, $value);
        return $this;
    }


    /**
     * Get value of the form element attribute.
     * @param  string  $key  Attribute name
     * @return mixed
     */
    public function getAttribute($key)
    {
        return $this->getElementPrototype()->getAttribute($key);
    }


    /**
     * Signal - On successfull submit.
     * @param  static  $form  Form instance
     * @param  mixed   $data  Submited data
     * @return null
     */
    protected function handleSuccess($form, $data)
    {
    }
}
