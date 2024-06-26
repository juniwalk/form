<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use DateTimeInterface;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Throwable;

final class DateTimePicker extends TextBase
{
	private string $format = 'Y-m-d H:i:s';


	public function setFormat(string $format): static
	{
		$this->format = $format;
		return $this;
	}


	public function getFormat(): string
	{
		return $this->format;
	}


	public function getControl(): Html
	{
		$control = parent::getControl();
		$value = $this->getValue();

		if ($value instanceof DateTimeInterface) {
			$control->value = $value->format($this->format);
		}

		return $control;
	}


	/**
	 * @param int|string|null|DateTimeInterface $value
	 */
	public function setValue(mixed $value = null): static
	{
		if ($value instanceof DateTimeInterface) {
			$value = $value->format($this->format);
		}

		return parent::setValue($value);
	}


	public function getValue(): ?DateTime
	{
		if (!$value = $this->value) {
			return null;
		}

		/** @var int|string|DateTimeInterface $value */
		if ($value instanceof DateTimeInterface || is_int($value)) {
			return DateTime::from($value);
		}

		try {
			$date = DateTime::createFromFormat($this->format, $value);

			if ($date instanceof DateTime) {
				return $date;
			}

		} catch (Throwable) {
		}

		if ($time = strtotime($value)) {
			return DateTime::from($time);
		}

		return null;
	}
}
