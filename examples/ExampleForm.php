<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Forms
 * @link      https://github.com/juniwalk/forms
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Forms\Examples;

abstract class ExampleForm extends \JuniWalk\Forms\FormControl
{
    /**
     * Prepare new form instance.
     * @param  string  $name  Component name
     * @return Form
     */
    public function createForm($name)
    {
        // Get standard implementation
        $form = parent::createForm($name);

        // Change layout of the form
        // VERTICAL | HORIZONTAL | INLINE
        $form->getRenderer()
            ->setLayout('vertical');

        // Just standard text input asking for name
        $name = $form->addText('name', 'Your name')
            ->setDefaultValue('John Doe')
            ->setRequired('Your name is required to continue.');

        // Add submit button to the end of form
        $form->addSubmit('submit', 'Save');

        return $form;
    }


    /**
     * Event - On successfull submit.
     * @param  Form       $form  Form instance
     * @param  ArrayHash  $data  Submited data
     * @return null
     */
    public function handleSuccess($form, $data)
    {
        // Handle your form submit here
        $this->flashMessage('Your name is '.$data->name.'.', 'info');
        $this->redirect('this');
    }
}
