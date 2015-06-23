<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use JuniWalk\FormsRenderer\Bootstrap;
use Nette\ComponentModel\IContainer;

class Form extends \Nette\Application\UI\Form
{
    /**
     * Initialize new application form.
     * @param IContainer  $parent  Parent container
     * @param string      $name    Component name
     */
    public function __construct(IContainer $parent = null, $name = null)
    {
        // Call parent constructor with params
        parent::__construct($parent, $name);

        // Set bootstrap renderer and subscribe to onSuccess event handler
        $this->setRenderer(new Bootstrap)->setLayout(Bootstrap::HORIZONTAL);
        $this->onSuccess[] = function($form, $data) {
            $this->handleSuccess($form, $data);
        };

        // Enable CSRF protection into each form of this app
        $this->addProtection('Bezpečnostní klíč vypršel, odešlete prosím formulář znovu.');
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
    protected function setAttribute($key, $value)
    {
        $this->getElementPrototype()->setAttribute($key, $value);
        return $this;
    }


    /**
     * Get value of the form element attribute.
     * @param  string  $key  Attribute name
     * @return mixed
     */
    protected function getAttribute($key)
    {
        return $this->getElementPrototype()->getAttribute($key);
    }


    /**
     * Set layout of the Bootstrap form.
     * @param  string  $mode  Layout mode
     * @return static
     */
    public function setLayout($mode)
    {
        $this->getRenderer()->setLayout($mode);
        return $this;
    }


    /**
     * Get layout mode of the form.
     * @return string
     */
    public function getLayout()
    {
        return $this->getRenderer()->getLayout();
    }


    /**
     * Render form component in vertical layout.
     */
    final public function renderVertical()
    {
        return $this->setLayout(Bootstrap::VERTICAL)->render();
    }


    /**
     * Render form component in horizontal layout.
     */
    final public function renderHorizontal()
    {
        return $this->setLayout(Bootstrap::HORIZONTAL)->render();
    }


    /**
     * Render form component in inline layout.
     */
    final public function renderInline()
    {
        return $this->setLayout(Bootstrap::INLINE)->render();
    }


    /**
     * Signal - On successfull submit.
     * @param  static  $form  Form instance
     * @param  mixed   $data  Submited data
     * @return null
     */
    protected function handleSuccess($form, $data)
    {
        return null;
    }
}
