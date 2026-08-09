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
 * Port dari ClientMovementPredictionSyncPacket versi bedrock-protocol 55.0.0
 * (1.26.0). Serverbound-only. Versi 1.26.30 menambah 3 field float baru
 * (frictionModifier, bounciness, airDragModifier) di tengah struktur.
 *
 * Catatan: PM sendiri TIDAK memproses isi packet ini sama sekali (tidak ada
 * handler khusus di source PM), jadi nilai persis dari 3 field baru itu tidak
 * berpengaruh - kita isi dengan nilai vanilla default yang wajar supaya tetap
 * valid kalau suatu saat dibaca kode lain.
 *
 * BitSet flags: panjang bit berubah dari 127 ke 128 (EntityMetadataFlags::
 * NUMBER_OF_FLAGS bertambah 1), TAPI keduanya sama-sama dibulatkan ke 16 byte
 * (ceil(127/8) = ceil(128/8) = 16), jadi aman dibaca pakai BitSet::read()
 * bawaan vendor dengan panjang manapun - jumlah byte yang dikonsumsi identik.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ClientMovementPredictionSyncPacket;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ClientMovementPredictionSyncPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : ClientMovementPredictionSyncPacket{
		$flags = BitSet::read($in, ClientMovementPredictionSyncPacket::FLAG_LENGTH);
		$scale = LE::readFloat($in);
		$width = LE::readFloat($in);
		$height = LE::readFloat($in);
		$movementSpeed = LE::readFloat($in);
		$underwaterMovementSpeed = LE::readFloat($in);
		$lavaMovementSpeed = LE::readFloat($in);
		$jumpStrength = LE::readFloat($in);
		$health = LE::readFloat($in);
		$hunger = LE::readFloat($in);
		$actorUniqueId = CommonTypes::getActorUniqueId($in);
		$actorFlyingState = CommonTypes::getBool($in);

		return ClientMovementPredictionSyncPacket::create(
			$flags,
			$scale,
			$width,
			$height,
			$movementSpeed,
			$underwaterMovementSpeed,
			$lavaMovementSpeed,
			$jumpStrength,
			$health,
			$hunger,
			0.6, //frictionModifier: default vanilla, tidak dipakai PM
			0.0, //bounciness: default, tidak dipakai PM
			0.0, //airDragModifier: default, tidak dipakai PM
			$actorUniqueId,
			$actorFlyingState,
		);
	}
}
