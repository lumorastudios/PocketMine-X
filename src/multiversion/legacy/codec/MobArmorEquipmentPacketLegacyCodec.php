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
 * Port persis dari MobArmorEquipmentPacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Bidirectional.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\multiversion\legacy\LegacyItemStackWrapper;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class MobArmorEquipmentPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(MobArmorEquipmentPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorRuntimeId($out, $packet->actorRuntimeId);
		LegacyItemStackWrapper::write($out, $packet->head);
		LegacyItemStackWrapper::write($out, $packet->chest);
		LegacyItemStackWrapper::write($out, $packet->legs);
		LegacyItemStackWrapper::write($out, $packet->feet);
		LegacyItemStackWrapper::write($out, $packet->body);

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : MobArmorEquipmentPacket{
		$packet = new MobArmorEquipmentPacket();
		$packet->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$packet->head = LegacyItemStackWrapper::read($in);
		$packet->chest = LegacyItemStackWrapper::read($in);
		$packet->legs = LegacyItemStackWrapper::read($in);
		$packet->feet = LegacyItemStackWrapper::read($in);
		$packet->body = LegacyItemStackWrapper::read($in);
		return $packet;
	}
}
