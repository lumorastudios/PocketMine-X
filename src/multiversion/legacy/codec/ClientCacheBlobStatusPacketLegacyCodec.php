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
 * Port persis dari ClientCacheBlobStatusPacket versi bedrock-protocol 55.0.0
 * (1.26.0). Serverbound-only. Urutan baca berbeda: versi lama baca KEDUA
 * jumlah (miss lalu hit) DULU baru semua hash-nya; versi baru baca per-bagian
 * (miss count + miss hashes, baru hit count + hit hashes).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientCacheBlobStatusPacket;

final class ClientCacheBlobStatusPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : ClientCacheBlobStatusPacket{
		$missCount = VarInt::readUnsignedInt($in);
		$hitCount = VarInt::readUnsignedInt($in);

		$missHashes = [];
		for($i = 0; $i < $missCount; ++$i){
			$missHashes[] = LE::readUnsignedLong($in);
		}

		$hitHashes = [];
		for($i = 0; $i < $hitCount; ++$i){
			$hitHashes[] = LE::readUnsignedLong($in);
		}

		return ClientCacheBlobStatusPacket::create($hitHashes, $missHashes);
	}
}
