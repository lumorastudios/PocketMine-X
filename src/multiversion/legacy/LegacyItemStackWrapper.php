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
 * Di 1.26.0-1.26.20, item stack di-encode lewat CommonTypes::getItemStackWrapper()/
 * putItemStackWrapper() (id sebagai VarInt bertanda, dengan id=0 berarti "kosong"
 * dan langsung berhenti tanpa membaca field lain).
 *
 * Di 1.26.30, packet-packet ini beralih ke CommonTypes::getNetworkItemStackDescriptor()/
 * putNetworkItemStackDescriptor() - format BERBEDA: id jadi LE short 2-byte TETAP
 * (tidak ada jalan pintas untuk id=0), ada field "variant" tambahan, dan
 * blockRuntimeId dibaca sebagai VarInt TIDAK bertanda (dulu bertanda).
 *
 * Class ini mereplikasi persis format LAMA, dipakai untuk semua packet yang
 * masih memanggil getItemStackWrapper/putItemStackWrapper di versi 1.26.0
 * (InventoryContentPacket, InventorySlotPacket, MobEquipmentPacket,
 * MobArmorEquipmentPacket, dan seluruh TransactionData di InventoryTransactionPacket).
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

final class LegacyItemStackWrapper{

	private function __construct(){
		//NOOP
	}

	public static function read(ByteBufferReader $in) : ItemStackWrapper{
		$id = VarInt::readSignedInt($in);
		if($id === 0){
			return new ItemStackWrapper(0, ItemStack::null());
		}

		$count = LE::readUnsignedShort($in);
		$meta = VarInt::readUnsignedInt($in);

		$hasNetId = CommonTypes::getBool($in);
		$stackId = $hasNetId ? VarInt::readSignedInt($in) : 0;

		$blockRuntimeId = VarInt::readSignedInt($in);
		$rawExtraData = CommonTypes::getString($in);

		$itemStack = new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData);

		return new ItemStackWrapper($stackId, $itemStack);
	}

	public static function write(ByteBufferWriter $out, ItemStackWrapper $wrapper) : void{
		$itemStack = $wrapper->getItemStack();

		if($itemStack->getId() === 0){
			VarInt::writeSignedInt($out, 0);
			return;
		}

		VarInt::writeSignedInt($out, $itemStack->getId());
		LE::writeUnsignedShort($out, $itemStack->getCount());
		VarInt::writeUnsignedInt($out, $itemStack->getMeta());

		$hasNetId = $wrapper->getStackId() !== 0;
		CommonTypes::putBool($out, $hasNetId);
		if($hasNetId){
			VarInt::writeSignedInt($out, $wrapper->getStackId());
		}

		VarInt::writeSignedInt($out, $itemStack->getBlockRuntimeId());
		CommonTypes::putString($out, $itemStack->getRawExtraData());
	}
}
