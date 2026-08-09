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
 * Port persis dari NetworkInventoryAction::read()/write() versi bedrock-protocol
 * 55.0.0 (1.26.0) - sebelum windowId/sourceFlags jadi nullable+optional-wrapped
 * dan sebelum dipecah jadi readAuthInput/readTransaction di versi 1.26.30.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;

final class LegacyNetworkInventoryAction{

	private function __construct(){
		//NOOP
	}

	public static function read(ByteBufferReader $in) : NetworkInventoryAction{
		$action = new NetworkInventoryAction();
		$action->sourceType = VarInt::readUnsignedInt($in);

		switch($action->sourceType){
			case NetworkInventoryAction::SOURCE_CONTAINER:
				$action->windowId = VarInt::readSignedInt($in);
				break;
			case NetworkInventoryAction::SOURCE_WORLD:
				$action->sourceFlags = VarInt::readUnsignedInt($in);
				break;
			case NetworkInventoryAction::SOURCE_CREATIVE:
				break;
			case NetworkInventoryAction::SOURCE_TODO:
				$action->windowId = VarInt::readSignedInt($in);
				break;
			default:
				throw new PacketDecodeException("Unknown inventory action source type $action->sourceType");
		}

		$action->inventorySlot = VarInt::readUnsignedInt($in);
		$action->oldItem = LegacyItemStackWrapper::read($in);
		$action->newItem = LegacyItemStackWrapper::read($in);

		return $action;
	}

	public static function write(ByteBufferWriter $out, NetworkInventoryAction $action) : void{
		VarInt::writeUnsignedInt($out, $action->sourceType);

		switch($action->sourceType){
			case NetworkInventoryAction::SOURCE_CONTAINER:
				VarInt::writeSignedInt($out, $action->windowId ?? 0);
				break;
			case NetworkInventoryAction::SOURCE_WORLD:
				VarInt::writeUnsignedInt($out, $action->sourceFlags ?? 0);
				break;
			case NetworkInventoryAction::SOURCE_CREATIVE:
				break;
			case NetworkInventoryAction::SOURCE_TODO:
				VarInt::writeSignedInt($out, $action->windowId ?? 0);
				break;
			default:
				throw new \InvalidArgumentException("Unknown inventory action source type $action->sourceType");
		}

		VarInt::writeUnsignedInt($out, $action->inventorySlot);
		LegacyItemStackWrapper::write($out, $action->oldItem);
		LegacyItemStackWrapper::write($out, $action->newItem);
	}
}
