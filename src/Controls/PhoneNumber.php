<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Format;
use JuniWalk\Utils\Sanitize;
use Nette\Forms\Controls\TextInput;
use Stringable;

final class PhoneNumber extends TextInput
{
	public function __construct(string|Stringable|null $label = null, ?int $maxLength = null)
	{
		parent::__construct($label, $maxLength);
		$this->setHtmlType('tel');
	}


	/**
	 * @param ?string $value
	 */
	public function setValue(mixed $value = null): static
	{
		$this->value = Format::phoneNumber($value);
		$this->rawValue = (string) $value;
		return $this;
	}


	public function getValue(): ?string
	{
		/** @var ?string */
		$value = parent::getValue();
		return Sanitize::phoneNumber($value);
	}
}
