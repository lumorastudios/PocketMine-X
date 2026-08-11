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

namespace pocketmine\network\mcpe\convert;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function microtime;

/**
 * Forces TypeConverter::getInstance() (and therefore BlockStateDictionary/ItemTypeDictionary construction) AND
 * ChunkSerializer::serializeFullChunk() to run once inside a fresh async worker thread as soon as that worker
 * starts, instead of on-demand the first time a player requests a chunk. Both are measurably much slower (in the
 * ~1-2 second range each) the first time they run in a brand new worker thread than on an already-warmed thread,
 * likely due to per-thread JIT warm-up - each hot code path needs its own warm-up, not just object construction.
 * Without this, the first player to request chunks from a freshly-started worker can time out waiting for chunk
 * data and get disconnected before the first chunk is ever produced.
 */
final class TypeConverterWarmupTask extends AsyncTask{

	public function onRun() : void{
		\GlobalLogger::get()->debug("TypeConverterWarmupTask: starting warmup at " . microtime(true));
		$typeConverter = TypeConverter::getInstance();
		\GlobalLogger::get()->debug("TypeConverterWarmupTask: TypeConverter warm at " . microtime(true));

		//use a throwaway chunk with one solid subchunk (not just default empty/air) so the block palette encoding
		//loop inside serializeSubChunk() actually runs at least once during warmup, not just the biome loop.
		$stoneStateId = VanillaBlocks::STONE()->getStateId();
		$warmupSubChunk = new SubChunk(Block::EMPTY_STATE_ID, [new PalettedBlockArray($stoneStateId)], new PalettedBlockArray(BiomeIds::OCEAN));
		$dummyChunk = new Chunk([0 => $warmupSubChunk], true);
		ChunkSerializer::serializeFullChunk($dummyChunk, DimensionIds::OVERWORLD, $typeConverter->getBlockTranslator());
		\GlobalLogger::get()->debug("TypeConverterWarmupTask: warmup complete at " . microtime(true));
	}
}
