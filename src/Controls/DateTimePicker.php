<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use Nette\Utils\DateTime;

final class DateTimePicker extends \Nette\Forms\Controls\TextBase
{
	/** @var string */
	private $format = 'Y-m-d H:i:s';


	/**
	 * @param  string  $format
	 * @return static
	 */
	public function setFormat($format)
	{
		$this->format = $format;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getFormat()
	{
		return $this->format;
	}


	/**
	 * @return Nette\Utils\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$value = $this->getValue();

		if ($value instanceof \DateTime) {
			$control->value = $value->format($this->format);
		}

		return $control;
	}


	/**
	 * @param  DateTime|string|NULL  $value
	 * @return static
	 */
	public function setValue($value = NULL)
	{
		if ($value instanceof \DateTime) {
			$value = $value->format($this->format);
		}

		return parent::setValue($value);
	}


	/**
	 * @return DateTime|NULL
	 */
	public function getValue()
	{
		if (!$value = $this->value) {
			return NULL;
		}

		if ($value instanceof \DateTime || is_int($value)) {
			return DateTime::from($value);
		}

		try {
			return DateTime::createFromFormat($this->format, $this->value);

		} catch (\Exception $e) { }

		if ($time = strtotime($value)) {
			return DateTime::from($value);
		}

		return NULL;
	}
}
