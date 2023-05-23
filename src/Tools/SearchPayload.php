<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form\Tools;

use JsonSerializable;
use JuniWalk\ORM\Interfaces\HtmlOption;
use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Html;
use JuniWalk\Utils\Strings;

class SearchPayload implements JsonSerializable
{
	private int $page;
	private ?int $maxResults = null;
	private bool $hasGroupAllowed = true;
	private array $items = [];

	public function __construct(int $page = null, int $maxResults = null)
	{
		$this->maxResults = $maxResults;
		$this->page = $page ?? 1;
	}


	public function setPage(int $page): void
	{
		$this->page = $page;
	}


	public function getPage(): int
	{
		return $this->page;
	}


	public function setMaxResults(?int $maxResults): void
	{
		$this->maxResults = $maxResults;
	}


	public function getMaxResults(): ?int
	{
		return $this->maxResults;
	}


	public function getFirstResult(): ?int
	{
		if (is_null($this->maxResults)) {
			return null;
		}

		return ($this->page -1) * $this->maxResults;
	}


	public function setGroupsAllowed(bool $groupsAllowed)
	{
		$this->hasGroupAllowed = $groupsAllowed;
	}


	public function hasGroupsAllowed(): bool
	{
		return $this->hasGroupAllowed;
	}


	public function addItems(iterable $items): static
	{
		foreach ($items as $value) {
			$this->addItem($value);
		}

		return $this;
	}


	public function addItem(mixed $item): void
	{
		$item = $this->checkStructure($item);
		$key = $item['id'];

		if (!$group = $this->createGroup($item)) {
			$this->items[$key] = $item;
			return;
		}

		$this->items[$group]['children'][$key] = $item;
	}


	public function getPayload(): array
	{
		$results = Arrays::map(
			items: $this->items,
			isRecursive: false,
			callback: function(array $item): array {
				if (isset($item['children'])) {
					$item['children'] = array_values($item['children']);
				}

				return $item;
			}
		);

		return [
			'results' => array_values($results),
			'pagination' => [
				'more' => $this->maxResults && !empty($results),
				'page' => $this->page,
			],
		];
	}


	public function jsonSerialize(): mixed
	{
		return $this->getPayload();
	}


	protected function createGroup(array $item): ?string
	{
		if (!$name = $item['group'] ?? null) {
			return null;
		}

		$key = Strings::webalize($name);

		if (isset($this->items[$key])) {
			return $key;
		}

		$this->items[$key] = [
			'text' => $name,
			'children' => [],
		];

		return $key;
	}


	protected function checkStructure(mixed $item): array
	{
		if ($item instanceof HtmlOption) {
			$item = $item->createOption();
		}

		if ($item instanceof Html && $item->getName() == 'option') {
			$item = [
				'id' => $item->getValue(),
				'text' => $item->getText(),
				'content' => $item->{'data-content'},
				'group' => $item->{'data-group'},
				'icon' => $item->{'data-icon'},
				'color' => $item->{'data-color'},
			];
		}

		// $item = Scheme::process($item, new Scheme);
		$group = $item['group'] ?? null;

		if (!$this->hasGroupAllowed && $group) {
			$item['text'] = $group.' - '.$item['text'];
			unset($item['group']);
		}

		return array_filter($item);
	}
}
