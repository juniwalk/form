<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Enums;

use JuniWalk\Utils\Enums\Color;
use JuniWalk\Utils\Enums\LabeledEnum;
use JuniWalk\Utils\Enums\Traits\Labeled;

enum Layout: string implements LabeledEnum
{
	use Labeled;

	case Card = 'card';
	case Modal = 'modal';


	public function label(): string
	{
		return $this->value;
	}


	public function color(): Color
	{
		return Color::Secondary;
	}
}
