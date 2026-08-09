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
 * Port persis dari UpdateSubChunkBlocksPacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Clientbound-only.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\multiversion\legacy\LegacyUpdateSubChunkBlocksPacketEntry;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;
use function count;

final class UpdateSubChunkBlocksPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(UpdateSubChunkBlocksPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		LegacyBlockPosition::write($out, $packet->getBaseBlockPosition());

		VarInt::writeUnsignedInt($out, count($packet->getLayer0Updates()));
		foreach($packet->getLayer0Updates() as $update){
			LegacyUpdateSubChunkBlocksPacketEntry::write($out, $update);
		}

		VarInt::writeUnsignedInt($out, count($packet->getLayer1Updates()));
		foreach($packet->getLayer1Updates() as $update){
			LegacyUpdateSubChunkBlocksPacketEntry::write($out, $update);
		}

		return $out->getData();
	}
}
