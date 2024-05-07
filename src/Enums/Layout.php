<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Enums;

use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Enums\Traits\Labeled;

enum Layout: string implements LabeledEnum
{
	use Labeled;

	case Accordion = 'accordion';
	case Card = 'card';
	case Modal = 'modal';


	public function label(): string
	{
		return $this->value;
	}


	public function path(string $dir): string
	{
		return sprintf('%s/templates/@layout-%s.latte', $dir, $this->value);
	}
}
