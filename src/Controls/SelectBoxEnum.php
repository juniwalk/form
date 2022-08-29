<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2016
 * @license   MIT License
 */

namespace JuniWalk\Form\Controls;

use BackedEnum;
use JuniWalk\Utils\Enums\LabelledEnum;
use Nette\Forms\Controls\SelectBox;

final class SelectBoxEnum extends SelectBox
{
	/** @var string */
	private $backedEnum;


	/**
	 * @param  iterable  $items
	 * @param  bool  $useKeys
	 * @return static
	 */
	public function setItems(array $items, bool $useKeys = true): self
	{
		$enums = [];

		foreach ($items as $item) {
			if (!$item instanceof LabelledEnum || !$item instanceof BackedEnum) {
				continue;
			}

			if ($this->backedEnum && !$item instanceof $this->backedEnum) {
				continue;
			}

			$enums[$item->value] = $item->label();
			$this->backedEnum = get_class($item);
		}

		return parent::setItems($enums, $useKeys);
	}


	/**
	 * @param  LabelledEnum|null  $item
	 * @return static
	 */
	public function setValue(LabelledEnum $item = null)
	{
		return parent::setValue($item->value);
	}


	/**
	 * @return LabelledEnum|null
	 */
	public function getValue(): ?LabelledEnum
	{
		return $this->backedEnum::tryFrom($this->value);
	}
}
