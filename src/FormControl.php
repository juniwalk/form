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
     * Render form component.
     */
    public function render()
    {
        // Just delegate render to a form
        $this['form']->render();
    }


    /**
     * Build our Form component.
     * @return Form
     */
    abstract protected function createComponentForm();


    /**
     * Event - On successfull submit.
     * @param  Form       $form  Form instance
     * @param  ArrayHash  $data  Submited data
     * @return null
     */
    abstract public function handleSuccess($form, $data);


    /**
     * Prepare new form instance.
     * @return Form
     */
    protected function createForm()
    {
        // Create instance of Form
        $form = new Form;
        $form->setRenderer(new Bootstrap);
        $form->onSuccess[] = [$this, 'handleSuccess'];

        // Enable CSRF protection into each form of this app
        $form->addProtection('Bezpečnostní klíč vypršel, odešlete prosím formulář znovu.');

        return $form;
    }


    /**
     * Disable whole Form component.
     * @param Form  $form  Form to disbale
     */
    protected function disableForm(Form $form)
    {
        // Get the list of form controls
        $controls = $form->getComponents();

        // Iterate over Form's controls
        foreach ($controls as $control) {
            // Make the control disabled
            $control->setDisabled(true);
        }
    }


    /**
     * Set autocomplete attribute on the form.
     * @param Form   $form   Form instance
     * @param mixed  $value  New value
     */
    protected function setAutocomplete(Form $form, $value)
    {
        // Set the value of the autocomplete attribute
        $form->getElementPrototype()->autocomplete = $value;
    }


    /**
     * Proxy - Flash messages.
     * @param string  $message  Message to send
     * @param string  $type     Type of the message
     */
    public function flashMessage($message, $type = 'info')
    {
        // Delegate flash message to the presenter
        $this->getPresenter()->flashMessage($message, $type);
    }


    /**
     * Proxy - Redirect to desired page.
     * @param string  $view  Presenter name
     * @param mixed   $x     Not used
     * @param mixed   $y     Not used
     */
    public function redirect($view, $x = null, $y = null)
    {
        // Delegate redirect to the view to presenter
        $this->getPresenter()->redirect($view);
    }


    /**
     * Proxy - Restore previous request.
     * @param string  $request  Request name
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
    }
}
