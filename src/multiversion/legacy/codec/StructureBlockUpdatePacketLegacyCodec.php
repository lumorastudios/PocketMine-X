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
 * Port persis dari StructureBlockUpdatePacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Serverbound-only. StructureEditorData sudah dikonfirmasi tidak berubah
 * antara 1.26.0 dan 1.26.30, jadi tetap pakai CommonTypes bawaan vendor.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StructureBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;

final class StructureBlockUpdatePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : StructureBlockUpdatePacket{
		$blockPosition = LegacyBlockPosition::read($in);
		$structureEditorData = StructureEditorData::read($in);
		$isPowered = CommonTypes::getBool($in);
		$waterlogged = CommonTypes::getBool($in);
		return StructureBlockUpdatePacket::create($blockPosition, $structureEditorData, $isPowered, $waterlogged);
	}
}
