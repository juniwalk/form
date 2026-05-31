<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Html;
use Nette\Forms\Controls\SelectBox;
use InvalidArgumentException;
use ValueError;

/**
 * @template T of LabeledEnum
 */
final class SelectBoxEnum extends SelectBox
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
	 * @param  array<T> $enums
	 * @throws InvalidArgumentException
	 */
	public function setItems(array $enums, bool $useKeys = true, bool $badge = false): self
	{
		$items = [];

		foreach ($enums as $enum) {
			if (!$enum instanceof $this->enumType) {
				throw new InvalidArgumentException('Enum does not match items of type '.$this->enumType);
			}

			$option = Html::optionEnum($enum, $badge);

			if (method_exists($enum, 'group') && $group = $enum->group()) {	// @phpstan-ignore function.alreadyNarrowedType (method_exists for BC compatibility with older versions of LabeledEnum)
				$items[$group][$enum->value] = $option;
			} else {
				$items[$enum->value] = $option;
			}
		}

		return parent::setItems($items, $useKeys);
	}


	/**
	 * @param  int|string|null|T $value
	 * @throws ValueError
	 */
	public function setValue(mixed $value): self
	{
		if (isset($value) && !$value instanceof $this->enumType) {
			$value = $this->enumType::make($value, $value !== '');
		}

		return parent::setValue($value?->value ?? null);	// @phpstan-ignore nullsafe.neverNull (It can be null?)
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
			$value = array_map(static fn($x) => $x->value, $value);
		}

		return parent::setDisabled($value);
	}


	public function isDisabled(mixed $key = null): bool
	{
		if (!$key || !is_array($this->disabled)) {
			return parent::isDisabled();
		}

		return $this->disabled[$key] ?? false;		// @phpstan-ignore-line
	}
}
