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
 * Port persis dari method decodeData()/encodeData() tiap subclass TransactionData
 * versi bedrock-protocol 55.0.0 (1.26.0):
 * - NormalTransactionData / MismatchTransactionData: tidak ada data tambahan sama sekali
 * - ReleaseItemTransactionData, UseItemOnEntityTransactionData, UseItemTransactionData:
 *   actionType pakai VarInt UNSIGNED (1.26.30 pakai SIGNED), dan itemInHand pakai
 *   LegacyItemStackWrapper (bukan getNetworkItemStackDescriptor)
 * - UseItemTransactionData khususnya: triggerType/face/clientInteractPrediction dulu
 *   VarInt, sekarang jadi Byte; blockPosition butuh LegacyBlockPosition; dan field
 *   clientCooldownState BELUM ADA sama sekali di 1.26.0 (diisi default 0 di sini).
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\PredictedResult;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TriggerType;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

final class LegacyTransactionDataCodec{

	private function __construct(){
		//NOOP
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeNormal(array $actions, ByteBufferReader $in) : NormalTransactionData{
		return NormalTransactionData::new($actions);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeMismatch(array $actions, ByteBufferReader $in) : MismatchTransactionData{
		if(count($actions) > 0){
			throw new PacketDecodeException("Mismatch transaction type should not have any actions associated with it, but got " . count($actions));
		}
		return MismatchTransactionData::new();
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeReleaseItem(array $actions, ByteBufferReader $in) : ReleaseItemTransactionData{
		$actionType = VarInt::readUnsignedInt($in);
		$hotbarSlot = VarInt::readSignedInt($in);
		$itemInHand = LegacyItemStackWrapper::read($in);
		$headPosition = CommonTypes::getVector3($in);
		return ReleaseItemTransactionData::new($actions, $actionType, $hotbarSlot, $itemInHand, $headPosition);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeUseItemOnEntity(array $actions, ByteBufferReader $in) : UseItemOnEntityTransactionData{
		$actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$actionType = VarInt::readUnsignedInt($in);
		$hotbarSlot = VarInt::readSignedInt($in);
		$itemInHand = LegacyItemStackWrapper::read($in);
		$playerPosition = CommonTypes::getVector3($in);
		$clickPosition = CommonTypes::getVector3($in);
		return UseItemOnEntityTransactionData::new($actions, $actorRuntimeId, $actionType, $hotbarSlot, $itemInHand, $playerPosition, $clickPosition);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function decodeUseItem(array $actions, ByteBufferReader $in) : UseItemTransactionData{
		$actionType = VarInt::readUnsignedInt($in);
		$triggerType = TriggerType::fromPacket(VarInt::readUnsignedInt($in));
		$blockPosition = LegacyBlockPosition::read($in);
		$face = VarInt::readSignedInt($in);
		$hotbarSlot = VarInt::readSignedInt($in);
		$itemInHand = LegacyItemStackWrapper::read($in);
		$playerPosition = CommonTypes::getVector3($in);
		$clickPosition = CommonTypes::getVector3($in);
		$blockRuntimeId = VarInt::readUnsignedInt($in);
		$clientInteractPrediction = PredictedResult::fromPacket(VarInt::readUnsignedInt($in));
		//clientCooldownState belum ada di protokol 1.26.0-1.26.20, isi default
		return UseItemTransactionData::new($actions, $actionType, $triggerType, $blockPosition, $face, $hotbarSlot, $itemInHand, $playerPosition, $clickPosition, $blockRuntimeId, $clientInteractPrediction, 0);
	}

	public static function encodeReleaseItem(ByteBufferWriter $out, ReleaseItemTransactionData $data) : void{
		VarInt::writeUnsignedInt($out, $data->getActionType());
		VarInt::writeSignedInt($out, $data->getHotbarSlot());
		LegacyItemStackWrapper::write($out, $data->getItemInHand());
		CommonTypes::putVector3($out, $data->getHeadPosition());
	}

	public static function encodeUseItemOnEntity(ByteBufferWriter $out, UseItemOnEntityTransactionData $data) : void{
		CommonTypes::putActorRuntimeId($out, $data->getActorRuntimeId());
		VarInt::writeUnsignedInt($out, $data->getActionType());
		VarInt::writeSignedInt($out, $data->getHotbarSlot());
		LegacyItemStackWrapper::write($out, $data->getItemInHand());
		CommonTypes::putVector3($out, $data->getPlayerPosition());
		CommonTypes::putVector3($out, $data->getClickPosition());
	}

	public static function encodeUseItem(ByteBufferWriter $out, UseItemTransactionData $data) : void{
		VarInt::writeUnsignedInt($out, $data->getActionType());
		VarInt::writeUnsignedInt($out, $data->getTriggerType()->value);
		LegacyBlockPosition::write($out, $data->getBlockPosition());
		VarInt::writeSignedInt($out, $data->getFace());
		VarInt::writeSignedInt($out, $data->getHotbarSlot());
		LegacyItemStackWrapper::write($out, $data->getItemInHand());
		CommonTypes::putVector3($out, $data->getPlayerPosition());
		CommonTypes::putVector3($out, $data->getClickPosition());
		VarInt::writeUnsignedInt($out, $data->getBlockRuntimeId());
		VarInt::writeUnsignedInt($out, $data->getClientInteractPrediction()->value);
		//clientCooldownState sengaja tidak ditulis (belum ada di 1.26.0-1.26.20)
	}
}
