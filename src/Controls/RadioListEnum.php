<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use Nette\Forms\Controls\RadioList;
use InvalidArgumentException;
use ValueError;

/**
 * @template T of LabeledEnum
 */
final class RadioListEnum extends RadioList
{
	/** @var class-string<T> */
	private string $enumType;


	/**
	 * @param  class-string<T> $enumType
	 * @throws InvalidArgumentException
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
	 * @throws ValueError
	 */
	public function setValue(mixed $value): self
	{
		if (isset($value) && !$value instanceof $this->enumType) {
			$value = $this->enumType::make($value, $value !== '');
		}

		return parent::setValue($value?->value ?? null);
	}


	/**
	 * @return T|null
	 */
	public function getValue(): ?LabeledEnum
	{
		return $this->enumType::make($this->value, false);
	}


	/**
	 * @param bool|array<T> $value
	 */
	public function setDisabled(array|bool $value = true): static
	{
		if (is_array($value)) {
			$value = Arrays::map($value, fn($x) => $x->value);
		}

		return parent::setDisabled($value);
	}


	public function isDisabled(mixed $key = null): bool
	{
		$key = $this->enumType::make($key, false);

		if (!$key || !is_array($this->disabled)) {		// @phpstan-ignore-line
			return parent::isDisabled();
		}

		return $this->disabled[$key->value] ?? false;	// @phpstan-ignore-line
	}


	public function isActive(mixed $key = null): bool
	{
		$key = $this->enumType::make($key, false);

		if (!$key || !is_scalar($this->value)) {
			return false;
		}

		return $key->value === $this->value;
	}
}
