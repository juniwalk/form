<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use DateTimeInterface;
use Exception;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

final class DateTimePicker extends TextBase
{
	/** @var string */
	private $format = 'Y-m-d H:i:s';


	/**
	 * @param  string  $format
	 * @return static
	 */
	public function setFormat(string $format): self
	{
		$this->format = $format;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getFormat(): string
	{
		return $this->format;
	}


	/**
	 * @return Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$value = $this->getValue();

		if ($value instanceof DateTimeInterface) {
			$control->value = $value->format($this->format);
		}

		return $control;
	}


	/**
	 * @param  DateTimeInterface|string|null  $value
	 * @return static
	 */
	public function setValue($value = null)
	{
		if ($value instanceof DateTimeInterface) {
			$value = $value->format($this->format);
		}

		return parent::setValue($value);
	}


	/**
	 * @return DateTime|null
	 */
	public function getValue(): ?DateTime
	{
		if (!$value = $this->value) {
			return NULL;
		}

		if ($value instanceof DateTimeInterface || is_int($value)) {
			return DateTime::from($value);
		}

		try {
			return DateTime::createFromFormat($this->format, $this->value);

		} catch (Exception $e) { }

		if ($time = strtotime($value)) {
			return DateTime::from($value);
		}

		return null;
	}
}
