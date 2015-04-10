<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms\Rendering;

use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Form;
use Nette\Forms\IControl;

class Bootstrap extends \Nette\Forms\Rendering\DefaultFormRenderer
{
    /**
     * Form classes for each render mode.
     * @var string
     */
    const VERTICAL = 'vertical';
    const HORIZONTAL = 'horizontal';
    const INLINE = 'inline';

    /**
     * List of primary controls.
     * @var array
     */
    protected static $primaryUsed = [];

    /**
     * Event - Before rendering.
     * @var array
     */
    public $onBeforeRender;
    protected $rendered;

    /**
     * Layout rendering mode.
     * @var string
     */
    protected $layout;


    /**
     * Set default render mode.
     */
    public function __construct()
    {
        // Standard classes which are the same for all layout modes
        $this->setWrapper('pair container', 'div class="form-group"');
        $this->setWrapper('pair .error', 'has-error');
        $this->setWrapper('controls container', null);
        $this->setWrapper('control description', 'span class="help-block"');
        $this->setWrapper('control errorcontainer', 'span class="help-block"');

        // Standard error messages in Bootstrap's alert control
        $this->setWrapper('error container', 'div class="errors"');
        $this->setWrapper('error item', 'div class="alert alert-danger"');

        // Append new method to beforeRender event
        $this->onBeforeRender[] = function() {
            // Call internal process method
            $this->process();
        };

        // Set default layout to horizontal
        $this->setLayout(static::HORIZONTAL);
    }


    /**
     * Set default render layout mode.
     * @param  string  $mode  Render mode
     * @return static
     * @throws Exception
     */
    public function setLayout($mode)
    {
        // Get the name of the render method
        $method = 'render'.$mode;

        // If there is no such render mode
        if (!method_exists($this, $method)) {
            throw new \Exception('Unsupported render mode "'.$mode.'".');
        }

        // Set layout mode
        $this->layout = $mode;

        // Append new method to beforeRender event
        $this->onBeforeRender[] = function() use ($method) {
            // Call internal render method
            $this->$method();
        };

        // Chainable
        return $this;
    }


    /**
     * Begin rendering.
     * @return string
     */
    public function renderBegin()
    {
        // Call before rendering event methods
        $this->rendered || $this->onBeforeRender();

        // Call parent method now
        return parent::renderBegin();
    }


    /**
     * End rendering.
     * @return string
     */
    public function renderEnd()
    {
        // Call before rendering event methods
        $this->rendered || $this->onBeforeRender();

        // Call parent method now
        return parent::renderEnd();
    }


    /**
     * Render form body.
     * @return string
     */
    public function renderBody()
    {
        // Call before rendering event methods
        $this->rendered || $this->onBeforeRender();

        // Call parent method now
        return parent::renderBody();
    }


    /**
     * Process form before rendering.
     */
    protected function process()
    {
        // Get the list of all form controls
        $controls = $this->form->getControls();

        // If there are no controls
        if (empty($controls)) {
            return null;
        }

        // Iterate over all form controls
        foreach ($controls as $control) {
            // Set the control up
            $this->setup($control);
        }

        // The form has been rendered
        $this->rendered = true;
    }


    /**
     * Provides complete form rendering.
     * @param IControl  $control  Control instance
     */
    protected function setup(IControl $control)
    {
        // Get the control prototype element
        $input = $control->getControlPrototype();

        // If the control is a button
        if ($control instanceof Button) {
            // Prepare default class name
            $class = 'btn btn-default';

            // Get Id hash of the Form control object
            $guid = spl_object_hash($this->form);

            // If this is instance of submit button and it has not been marked primary yet
            if ($control instanceof SubmitButton && !isset($this::$primaryUsed[$guid])) {
                // Switch the class to primary
                $class = 'btn btn-primary';

                // Make sure we won't mark any other
                // submit button with the primary color
                $this::$primaryUsed[$guid] = true;
            }

            // Add class to the control prototype
            $input->addClass($class);
        }

        // If this is control based on text or either one of the select boxes
        elseif ($control instanceof TextBase || $control instanceof SelectBox || $control instanceof MultiSelectBox) {
            // If this is inline form layout and there is no placeholder text in the input
            if ($this->layout == static::INLINE && !$input->placeholder) {
                // Copy text from the label into placeholder attribute of the input
                $input->placeholder = $control->getLabel()->getText();
            }

            // Add form-control class to the control prototype
            $input->addClass('form-control');
        }

        // For all checkboxes and radioses, give option to display them inline if there is 'inline' class available
        elseif ($control instanceof Checkbox || $control instanceof CheckboxList || $control instanceof RadioList) {
            // Set default classes for the input list
            $row = $control->getSeparatorPrototype()
                ->setName('div')
                ->setClass($input->type);

            // If this inputs should be displayed inline
            if (strpos($input->getClass(), 'inline') !== false) {
                // Set proper class of the row to inline
                $row->setClass($input->type.'-inline');
            }

            // Add name of the choice group to the separator prefixed by mask
            $row->addClass(sprintf($control::$idMask, $control->getName()));
        }
    }


    /**
     * Classess for vertical form.
     */
    protected function renderVertical()
    {
        // Set Form class into horizontal
        $this->form->getElementPrototype()
            ->setClass(null);

        // Set classes and elements for vertical layout rendering
        $this->setWrapper('control container', null);
        $this->setWrapper('label container', 'span class="control-label"');
    }


    /**
     * Classess for horizontal form.
     */
    protected function renderHorizontal()
    {
        // Set Form class into horizontal
        $this->form->getElementPrototype()
            ->setClass('form-horizontal');

        // Set classes and elements for horizontal layout rendering
        $this->setWrapper('control container', 'div class="col-sm-9"');
        $this->setWrapper('label container', 'div class="col-sm-3 control-label"');
    }


    /**
     * Classess for inline form.
     */
    protected function renderInline()
    {
        // Set Form class into inline
        $this->form->getElementPrototype()
            ->setClass('form-inline');

        // Set classes and elements for inline layout rendering
        $this->setWrapper('control container', null);
        $this->setWrapper('label container', 'span class="sr-only"');
    }


    /**
     * Set new value of the wrapper.
     * @param  string       $name   Wrapper name
     * @param  string|null  $value  New value
     * @return static
     */
    protected function setWrapper($name, $value = null)
    {
        // Get the name parts
        $name = explode(' ', $name);

        // Set new value of the wrapper
        $this->wrappers[$name[0]][$name[1]] = $value;

        // Chainable
        return $this;
    }
}
