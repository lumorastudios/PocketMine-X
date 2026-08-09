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
 * Port persis dari BossEventPacket versi bedrock-protocol 55.0.0 (1.26.0).
 * Bidirectional. Format LAMA memakai switch+fall-through berdasarkan eventType
 * (field yang ditulis beda-beda tergantung tipe event), field eventType/color/
 * overlay pakai VarInt (bukan Byte), dan ada field `darkenScreen` yang SUDAH
 * DIHAPUS di versi 1.26.30 - karena objek modern tidak lagi menyimpan nilai
 * ini sama sekali, kita pakai default `false` saat re-encode ke client lama
 * (nilai paling umum dipakai vanilla).
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class BossEventPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(BossEventPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::putActorUniqueId($out, $packet->bossActorUniqueId);
		VarInt::writeUnsignedInt($out, $packet->eventType);

		switch($packet->eventType){
			case BossEventPacket::TYPE_REGISTER_PLAYER:
			case BossEventPacket::TYPE_UNREGISTER_PLAYER:
			case BossEventPacket::TYPE_QUERY:
				CommonTypes::putActorUniqueId($out, $packet->playerActorUniqueId);
				break;
			case BossEventPacket::TYPE_SHOW:
				CommonTypes::putString($out, $packet->title);
				CommonTypes::putString($out, $packet->filteredTitle);
				LE::writeFloat($out, $packet->healthPercent);
				//sengaja fall-through, sama seperti versi 1.26.0
				// no break
			case BossEventPacket::TYPE_PROPERTIES:
				LE::writeUnsignedShort($out, 0); //darkenScreen: sudah tidak ada di objek modern, default false
				// no break
			case BossEventPacket::TYPE_TEXTURE:
				VarInt::writeUnsignedInt($out, $packet->color);
				VarInt::writeUnsignedInt($out, $packet->overlay);
				break;
			case BossEventPacket::TYPE_HEALTH_PERCENT:
				LE::writeFloat($out, $packet->healthPercent);
				break;
			case BossEventPacket::TYPE_TITLE:
				CommonTypes::putString($out, $packet->title);
				CommonTypes::putString($out, $packet->filteredTitle);
				break;
			default:
				break;
		}

		return $out->getData();
	}

	public static function decodePayload(ByteBufferReader $in) : BossEventPacket{
		$packet = new BossEventPacket();
		$packet->bossActorUniqueId = CommonTypes::getActorUniqueId($in);
		$packet->eventType = VarInt::readUnsignedInt($in);

		switch($packet->eventType){
			case BossEventPacket::TYPE_REGISTER_PLAYER:
			case BossEventPacket::TYPE_UNREGISTER_PLAYER:
			case BossEventPacket::TYPE_QUERY:
				$packet->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
				break;
			case BossEventPacket::TYPE_SHOW:
				$packet->title = CommonTypes::getString($in);
				$packet->filteredTitle = CommonTypes::getString($in);
				$packet->healthPercent = LE::readFloat($in);
				// no break
			case BossEventPacket::TYPE_PROPERTIES:
				$raw = LE::readUnsignedShort($in); //darkenScreen: dibaca tapi diabaikan, sudah tidak ada di objek modern
				if($raw !== 0 && $raw !== 1){
					throw new PacketDecodeException("Invalid darkenScreen value $raw");
				}
				// no break
			case BossEventPacket::TYPE_TEXTURE:
				$packet->color = VarInt::readUnsignedInt($in);
				$packet->overlay = VarInt::readUnsignedInt($in);
				break;
			case BossEventPacket::TYPE_HEALTH_PERCENT:
				$packet->healthPercent = LE::readFloat($in);
				break;
			case BossEventPacket::TYPE_TITLE:
				$packet->title = CommonTypes::getString($in);
				$packet->filteredTitle = CommonTypes::getString($in);
				break;
			default:
				break;
		}

		return $packet;
	}
}
