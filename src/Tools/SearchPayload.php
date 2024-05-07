<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Tools;

use JuniWalk\Form\SearchPayload as Replacement;

/**
 * @deprecated
 */
class SearchPayload extends Replacement
{
	public function __construct(?int $page = null, ?int $maxResults = null)
	{
		trigger_error(static::class.' is deprecated, use '.Replacement::class.' instead.', E_USER_DEPRECATED);
		parent::__construct($page, $maxResults);
	}
}
