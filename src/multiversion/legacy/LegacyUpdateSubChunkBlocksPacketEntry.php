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

/*
 * MultiVersion support for PocketMine-MP 5.44.3
 * Port persis dari UpdateSubChunkBlocksPacketEntry::write() versi 1.26.0.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\UpdateSubChunkBlocksPacketEntry;

final class LegacyUpdateSubChunkBlocksPacketEntry{

	private function __construct(){
		//NOOP
	}

	public static function write(ByteBufferWriter $out, UpdateSubChunkBlocksPacketEntry $entry) : void{
		LegacyBlockPosition::write($out, $entry->getBlockPosition());
		VarInt::writeUnsignedInt($out, $entry->getBlockRuntimeId());
		VarInt::writeUnsignedInt($out, $entry->getFlags());
		VarInt::writeUnsignedLong($out, $entry->getSyncedUpdateActorUniqueId());
		VarInt::writeUnsignedInt($out, $entry->getSyncedUpdateType());
	}
}
