<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Enums\LabeledEnum;
use Nette\Forms\Controls\SelectBox;
use InvalidArgumentException;

final class SelectBoxEnum extends SelectBox
{
	/** @var string */
	private $backedEnum;


	/**
	 * @param  iterable  $items
	 * @param  bool  $useKeys
	 * @return static
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
	 * @param  LabeledEnum|null  $value
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function setValue($value): self
	{
		if ($value && !$value instanceof LabeledEnum) {
			throw new InvalidArgumentException('Enum has to implement '.LabeledEnum::class);
		}

		if ($value && !$value instanceof $this->backedEnum) {
			throw new InvalidArgumentException('Enum does not match items of type '.$this->backedEnum);
		}

		return parent::setValue($value?->value ?? null);
	}


	/**
	 * @return LabeledEnum|null
	 */
	public function getValue(): ?LabeledEnum
	{
		return $this->backedEnum::tryMake($this->value);
	}


	/**
	 * Disables or enables control or items.
	 * @param  bool|array  $value
	 * @return static
	 */
	public function setDisabled($value = true)
	{
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $item->value;
			}
		}

		return parent::setDisabled($value);
	}
}
