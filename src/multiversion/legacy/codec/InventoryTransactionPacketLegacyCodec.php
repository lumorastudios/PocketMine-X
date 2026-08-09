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
 * Port persis dari InventoryTransactionPacket versi bedrock-protocol 55.0.0
 * (1.26.0). Beda utama dibanding 1.26.30:
 * - requestChangedSlots ditulis/dibaca implisit berdasarkan requestId !== 0
 *   (TIDAK ada byte "optional" pembungkus seperti di 1.26.30)
 * - TIDAK ada 2 byte "dummy optional" (selalu bernilai 1) sebelum transactionType & trData
 * - Semua sub-data transaksi pakai LegacyTransactionDataCodec (lihat file itu)
 */

namespace pocketmine\multiversion\legacy\codec;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\multiversion\legacy\LegacyNetworkInventoryAction;
use pocketmine\multiversion\legacy\LegacyPacketHeader;
use pocketmine\multiversion\legacy\LegacyTransactionDataCodec;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

final class InventoryTransactionPacketLegacyCodec{

	private function __construct(){
		//NOOP
	}

	public static function encode(InventoryTransactionPacket $packet) : string{
		$out = new ByteBufferWriter();
		LegacyPacketHeader::write($out, $packet);

		CommonTypes::writeLegacyItemStackRequestId($out, $packet->requestId);
		if($packet->requestId !== 0){
			$changedSlots = $packet->requestChangedSlots ?? [];
			VarInt::writeUnsignedInt($out, count($changedSlots));
			foreach($changedSlots as $changedSlot){
				$changedSlot->write($out);
			}
		}

		if($packet->trData === null){
			throw new \InvalidArgumentException("Cannot encode an InventoryTransactionPacket with no transaction data for a legacy (pre-1.26.30) client");
		}
		VarInt::writeUnsignedInt($out, $packet->trData->getTypeId());
		self::encodeTrData($out, $packet->trData);

		return $out->getData();
	}

	private static function encodeTrData(ByteBufferWriter $out, TransactionData $trData) : void{
		VarInt::writeUnsignedInt($out, count($trData->getActions()));
		foreach($trData->getActions() as $action){
			LegacyNetworkInventoryAction::write($out, $action);
		}

		match(true){
			$trData instanceof NormalTransactionData, $trData instanceof MismatchTransactionData => null,
			$trData instanceof ReleaseItemTransactionData => LegacyTransactionDataCodec::encodeReleaseItem($out, $trData),
			$trData instanceof UseItemOnEntityTransactionData => LegacyTransactionDataCodec::encodeUseItemOnEntity($out, $trData),
			$trData instanceof UseItemTransactionData => LegacyTransactionDataCodec::encodeUseItem($out, $trData),
			default => throw new \InvalidArgumentException("Unsupported transaction data type " . $trData::class),
		};
	}

	public static function decodePayload(ByteBufferReader $in) : InventoryTransactionPacket{
		$packet = new InventoryTransactionPacket();

		$packet->requestId = CommonTypes::readLegacyItemStackRequestId($in);
		$packet->requestChangedSlots = [];
		if($packet->requestId !== 0){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$packet->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = VarInt::readUnsignedInt($in);

		$actionCount = VarInt::readUnsignedInt($in);
		$actions = [];
		for($i = 0; $i < $actionCount; ++$i){
			$actions[] = LegacyNetworkInventoryAction::read($in);
		}

		$packet->trData = match($transactionType){
			NormalTransactionData::ID => LegacyTransactionDataCodec::decodeNormal($actions, $in),
			MismatchTransactionData::ID => LegacyTransactionDataCodec::decodeMismatch($actions, $in),
			UseItemTransactionData::ID => LegacyTransactionDataCodec::decodeUseItem($actions, $in),
			UseItemOnEntityTransactionData::ID => LegacyTransactionDataCodec::decodeUseItemOnEntity($actions, $in),
			ReleaseItemTransactionData::ID => LegacyTransactionDataCodec::decodeReleaseItem($actions, $in),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		return $packet;
	}
}
