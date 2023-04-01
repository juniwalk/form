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

final class RadioListEnum extends RadioList
{
	private string $backedEnum;


	public function getCases(): array
	{
		return Arrays::map($this->getItems(), function($value) {
			return $this->backedEnum::tryMake($value);
		});
	}


	/**
	 * @throws InvalidArgumentException
	 */
	public function setItems(array $enums, bool $useKeys = true): self
	{
		$class = null;
		$items = [];

		foreach ($enums as $enum) {
			if (!$enum instanceof LabeledEnum) {
				throw new InvalidArgumentException('Enum has to implement '.LabeledEnum::class);
			}

			if ($class && !$enum instanceof $class) {
				throw new InvalidArgumentException('Enum does not match items of type '.$class);
			}

			$items[$enum->value] = $enum->label();
			$class = get_class($enum);
		}

		$this->backedEnum = $class;
		return parent::setItems($items, $useKeys);
	}


	/**
	 * @throws InvalidArgumentException
	 */
	public function setValue(/*?LabeledEnum*/ $value): self
	{
		if ($value && isset($this->backedEnum)) {
			$value = $this->backedEnum::tryMake($value) ?: $value;
		}

		if ($value && !$value instanceof LabeledEnum) {
			throw new InvalidArgumentException('Enum has to implement '.LabeledEnum::class);
		}

		if ($value && !$value instanceof $this->backedEnum) {
			throw new InvalidArgumentException('Enum does not match items of type '.$this->backedEnum);
		}

		return parent::setValue($value?->value ?? null);
	}


	public function getValue(): ?LabeledEnum
	{
		return $this->backedEnum::tryMake($this->value);
	}


	public function setDisabled(/*bool|array*/ $value = true)//: self
	{
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $item->value;
			}
		}

		return parent::setDisabled($value);
	}
}
