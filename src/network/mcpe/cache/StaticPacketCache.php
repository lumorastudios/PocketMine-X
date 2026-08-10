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

namespace pocketmine\network\mcpe\cache;

use pocketmine\color\Color;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use function count;
use function gzdecode;

class StaticPacketCache{
	use SingletonTrait;

	/**
	 * @phpstan-return CacheableNbt<\pocketmine\nbt\tag\CompoundTag>
	 */
	private static function loadCompoundFromFile(string $filePath) : CacheableNbt{
		$raw = Filesystem::fileGetContents($filePath);
		$decompressed = @gzdecode($raw);
		return new CacheableNbt((new BigEndianNbtSerializer())->read($decompressed !== false ? $decompressed : $raw)->mustGetCompoundTag());
	}

	/**
	 * bedrock-data 1.26.40+ ships biome_definitions as gzip-compressed big-endian NBT instead of JSON.
	 * The root compound has a shared "biomeStringList" string pool (used for both biome names and tag names)
	 * and a "biomeData" list of entries shaped like {index: short index into biomeStringList, data: compound
	 * of the actual biome properties}. "data.tags" is itself a single-key wrapper compound containing the
	 * real "tags" list (list of short indices into biomeStringList). "data.chunkGenData" (terrain feature
	 * placement rules) is intentionally not parsed here, since BiomeDefinitionEntry doesn't need it.
	 *
	 * @return list<BiomeDefinitionEntry>
	 */
	private static function loadBiomeDefinitionModel(string $filePath) : array{
		$raw = Filesystem::fileGetContents($filePath);
		$decompressed = @gzdecode($raw);
		$root = (new BigEndianNbtSerializer())->read($decompressed !== false ? $decompressed : $raw)->mustGetCompoundTag();

		$stringListTag = $root->getListTag("biomeStringList");
		$biomeDataListTag = $root->getListTag("biomeData");
		if($stringListTag === null || $biomeDataListTag === null){
			throw new SavedDataLoadingException("$filePath is missing the biomeStringList/biomeData tags");
		}

		$stringList = [];
		foreach($stringListTag as $stringTag){
			$stringList[] = (string) $stringTag->getValue();
		}

		$entries = [];
		foreach($biomeDataListTag as $biomeEntryTag){
			if(!($biomeEntryTag instanceof CompoundTag)){
				continue;
			}
			$biomeName = $stringList[$biomeEntryTag->getShort("index", -1)] ?? null;
			$data = $biomeEntryTag->getCompoundTag("data");
			if($biomeName === null || $data === null){
				continue;
			}

			$tags = null;
			$tagsWrapper = $data->getCompoundTag("tags");
			$tagsListTag = $tagsWrapper !== null ? $tagsWrapper->getListTag("tags") : null;
			if($tagsListTag !== null){
				$tagNames = [];
				foreach($tagsListTag as $tagIndexTag){
					$resolvedTag = $stringList[(int) $tagIndexTag->getValue()] ?? null;
					if($resolvedTag !== null){
						$tagNames[] = $resolvedTag;
					}
				}
				if(count($tagNames) > 0){
					$tags = $tagNames;
				}
			}

			$waterColorRaw = $data->getInt("mapWaterColorARGB", 0) & 0xFFFFFFFF;

			$entries[] = new BiomeDefinitionEntry(
				$biomeName,
				$data->getShort("id", -1),
				$data->getFloat("temperature", 0.0),
				$data->getFloat("downfall", 0.0),
				$data->getFloat("foliageSnow", 0.0),
				$data->getFloat("depth", 0.0),
				$data->getFloat("scale", 0.0),
				new Color(
					($waterColorRaw >> 16) & 0xFF,
					($waterColorRaw >> 8) & 0xFF,
					$waterColorRaw & 0xFF,
					($waterColorRaw >> 24) & 0xFF,
				),
				$data->getByte("rain", 0) !== 0,
				$tags,
			);
		}

		return $entries;
	}

	private static function make() : self{
		return new self(
			BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionModel(BedrockDataFiles::BIOME_DEFINITIONS_NBT)),
			AvailableActorIdentifiersPacket::create(self::loadCompoundFromFile(BedrockDataFiles::ENTITY_IDENTIFIERS_NBT))
		);
	}

	public function __construct(
		private BiomeDefinitionListPacket $biomeDefs,
		private AvailableActorIdentifiersPacket $availableActorIdentifiers
	){}

	public function getBiomeDefs() : BiomeDefinitionListPacket{
		return $this->biomeDefs;
	}

	public function getAvailableActorIdentifiers() : AvailableActorIdentifiersPacket{
		return $this->availableActorIdentifiers;
	}
}
