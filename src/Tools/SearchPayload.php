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
	private array $items = [];


	public function __construct(array $data)
	{
		foreach ($data as $key => $value) {
			$this->addItem($value);
		}
	}


	public function addItem(mixed $item): void
	{
		$item = $this->checkStructure($item);
		$group = $item['group'] ?? null;
		$key = $item['id'];

		unset($item['group']);

		if (!isset($group)) {
			$this->items[$key] = $item;
			return;
		}

		$name = $this->createGroup($group);
		$this->items[$name]['children'][$key] = $item;
	}


	public function jsonSerialize(): mixed
	{
		return $this->getPayload();
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
				'more' => !empty($results),
			],
		];
	}


	protected function createGroup(string $name): string
	{
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

		// return Scheme::process($item, new Scheme);
		return array_filter($item);
	}
}
