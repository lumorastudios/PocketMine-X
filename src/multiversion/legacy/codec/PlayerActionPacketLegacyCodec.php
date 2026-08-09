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
 * Port persis dari PlayerActionPacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Struktur field & urutannya IDENTIK dengan versi 58.0.0 (1.26.30) yang
 * dipakai server ini - satu-satunya beda adalah cara encode BlockPosition
 * (lihat LegacyBlockPosition). actorRuntimeId, action, dan face TIDAK
 * berubah, jadi tetap memakai CommonTypes bawaan vendor.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class PlayerActionPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(PlayerActionPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorRuntimeId($out, $packet->actorRuntimeId);
		VarInt::writeSignedInt($out, $packet->action);
		LegacyBlockPosition::write($out, $packet->blockPosition);
		LegacyBlockPosition::write($out, $packet->resultPosition);
		VarInt::writeSignedInt($out, $packet->face);

		return $out->getData();
	}

	/**
	 * @param ByteBufferReader $in reader yang sudah dilewati header packet (payload saja)
	 */
	public static function decodePayload(ByteBufferReader $in) : PlayerActionPacket{
		$packet = new PlayerActionPacket();
		$packet->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$packet->action = VarInt::readSignedInt($in);
		$packet->blockPosition = LegacyBlockPosition::read($in);
		$packet->resultPosition = LegacyBlockPosition::read($in);
		$packet->face = VarInt::readSignedInt($in);
		return $packet;
	}
}
