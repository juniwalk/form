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
     * @var ITranslator|NULL
     */
    private $translator;


	/**
	 * @param ITranslator|NULL  $translator
	 */
    public function __construct(ITranslator $translator = NULL)
    {
        $this->translator = $translator;
    }


	/**
	 * @return ITranslator|NULL
	 */
	public function getTranslator()
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
