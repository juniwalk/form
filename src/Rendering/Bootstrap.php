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
     * Event - Before rendering.
     * @var array
     */
    public $onBeforeRender;

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
        $this->setWrapper('control container', null);
        $this->setWrapper('control description', 'span class="help-block"');
        $this->setWrapper('control errorcontainer', 'span class="help-block"');

        // Standard error messages in Bootstrap's alert control
        $this->setWrapper('error container', 'div class="errors"');
        $this->setWrapper('error item', 'div class="alert alert-danger"');

        // Append new method to beforeRender event to setup render mode classes
        $this->onBeforeRender[] = function(self $self, Form $form) {
            // Call internal process method
            $this->process($form);
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

        // Append new method to beforeRender event to setup render mode classes
        $this->onBeforeRender[] = function (self $self, Form $form) use ($method) {
            // Call internal render method
            $this->$method($form);
        };

        // Chainable
        return $this;
    }


    /**
     * Provides complete form rendering.
     * @param  Form         $form  Form instance
     * @param  string|null  $mode  Part of the form to render
     * @return string
     */
    public function render(Form $form, $mode = null)
    {
        // Call before rendering event methods
        $this->onBeforeRender($this, $form);

        // Call parent render method and return output
        return parent::render($form, $mode);
    }


    /**
     * Process form before rendering.
     * @param Form  $form  Form instance
     */
    protected function process(Form $form)
    {
        // Get the list of all form controls
        $controls = $form->getControls();

        // If there are no controls
        if (empty($controls)) {
            return null;
        }

        // Iterate over all form controls
        foreach ($controls as $control) {
            // Set the control up
            $this->setup($control);
        }
    }


    /**
     * Provides complete form rendering.
     * @param IControl  $control  Control instance
     */
    protected function setup(IControl $control)
    {
        // Has the primary button been used?
        static $primaryUsed = false;

        // Get the control prototype element
        $input = $control->getControlPrototype();

        // If the control is a button
        if ($control instanceof Button) {
            // Prepare default class name
            $class = 'btn btn-default';

            // Shall the button be marked?
            if ($control instanceof SubmitButton && !$primaryUsed) {
                $class = 'btn btn-primary';
                $primaryUsed = true;
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
        }
    }


    /**
     * Classess for vertical form.
     */
    protected function renderVertical(Form $form)
    {
        // Set Form class into horizontal
        $form->getElementPrototype()
            ->setClass(null);

        // Set classes and elements for vertical layout rendering
        $this->setWrapper('label container', 'span class="control-label"');
    }


    /**
     * Classess for horizontal form.
     */
    protected function renderHorizontal(Form $form)
    {
        // Set Form class into horizontal
        $form->getElementPrototype()
            ->setClass('form-horizontal');

        // Set classes and elements for horizontal layout rendering
        $this->setWrapper('control container', 'div class="col-sm-9"');
        $this->setWrapper('label container', 'div class="col-sm-3 control-label"');
    }


    /**
     * Classess for inline form.
     */
    protected function renderInline(Form $form)
    {
        // Set Form class into inline
        $form->getElementPrototype()
            ->setClass('form-inline');

        // Set classes and elements for inline layout rendering
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
