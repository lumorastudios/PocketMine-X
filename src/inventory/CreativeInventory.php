<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\crafting\json\ItemStackData;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\item\Item;
use pocketmine\lang\Translatable;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\DestructorCallbackTrait;
use pocketmine\utils\Filesystem;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\SingletonTrait;
use function array_map;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;

final class CreativeInventory{
	use SingletonTrait;
	use DestructorCallbackTrait;

	/**
	 * @var CreativeInventoryEntry[]
	 * @phpstan-var array<int, CreativeInventoryEntry>
	 */
	private array $creative = [];

	/** @phpstan-var ObjectSet<\Closure() : void> */
	private ObjectSet $contentChangedCallbacks;

	private function __construct(){
		$this->contentChangedCallbacks = new ObjectSet();

		$categoryByNumericId = [
			1 => CreativeCategory::CONSTRUCTION,
			2 => CreativeCategory::NATURE,
			3 => CreativeCategory::EQUIPMENT,
			4 => CreativeCategory::ITEMS,
		];

		$raw = Filesystem::fileGetContents(BedrockDataFiles::CREATIVE_ITEMS_JSON);
		$table = json_decode($raw, true);
		if(!is_array($table) || !isset($table["groups"]) || !is_array($table["groups"]) || !isset($table["items"]) || !is_array($table["items"])){
			throw new AssumptionFailedError("Invalid creative_items.json format");
		}

		/** @var array<int, array{0: CreativeCategory, 1: ?CreativeGroup}> $groups */
		$groups = [];
		/** @phpstan-var array<int, mixed> $groupsRaw */
		$groupsRaw = $table["groups"];
		foreach($groupsRaw as $index => $groupData){
			if(!is_array($groupData) || !isset($groupData["creative_category"]) || !is_int($groupData["creative_category"])){
				continue;
			}
			$categoryEnum = $categoryByNumericId[$groupData["creative_category"]] ?? CreativeCategory::ITEMS;

			$group = null;
			$groupName = $groupData["name"] ?? null;
			$icon = $groupData["icon"] ?? null;
			if(is_string($groupName) && $groupName !== "" && is_array($icon) && isset($icon["id"]) && is_string($icon["id"])){
				$iconItem = CraftingManagerFromDataHelper::deserializeItemStack(new ItemStackData($icon["id"]));
				if($iconItem !== null){
					$group = new CreativeGroup(new Translatable($groupName), $iconItem);
				}
			}
			$groups[$index] = [$categoryEnum, $group];
		}

		foreach($table["items"] as $itemData){
			if(!is_array($itemData) || !isset($itemData["id"]) || !is_string($itemData["id"])){
				continue;
			}
			$groupIndexRaw = $itemData["group_index"] ?? -1;
			$groupIndex = is_int($groupIndexRaw) ? $groupIndexRaw : -1;
			[$categoryEnum, $group] = $groups[$groupIndex] ?? [CreativeCategory::ITEMS, null];

			$item = CraftingManagerFromDataHelper::deserializeItemStack(new ItemStackData($itemData["id"]));
			if($item === null){
				continue;
			}
			$this->add($item, $categoryEnum, $group);
		}
	}

	/**
	 * Removes all previously added items from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function clear() : void{
		$this->creative = [];
		$this->onContentChange();
	}

	/**
	 * @return Item[]
	 * @phpstan-return array<int, Item>
	 */
	public function getAll() : array{
		return array_map(fn(CreativeInventoryEntry $entry) => $entry->getItem(), $this->creative);
	}

	/**
	 * @return CreativeInventoryEntry[]
	 * @phpstan-return array<int, CreativeInventoryEntry>
	 */
	public function getAllEntries() : array{
		return $this->creative;
	}

	public function getItem(int $index) : ?Item{
		return $this->getEntry($index)?->getItem();
	}

	public function getEntry(int $index) : ?CreativeInventoryEntry{
		return $this->creative[$index] ?? null;
	}

	public function getItemIndex(Item $item) : int{
		foreach($this->creative as $i => $d){
			if($d->matchesItem($item)){
				return $i;
			}
		}

		return -1;
	}

	/**
	 * Adds an item to the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function add(Item $item, CreativeCategory $category = CreativeCategory::ITEMS, ?CreativeGroup $group = null) : void{
		$this->creative[] = new CreativeInventoryEntry($item, $category, $group);
		$this->onContentChange();
	}

	/**
	 * Removes an item from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function remove(Item $item) : void{
		$index = $this->getItemIndex($item);
		if($index !== -1){
			unset($this->creative[$index]);
			$this->onContentChange();
		}
	}

	public function contains(Item $item) : bool{
		return $this->getItemIndex($item) !== -1;
	}

	/** @phpstan-return ObjectSet<\Closure() : void> */
	public function getContentChangedCallbacks() : ObjectSet{
		return $this->contentChangedCallbacks;
	}

	private function onContentChange() : void{
		foreach($this->contentChangedCallbacks as $callback){
			$callback();
		}
	}
}
