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
 * Port persis dari UpdateClientOptionsPacket versi bedrock-protocol 55.0.0
 * (1.26.0). Serverbound-only. PM sendiri TIDAK memproses isi packet ini sama
 * sekali, tapi kalau tidak ditangani, client lama yang mengirim packet ini
 * (mis. saat ganti graphics mode di menu options ketika sedang di server)
 * akan menyebabkan buffer-underrun saat decode field baru `filterProfanityChange`
 * di akhir - yang berpotensi membuat sesi ter-disconnect. Jadi tetap perlu
 * ditangani demi keamanan walau datanya tidak dipakai.
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GraphicsMode;
use pocketmine\network\mcpe\protocol\UpdateClientOptionsPacket;

final class UpdateClientOptionsPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function decodePayload(ByteBufferReader $in) : UpdateClientOptionsPacket{
		$graphicsMode = CommonTypes::readOptional($in, fn() => GraphicsMode::fromPacket(Byte::readUnsigned($in)));
		return UpdateClientOptionsPacket::create($graphicsMode, null);
	}
}
