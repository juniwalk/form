<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Enums\LabeledEnum;
use JuniWalk\Utils\Arrays;
use Nette\Forms\Controls\CheckboxList;
use InvalidArgumentException;

final class CheckboxListEnum extends CheckboxList
{
	private string $backedEnum;


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
	public function setValue(/*?LabeledEnum*/ $values): self
	{
		if (!is_iterable($values)) {
			return parent::setValue(null);
		}

		$values = Arrays::map($values, function($value) {
			if ($value && isset($this->backedEnum)) {
				$value = $this->backedEnum::tryMake($value) ?: $value;
			}
	
			if ($value && !$value instanceof LabeledEnum) {
				throw new InvalidArgumentException('Enum has to implement '.LabeledEnum::class);
			}
	
			if ($value && !$value instanceof $this->backedEnum) {
				throw new InvalidArgumentException('Enum does not match items of type '.$this->backedEnum);
			}

			return $value?->value;
		});

		return parent::setValue($values);
	}


	public function getValue(): array
	{
		$values = Arrays::map($this->value, function($value, $key) {
			return $this->backedEnum::tryMake($value);
		});

		return parent::getValue($values);
	}


	public function setDisabled(/*bool|array*/ $value = true)//: self
	{
		if (is_bool($value)) {
			return parent::setDisabled($value);
		}

		foreach ($value as $key => $item) {
			$value[$key] = $item->value;
		}

		return parent::setDisabled($value);
	}
}
