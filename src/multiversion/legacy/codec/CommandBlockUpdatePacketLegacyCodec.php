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
 * Port persis dari CommandBlockUpdatePacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Serverbound-only.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CommandBlockUpdatePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : CommandBlockUpdatePacket{
		$packet = new CommandBlockUpdatePacket();
		$packet->isBlock = CommonTypes::getBool($in);

		if($packet->isBlock){
			$packet->blockPosition = LegacyBlockPosition::read($in);
			$packet->commandBlockMode = VarInt::readUnsignedInt($in);
			$packet->isRedstoneMode = CommonTypes::getBool($in);
			$packet->isConditional = CommonTypes::getBool($in);
		}else{
			$packet->minecartActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		}

		$packet->command = CommonTypes::getString($in);
		$packet->lastOutput = CommonTypes::getString($in);
		$packet->name = CommonTypes::getString($in);
		$packet->filteredName = CommonTypes::getString($in);
		$packet->shouldTrackOutput = CommonTypes::getBool($in);
		$packet->tickDelay = LE::readUnsignedInt($in);
		$packet->executeOnFirstTick = CommonTypes::getBool($in);

		return $packet;
	}
}
