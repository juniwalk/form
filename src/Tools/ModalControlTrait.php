<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Form\Tools;

use JuniWalk\Utils\UI\Modal\Presenter;

/**
 * @deprecated
 */
trait ModalControlTrait
{
	use Presenter\NajaAjaxRedirectTrait;
	use Presenter\ModalControlTrait;
}
