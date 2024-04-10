<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Form\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PreventLeavingWhenDirty
{
}
