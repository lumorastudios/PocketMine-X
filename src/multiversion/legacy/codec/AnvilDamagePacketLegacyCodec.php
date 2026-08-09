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
 * Port persis dari AnvilDamagePacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Serverbound-only.
 *
 * CATATAN (update 1.26.40): field damageAmount sudah DIHAPUS TOTAL dari
 * AnvilDamagePacket modern (AnvilDamagePacket::create() sekarang cuma
 * menerima blockPosition). Byte damageAmount tetap dibaca dari buffer client
 * lama supaya posisi baca tidak geser, tapi nilainya sengaja dibuang.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\multiversion\legacy\LegacyBlockPosition;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;

final class AnvilDamagePacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : AnvilDamagePacket{
		Byte::readUnsigned($in); //damageAmount - no longer exists on the modern packet, discarded
		$blockPosition = LegacyBlockPosition::read($in);
		return AnvilDamagePacket::create($blockPosition);
	}
}
