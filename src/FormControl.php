<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms;

use JuniWalk\Forms\Rendering\Bootstrap;
use Nette\Application\UI\Form;

abstract class FormControl extends \Nette\Application\UI\Control
{
    /**
     * Prepare new form instance.
     * @param  string  $name  Component name
     * @return Form
     */
    public function createForm($name)
    {
        // Create instance of Form
        $form = new Form($this, $name);
        $form->setRenderer(new Bootstrap);
        $form->onSuccess[] = [$this, 'handleSuccess'];

        // Enable CSRF protection into each form of this app
        $form->addProtection('Bezpečnostní klíč vypršel, odešlete prosím formulář znovu.');

        return $form;
    }


    /**
     * Event - On successfull submit.
     * @param  Form       $form  Form instance
     * @param  ArrayHash  $data  Submited data
     * @return null
     */
    abstract public function handleSuccess($form, $data);


    /**
     * Disable whole Form component.
     * @return static
     */
    public function disableForm()
    {
        // Get the list of form controls
        $controls = $this['form']->getComponents();

        // Iterate over Form's controls
        foreach ($controls as $control) {
            // Make the control disabled
            $control->setDisabled(true);
        }

        // Chainable
        return $this;
    }


    /**
     * Set autocomplete attribute on the form.
     * @param  mixed  $value  New value
     * @return static
     */
    protected function setAutocomplete($value)
    {
        // Set the value of the autocomplete attribute
        $this['form']->getElementPrototype()->autocomplete = $value;

        // Chainable
        return $this;
    }


    /**
     * Proxy - Flash messages.
     * @param  string  $message  Message to send
     * @param  string  $type     Type of the message
     * @return stdClass
     */
    public function flashMessage($message, $type = 'info')
    {
        // Delegate flash message to the presenter
        return $this->getPresenter()->flashMessage($message, $type);
    }


    /**
     * Proxy - Redirect to desired page.
     * @param  string  $view  Presenter name
     * @param  mixed   $x     Not used
     * @param  mixed   $y     Not used
     * @return null
     */
    public function redirect($view, $x = null, $y = null)
    {
        // Delegate redirect to the view to presenter
        return $this->getPresenter()->redirect($view);
    }


    /**
     * Proxy - Restore previous request.
     * @param  string|null  $request  Request name
     * @return static
     */
    public function restoreRequest($request = null)
    {
        // Get the instance of the presenter
        $presenter = $this->getPresenter();

        // If there is no request and there is redirect pending
        if (!isset($request) && isset($presenter->backlink)) {
            // Get the backlink redirect
            $request = $presenter->backlink;
        }

        // Delegate restoreRequest to Presenter
        $presenter->restoreRequest($request);

        // Chainable
        return $this;
    }


    /**
     * Render form component.
     * @return null
     */
    final public function render()
    {
        // Just delegate render to a form
        return $this['form']->render();
    }


    /**
     * Build our Form component.
     * @param  string  $name  Component name
     * @return Form
     */
    final protected function createComponentForm($name)
    {
        // Create new form instance
        return $this->createForm($name);
    }
}
