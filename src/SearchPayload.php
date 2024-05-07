<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Form;

use JsonSerializable;
use JuniWalk\ORM\Entity\Interfaces\HtmlOption;
use JuniWalk\Utils\Arrays;
use JuniWalk\Utils\Html;
use JuniWalk\Utils\Strings;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Throwable;

/**
 * @phpstan-type Result array{
 * 		id: int|string,
 * 		text: string,
 * 		content?: string,
 * 		group?: string,
 * 		icon?: string,
 * 		color?: string,
 * 		disabled: bool,
 * }
 * @phpstan-type Group array{text: string, childen: Result[]}
 */
class SearchPayload implements JsonSerializable
{
	private bool $isGroupsAllowed = true;
	private ?int $maxResults = null;
	private int $page;

	private Processor $processor;
	private Structure $schema;

	/** @var array<int|string, mixed> */
	private array $items = [];

	public function __construct(?int $page = null, ?int $maxResults = null)
	{
		$this->maxResults = $maxResults;
		$this->page = $page ?? 1;

		$this->processor = new Processor;
		$this->schema = Expect::structure([
			'id'		=> Expect::anyOf(Expect::int(), Expect::string()),
			'text'		=> Expect::string(),
			'content'	=> Expect::string()->nullable(),
			'group'		=> Expect::string()->nullable(),
			'icon'		=> Expect::string()->nullable(),
			'color'		=> Expect::string()->nullable(),
			'disabled'	=> Expect::bool(),
		]);

		$this->schema->skipDefaults(true);
		$this->schema->castTo('array');
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


	public function setGroupsAllowed(bool $groupsAllowed): void
	{
		$this->isGroupsAllowed = $groupsAllowed;
	}


	public function isGroupsAllowed(): bool
	{
		return $this->isGroupsAllowed;
	}


	/**
	 * @param array<mixed[]> $items
	 */
	public function addItems(iterable $items): static
	{
		foreach ($items as $value) {
			$this->addItem($value);
		}

		return $this;
	}


	/**
	 * @param Html|HtmlOption|array<string, mixed> $item
	 */
	public function addItem(Html|HtmlOption|array $item): void
	{
		$item = $this->checkStructure($item);
		$key = $item['id'];

		if (!$group = $this->createGroup($item)) {
			$this->items[$key] = $item;
			return;
		}

		/** @var Group */
		$group = &$this->items[$group];
		$group['children'][$key] = $item;
	}


	/**
	 * @return array{results: array<int, Group|Result>, pagination: array{more: bool, page: int}}
	 */
	public function getPayload(): array
	{
		/** @var array<int, Group|Result> */
		$results = Arrays::mapRecursive($this->items, function(array $item): array {
			if (isset($item['children'])) {
				$item['children'] = array_values($item['children']);
			}

			return $item;
		});

		return [
			'results' => array_values($results),
			'pagination' => [
				'more' => $this->maxResults && sizeof($results) >= $this->maxResults,
				'page' => $this->page,
			],
		];
	}


	public function jsonSerialize(): mixed
	{
		return $this->getPayload();
	}


	/**
	 * @param Result $item
	 */
	protected function createGroup(array $item): ?string
	{
		if (!$name = $item['group'] ?? null) {
			return null;
		}

		$group = Strings::webalize($name);
		$this->items[$group] ??= [
			'text' => $name,
			'children' => [],
		];

		return $group;
	}


	/**
	 * @param  Html|HtmlOption|array<string, mixed> $item
	 * @return Result
	 */
	protected function checkStructure(Html|HtmlOption|array $item): array
	{
		if ($item instanceof HtmlOption) {
			$item = $item->createOption();
		}

		if ($item instanceof Html && $item->getName() == 'option') {
			$item = [
				'id' => $item->getValue(),
				'text' => $item->getText(),
				'content' => $item->getAttribute('data-content'),
				'group' => $item->getAttribute('data-group'),
				'icon' => $item->getAttribute('data-icon'),
				'color' => $item->getAttribute('data-color'),
				'disabled' => $item->getDisabled() ?? false,
			];
		}

		try {
			/** @var Result */
			$item = $this->processor->process($this->schema, $item);

		} catch (Throwable $e) {
			throw new \Exception;
		}

		if (!$this->isGroupsAllowed && $group = $item['group'] ?? null) {
			$item['text'] = $group.' - '.$item['text'];
			unset($item['group']);
		}

		return $item;
	}
}
