<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Form\DI;

use Contributte\Translation\Translator;
use JuniWalk\Form\Controls;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Country;
use Nette\Forms\Container as Form;
use Nette\Forms\Control;
use Throwable;

final class ControlFactory
{
	public static function registerControls(): void
	{
		static $methods = [
			'addDateTime',
			'addPhoneNumber',
			'addSelectCountry',
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
		?string $label = null,
	): Control {
		return $form[$name] = new Controls\DateTimePicker($label);
	}


	public static function addPhoneNumber(
		Form $form,
		string $name,
		?string $label = null,
		?int $cols = null,
		?int $maxLength = null,
	): Control {
		return $form[$name] = (new Controls\PhoneNumber($label, $maxLength))
			->setHtmlAttribute('size', $cols);
	}


	/**
	 * @param array<int|string, mixed> $items
	 */
	public static function addSelectCountry(
		Form $form,
		string $name,
		array $items = [],
		?string $lang = null,
	): Control {
		$translator = $form->getForm()->getTranslator();
		$select = $form->addSelect($name);

		if ($translator instanceof Translator) {
			$lang ??= $translator->getLocale();
		}

		try {
			if (!$items && class_exists(Country::class)) {
				/** @var array<int|string, mixed> */
				$items = Country::getList($lang ?? 'cs');
				$select->setTranslator(null);
			}

		} catch (Throwable) {
			$items = [];
		}

		return $select->setItems($items);
	}


	/**
	 * @param class-string<LabeledEnum> $enumType
	 */
	public static function addSelectEnum(
		Form $form,
		string $name,
		string $enumType,
		bool $badge = false,
	): Control {
		return $form[$name] = (new Controls\SelectBoxEnum)->setEnumType($enumType)
			->setItems($enumType::cases(), badge: $badge);
	}


	/**
	 * @param class-string<LabeledEnum> $enumType
	 */
	public static function addRadioEnum(
		Form $form,
		string $name,
		string $enumType,
	): Control {
		return $form[$name] = (new Controls\RadioListEnum)->setEnumType($enumType)
			->setItems($enumType::cases());
	}


	/**
	 * @param class-string<LabeledEnum> $enumType
	 */
	public static function addCheckboxEnum(
		Form $form,
		string $name,
		string $enumType,
	): Control {
		return $form[$name] = (new Controls\CheckboxListEnum)->setEnumType($enumType)
			->setItems($enumType::cases());
	}
}
