<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use Nette\Forms\Controls\CheckboxList;
use InvalidArgumentException;
use ValueError;

use function array_map;
use function in_array;
use function is_array;
use function is_iterable;
use function is_subclass_of;
use function iterator_to_array;

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
		$cases = [];

		foreach ($this->getItems() as $key => $label) {
			$cases[$key] = $this->enumType::make($key);
		}

		return $cases;
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
	 * @param  array<T|string|int> $values
	 * @throws ValueError
	 */
	public function setValue(mixed $values): self
	{
		if (!is_iterable($values)) {	// @phpstan-ignore function.alreadyNarrowedType
			return parent::setValue(null);
		}

		foreach ($values as $key => $value) {
			if (!$value instanceof $this->enumType) {
				$value = $this->enumType::make($value, $value !== '');
			}

			$values[$key] = $value?->value;
		}

		return parent::setValue($values);
	}


	/**
	 * @return array<T>
	 * @throws ValueError
	 */
	public function getValue(): array	// @phpstan-ignore method.childReturnType
	{
		$values = [];

		if (!is_iterable($this->value)) {
			return $values;
		}

		foreach ($this->value as $key => $case) {
			$values[$key] = $this->enumType::make($case);
		}

		return $values;
	}


	/**
	 * @param bool|array<T> $value
	 */
	public function setDisabled(array|bool $value = true): static
	{
		if (is_array($value)) {
			$value = array_map(static fn($x) => $x->value, $value);
		}

		return parent::setDisabled($value);
	}


	public function isDisabled(mixed $key = null): bool
	{
		$enum = $this->enumType::make($key, false);

		if (!$enum || !is_array($this->disabled)) {
			return parent::isDisabled();
		}

		return $this->disabled[$enum->value] ?? false;
	}


	public function isActive(mixed $key = null): bool
	{
		$enum = $this->enumType::make($key, false);

		if (!$enum || !is_iterable($this->value)) {
			return false;
		}

		$values = iterator_to_array($this->value);
		return in_array($enum->value, $values, true);
	}


	public function getColor(mixed $key, bool $outline = true): string
	{
		$enum = $this->enumType::make($key);

		if ($outline && $this->isDisabled($enum) && $this->isActive($enum)) {
			$outline = false;
		}

		return $enum->color()->for(
			$outline ? 'btn-outline' : 'btn'
		);
	}


	/**
	 * @return string[]
	 */
	public function getClassList(mixed $key): array
	{
		$list = [];
		$list[] = $this->getColor($key);

		if ($this->isDisabled($key)) {
			$list[] = 'disabled';
		}

		if ($this->isActive($key)) {
			$list[] = 'active';
		}

		return $list;
	}
}
