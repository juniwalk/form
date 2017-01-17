<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;

final class FormFactory
{
    /**
     * @var ITranslator
     */
    private $translator;


	/**
	 * @param ITranslator  $translator
	 */
    public function __construct(ITranslator $translator)
    {
        $this->translator = $translator;
    }


	/**
	 * @return ITranslator
	 */
	public function getTranslator() : ITranslator
	{
		return $this->translator;
	}


	/**
	 * @return Form
	 */
    public function create() : Form
    {
        $form = new Form;
        $form->setTranslator($this->translator);

        return $form;
    }
}
