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
 * Port persis dari SubChunkRequestPacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Serverbound-only (client meminta subchunk tertentu ke server - PENTING untuk
 * loading dunia). Beda dari 1.26.30:
 * - urutan field: dulu basePosition DULU baru daftar entries, sekarang entries
 *   dulu baru basePosition
 * - jumlah entries dulu LE::readUnsignedInt (4 byte tetap), sekarang VarInt
 * - basePosition dulu pakai VarInt (SubChunkPosition::read()), sekarang pakai
 *   LE int tetap (SubChunkPosition::readFixedInts())
 *
 * Catatan: SubChunkPacket (kebalikannya, clientbound, berisi data chunk asli)
 * TERNYATA TIDAK BERUBAH FORMATNYA sama sekali walau nama fungsinya beda
 * (SubChunkPosition::readVarInts() persis sama dengan read() versi lama) -
 * jadi packet itu TIDAK butuh legacy codec.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\SubChunkRequestPacket;
use pocketmine\network\mcpe\protocol\types\SubChunkPosition;
use pocketmine\network\mcpe\protocol\types\SubChunkPositionOffset;

final class SubChunkRequestPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : SubChunkRequestPacket{
		$dimension = VarInt::readSignedInt($in);

		$x = VarInt::readSignedInt($in);
		$y = VarInt::readSignedInt($in);
		$z = VarInt::readSignedInt($in);
		$basePosition = new SubChunkPosition($x, $y, $z);

		$entries = [];
		for($i = 0, $count = LE::readUnsignedInt($in); $i < $count; $i++){
			$entries[] = SubChunkPositionOffset::read($in);
		}

		return SubChunkRequestPacket::create($dimension, $basePosition, $entries);
	}
}
