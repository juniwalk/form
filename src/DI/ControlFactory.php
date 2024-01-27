<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use JuniWalk\Form\Controls;
use Nette\Forms\Container as Form;

final class ControlFactory
{
	public static function registerControls(): void
	{
		static $methods = [
			'addDateTime',
			'addPhoneNumber',
			'addSelectEnum',
			'addRadioEnum',
			'addCheckboxEnum',
		];

		foreach ($methods as $method) {
			if (method_exists(Form::class, $method)) {
				continue;
			}

			Form::extensionMethod($method, static::$method(...));
		}
	}


	public static function addDateTime(
		Form $form,
		string $name,
		string $label = null,
	) {
		return $form[$name] = new Controls\DateTimePicker($label);
	}


	public static function addPhoneNumber(
		Form $form,
		string $name,
		string $label = null,
		?int $cols = null,
		?int $maxLength = null,
	) {
		return $form[$name] = (new Controls\PhoneNumber($label, $maxLength))
			->setHtmlAttribute('size', $cols);
	}


	public static function addSelectEnum(
		Form $form,
		string $name,
		string $enumType,
		bool $badge = false,
	) {
		return $form[$name] = (new Controls\SelectBoxEnum)->setEnumType($enumType)
			->setItems($enumType::cases(), badge: $badge);
	}


	public static function addRadioEnum(
		Form $form,
		string $name,
		string $enumType,
	) {
		return $form[$name] = (new Controls\RadioListEnum)->setEnumType($enumType)
			->setItems($enumType::cases());
	}


	public static function addCheckboxEnum(
		Form $form,
		string $name,
		string $enumType,
	) {
		return $form[$name] = (new Controls\CheckboxListEnum)->setEnumType($enumType)
			->setItems($enumType::cases());
	}
}
