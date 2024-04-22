<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Form\Attributes;

use Attribute;
use JuniWalk\Form\Enums\Layout;

#[Attribute(Attribute::TARGET_CLASS)]
class PreventLeavingWhenDirty
{
	/** @var Layout[] */
	private array $layouts;

	public function __construct(Layout ...$layouts)
	{
		$this->layouts = $layouts ?: Layout::cases();
	}


	public function for(Layout $layout): bool
	{
		return in_array($layout, $this->layouts, true);
	}
}
