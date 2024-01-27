<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Enums\LabeledEnum;
use Nette\Forms\Controls\RadioList;
use InvalidArgumentException;
use ValueError;

final class RadioListEnum extends RadioList
{
	private ?string $enumType = null;


	public function setEnumType(string $enumType): self
	{
		if (!is_subclass_of($enumType, LabeledEnum::class)) {
			throw new InvalidArgumentException('Enum has to implement '.LabeledEnum::class);
		}

		$this->enumType = $enumType;
		return $this;
	}


	public function getCases(): array
	{
		return Arrays::map($this->getItems(), fn($value, $key) => $this->enumType::make($key));
	}


	/**
	 * @throws InvalidArgumentException
	 */
	public function setItems(array $enums, bool $useKeys = true): self
	{
		$items = Arrays::walk($enums, function(LabeledEnum $enum) {
			if (!$enum instanceof $this->enumType) {
				throw new InvalidArgumentException('Enum does not match items of type '.$this->enumType);
			}

			yield $enum->value => $enum->label();
		});

		return parent::setItems($items, $useKeys);
	}


	/**
	 * @throws ValueError
	 */
	public function setValue(/*?LabeledEnum*/ $value): self
	{
		if ($value && !$value instanceof $this->enumType) {
			$value = $this->enumType::make($value);
		}

		return parent::setValue($value?->value ?? null);
	}


	public function getValue(): ?LabeledEnum
	{
		return $this->enumType::make($this->value, false);
	}


	public function setDisabled(/*bool|array*/ $value = true)//: self
	{
		if (is_array($value)) {
			$value = Arrays::map($value, fn($item) => $item->value);
		}

		return parent::setDisabled($value);
	}
}
