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
 *
 * Port persis dari LevelSoundEventPacket versi bedrock-protocol 55.0.0
 * (1.26.0). Bidirectional. Beda dari 1.26.30:
 * - field `sound`: dulu int (VarInt, lihat LegacySoundMap), sekarang string
 * - field `firePosition` (optional Vector3) belum ada sama sekali di versi ini
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\multiversion\legacy\LegacySoundMap;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class LevelSoundEventPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(LevelSoundEventPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		VarInt::writeUnsignedInt($out, LegacySoundMap::newStringToOldId($packet->sound));
		CommonTypes::putVector3($out, $packet->position);
		VarInt::writeSignedInt($out, $packet->extraData);
		CommonTypes::putString($out, $packet->entityType);
		CommonTypes::putBool($out, $packet->isBabyMob);
		CommonTypes::putBool($out, $packet->disableRelativeVolume);
		LE::writeSignedLong($out, $packet->actorUniqueId);
		//firePosition sengaja tidak ditulis (belum ada di 1.26.0-1.26.20)

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : LevelSoundEventPacket{
		$packet = new LevelSoundEventPacket();
		$packet->sound = LegacySoundMap::oldIdToNewString(VarInt::readUnsignedInt($in));
		$packet->position = CommonTypes::getVector3($in);
		$packet->extraData = VarInt::readSignedInt($in);
		$packet->entityType = CommonTypes::getString($in);
		$packet->isBabyMob = CommonTypes::getBool($in);
		$packet->disableRelativeVolume = CommonTypes::getBool($in);
		$packet->actorUniqueId = LE::readSignedLong($in);
		$packet->firePosition = null;
		return $packet;
	}
}
