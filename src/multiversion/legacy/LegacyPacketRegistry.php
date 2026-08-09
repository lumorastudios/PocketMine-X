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
 * Titik pusat untuk translasi packet antara protokol lama (1.26.0/1.26.10/
 * 1.26.20) dan protokol server saat ini (1.26.30 / CURRENT_PROTOCOL).
 *
 * ARAH KELUAR (server -> client / outgoing):
 *   Packet sudah dibuat & di-encode dengan format MODERN (mengikuti kode
 *   game PM apa adanya). Kalau sesi ini protokolnya lama DAN packetnya ada
 *   di daftar yang butuh penyesuaian, kita timpa hasil encode modern itu
 *   dengan hasil dari legacy codec.
 *
 * ARAH MASUK (client -> server / incoming):
 *   Client lama mengirim bytes dalam format LAMA. Supaya sisa alur decode
 *   bawaan PocketMine (yang memanggil $packet->decode() dengan format
 *   MODERN) tetap bisa jalan tanpa modifikasi lebih lanjut, kita decode
 *   payload memakai legacy codec dulu untuk mendapatkan objek packet yang
 *   terisi benar, lalu langsung encode ULANG objek itu memakai method
 *   encode() BAWAAN VENDOR (yang otomatis pakai format modern). Hasilnya:
 *   buffer modern yang valid, siap diproses alur bawaan PocketMine seperti biasa.
 *
 * KETERBATASAN YANG DIKETAHUI (sengaja BELUM ditangani):
 * - types/command/CommandParameterTypes::CODEBUILDERARGS: nomor tipe parameter
 *   ini bergeser (87->88) karena ada 1 tipe baru disisipkan sebelumnya. Cuma
 *   berpengaruh kalau ada command yang benar-benar pakai parameter tipe
 *   "code builder" (fitur Education Edition) - sangat kecil kemungkinannya
 *   dipakai plugin/minigame biasa.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\codec\ActorEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\AddVolumeEntityPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\AnvilDamagePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BlockActorDataPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BlockEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\BossEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ClientCacheBlobStatusPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ClientMovementPredictionSyncPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\CommandBlockUpdatePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\ContainerOpenPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\InventoryContentPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\InventorySlotPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\InventoryTransactionPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\LecternUpdatePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\LevelSoundEventPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\MobArmorEquipmentPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\MobEquipmentPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\OpenSignPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\PlayerActionPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\PlayerEnchantOptionsPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\PlaySoundPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\SetSpawnPositionPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\StartGamePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\StructureBlockUpdatePacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\StructureTemplateDataRequestPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\SubChunkRequestPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\UpdateBlockPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\UpdateClientOptionsPacketLegacyCodec;
use pocketmine\multiversion\legacy\codec\UpdateSubChunkBlocksPacketLegacyCodec;
use pocketmine\multiversion\MultiVersionInfo;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerEnchantOptionsPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;
use function in_array;
use function strlen;

final class LegacyPacketRegistry{

	private function __construct(){
		//NOOP
	}

	/**
	 * Daftar packet ID yang punya legacy codec untuk arah MASUK (serverbound).
	 * Dipisah dari logika translate supaya bisa dicek murah (cuma intip pid)
	 * sebelum benar-benar decode apa pun.
	 *
	 * @var int[]
	 */
	private const INCOMING_TRANSLATABLE_PIDS = [
		ProtocolInfo::PLAYER_ACTION_PACKET,
		ProtocolInfo::BLOCK_ACTOR_DATA_PACKET,
		ProtocolInfo::ANVIL_DAMAGE_PACKET,
		ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET,
		ProtocolInfo::LECTERN_UPDATE_PACKET,
		ProtocolInfo::STRUCTURE_BLOCK_UPDATE_PACKET,
		ProtocolInfo::STRUCTURE_TEMPLATE_DATA_REQUEST_PACKET,
		ProtocolInfo::INVENTORY_TRANSACTION_PACKET,
		ProtocolInfo::MOB_EQUIPMENT_PACKET,
		ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET,
		ProtocolInfo::ACTOR_EVENT_PACKET,
		ProtocolInfo::BOSS_EVENT_PACKET,
		ProtocolInfo::SUB_CHUNK_REQUEST_PACKET,
		ProtocolInfo::CLIENT_MOVEMENT_PREDICTION_SYNC_PACKET,
		ProtocolInfo::CLIENT_CACHE_BLOB_STATUS_PACKET,
		ProtocolInfo::UPDATE_CLIENT_OPTIONS_PACKET,
		ProtocolInfo::LEVEL_SOUND_EVENT_PACKET,
	];

	public static function isLegacySession(int $protocolVersion) : bool{
		return $protocolVersion !== ProtocolInfo::CURRENT_PROTOCOL && MultiVersionInfo::isProtocolSupported($protocolVersion);
	}

	/**
	 * @internal dipanggil dari NetworkSession saat mengirim packet ke client.
	 */
	public static function translateOutgoing(int $protocolVersion, ClientboundPacket $packet, string $modernEncodedBuffer) : string{
		if(!self::isLegacySession($protocolVersion)){
			return $modernEncodedBuffer;
		}

		return match(true){
			$packet instanceof StartGamePacket => StartGamePacketLegacyCodec::encode($packet),
			$packet instanceof PlayerActionPacket => PlayerActionPacketLegacyCodec::encode($packet),
			$packet instanceof UpdateBlockPacket => UpdateBlockPacketLegacyCodec::encode($packet),
			$packet instanceof AddVolumeEntityPacket => AddVolumeEntityPacketLegacyCodec::encode($packet),
			$packet instanceof BlockActorDataPacket => BlockActorDataPacketLegacyCodec::encode($packet),
			$packet instanceof BlockEventPacket => BlockEventPacketLegacyCodec::encode($packet),
			$packet instanceof ContainerOpenPacket => ContainerOpenPacketLegacyCodec::encode($packet),
			$packet instanceof OpenSignPacket => OpenSignPacketLegacyCodec::encode($packet),
			$packet instanceof SetSpawnPositionPacket => SetSpawnPositionPacketLegacyCodec::encode($packet),
			$packet instanceof UpdateSubChunkBlocksPacket => UpdateSubChunkBlocksPacketLegacyCodec::encode($packet),
			$packet instanceof InventoryContentPacket => InventoryContentPacketLegacyCodec::encode($packet),
			$packet instanceof InventorySlotPacket => InventorySlotPacketLegacyCodec::encode($packet),
			$packet instanceof InventoryTransactionPacket => InventoryTransactionPacketLegacyCodec::encode($packet),
			$packet instanceof MobEquipmentPacket => MobEquipmentPacketLegacyCodec::encode($packet),
			$packet instanceof MobArmorEquipmentPacket => MobArmorEquipmentPacketLegacyCodec::encode($packet),
			$packet instanceof ActorEventPacket => ActorEventPacketLegacyCodec::encode($packet),
			$packet instanceof BossEventPacket => BossEventPacketLegacyCodec::encode($packet),
			$packet instanceof PlaySoundPacket => PlaySoundPacketLegacyCodec::encode($packet),
			$packet instanceof PlayerEnchantOptionsPacket => PlayerEnchantOptionsPacketLegacyCodec::encode($packet),
			$packet instanceof LevelSoundEventPacket => LevelSoundEventPacketLegacyCodec::encode($packet),
			default => $modernEncodedBuffer,
		};
	}

	/**
	 * @internal dipanggil dari NetworkSession saat menerima packet dari client.
	 * Mengembalikan buffer yang SUDAH dalam format modern (siap diproses decode() bawaan),
	 * atau buffer asli apa adanya kalau tidak butuh translasi.
	 */
	public static function translateIncoming(int $protocolVersion, string $buffer) : string{
		if(!self::isLegacySession($protocolVersion) || strlen($buffer) === 0){
			return $buffer;
		}

		$pid = self::peekPacketId($buffer);
		if(!in_array($pid, self::INCOMING_TRANSLATABLE_PIDS, true)){
			return $buffer;
		}

		$reader = new ByteBufferReader($buffer);
		[$senderSubId, $recipientSubId] = self::readHeaderSubIds($reader);

		$packet = match($pid){
			ProtocolInfo::PLAYER_ACTION_PACKET => PlayerActionPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::BLOCK_ACTOR_DATA_PACKET => BlockActorDataPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::ANVIL_DAMAGE_PACKET => AnvilDamagePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET => CommandBlockUpdatePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::LECTERN_UPDATE_PACKET => LecternUpdatePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::STRUCTURE_BLOCK_UPDATE_PACKET => StructureBlockUpdatePacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::STRUCTURE_TEMPLATE_DATA_REQUEST_PACKET => StructureTemplateDataRequestPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::MOB_EQUIPMENT_PACKET => MobEquipmentPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::ACTOR_EVENT_PACKET => ActorEventPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::BOSS_EVENT_PACKET => BossEventPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::SUB_CHUNK_REQUEST_PACKET => SubChunkRequestPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::CLIENT_MOVEMENT_PREDICTION_SYNC_PACKET => ClientMovementPredictionSyncPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::CLIENT_CACHE_BLOB_STATUS_PACKET => ClientCacheBlobStatusPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::UPDATE_CLIENT_OPTIONS_PACKET => UpdateClientOptionsPacketLegacyCodec::decodePayload($reader),
			ProtocolInfo::LEVEL_SOUND_EVENT_PACKET => LevelSoundEventPacketLegacyCodec::decodePayload($reader), // @phpstan-ignore match.alwaysTrue (last of exactly INCOMING_TRANSLATABLE_PIDS members after narrowing; not dead code)
			default => null,
		};

		if($packet === null){
			return $buffer;
		}

		$packet->senderSubId = $senderSubId;
		$packet->recipientSubId = $recipientSubId;

		//Re-encode pakai method encode() bawaan vendor (otomatis format modern/1.26.30),
		//supaya sisa alur decode() bawaan PocketMine tidak perlu diubah sama sekali.
		$out = new ByteBufferWriter();
		$packet->encode($out);
		return $out->getData();
	}

	private static function peekPacketId(string $buffer) : int{
		$reader = new ByteBufferReader($buffer);
		$header = VarInt::readUnsignedInt($reader);
		return $header & DataPacket::PID_MASK;
	}

	/**
	 * @return array{int, int} [senderSubId, recipientSubId]
	 */
	private static function readHeaderSubIds(ByteBufferReader $in) : array{
		$header = VarInt::readUnsignedInt($in);
		$senderSubId = ($header >> 10) & 0x03;
		$recipientSubId = ($header >> 12) & 0x03;
		return [$senderSubId, $recipientSubId];
	}
}
