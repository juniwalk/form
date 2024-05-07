<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use Nette\Forms\Controls\CheckboxList;
use InvalidArgumentException;
use ValueError;

/**
 * @template T of LabeledEnum
 */
final class CheckboxListEnum extends CheckboxList
{
	/** @var class-string<T> */
	private string $enumType;


	/**
	 * @param class-string<T> $enumType
	 */
	public function setEnumType(string $enumType): static
	{
		if (!is_subclass_of($enumType, LabeledEnum::class)) {
			throw new InvalidArgumentException('Enum has to implement '.LabeledEnum::class);
		}

		$this->enumType = $enumType;
		return $this;
	}


	/**
	 * @return array<T>
	 */
	public function getCases(): array
	{
		/** @var array<T> */
		return Arrays::map($this->getItems(), fn($x, $y) => $this->enumType::make($y));
	}


	/**
	 * @param  array<T> $enums
	 * @throws InvalidArgumentException
	 */
	public function setItems(array $enums, bool $useKeys = true): self
	{
		$items = [];

		foreach ($enums as $enum) {
			if (!$enum instanceof $this->enumType) {
				throw new InvalidArgumentException('Enum does not match items of type '.$this->enumType);
			}

			$items[$enum->value] = $enum->label();
		}

		return parent::setItems($items, $useKeys);
	}


	/**
	 * @param  array<T> $values
	 * @throws ValueError
	 */
	public function setValue(mixed $values): self
	{
		if (!is_iterable($values)) {
			return parent::setValue(null);
		}

		/** @var array<T> */
		$values = Arrays::map($values, function(mixed $value) {
			if (isset($value) && !$value instanceof $this->enumType) {
				$value = $this->enumType::make($value, $value !== '');
			}

			return $value?->value;
		});

		return parent::setValue($values);
	}


	/**
	 * @return array<T>
	 * @throws ValueError
	 */
	public function getValue(): array
	{
		if (!is_iterable($this->value)) {
			return [];
		}

		/** @var array<T> */
		return Arrays::map($this->value, fn($x, $y) => $this->enumType::make($y, false));
	}


	/**
	 * @param bool|array<T> $value
	 */
	public function setDisabled(array|bool $value = true): static
	{
		if (is_array($value)) {
			$value = Arrays::map($value, fn($item) => $item->value);
		}

		return parent::setDisabled($value);
	}


	public function isDisabled(mixed $key = null): bool
	{
		if (!$key || !is_array($this->disabled)) {	// @phpstan-ignore-line
			return parent::isDisabled();
		}

		return $this->disabled[$key] ?? false;		// @phpstan-ignore-line
	}
}
