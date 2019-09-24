<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form;

use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;

final class FormFactory
{
    /** @var ITranslator|null */
    private $translator;


	/**
	 * @param ITranslator|null  $translator
	 */
    public function __construct(ITranslator $translator = null)
    {
        $this->translator = $translator;
    }


	/**
	 * @return ITranslator|null
	 */
	public function getTranslator(): ?ITranslator
	{
		return $this->translator;
	}


	/**
	 * @return Form
	 */
    public function create(): Form
    {
        $form = new Form;
        $form->setTranslator($this->translator);

        return $form;
    }
}
