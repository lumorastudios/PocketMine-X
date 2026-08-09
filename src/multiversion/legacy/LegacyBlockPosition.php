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
 * Di protokol 1.26.0 - 1.26.20 (protocol ID 924, 944, 975), BlockPosition
 * ditulis dengan X dan Z sebagai signed VarInt, tapi Y ditulis sebagai
 * UNSIGNED VarInt hasil dari reinterpretasi bit (bukan zigzag biasa).
 *
 * Di protokol 1.26.30 (protocol ID 1001, versi CURRENT_PROTOCOL server ini),
 * pmmp menggabungkan ini dengan varian "signed" yang sudah ada sebelumnya,
 * sehingga sekarang X, Y, Z semuanya ditulis sebagai signed VarInt biasa.
 *
 * Sumber pembanding:
 * - vendor lama (bedrock-protocol 55.0.0+bedrock-1.26.0)
 *   src/serializer/CommonTypes.php::getBlockPosition() / putBlockPosition()
 * - vendor baru (bedrock-protocol 58.0.0+bedrock-1.26.30)
 *   src/serializer/CommonTypes.php::getBlockPosition() / putBlockPosition()
 *   (sekarang berperilaku seperti getSignedBlockPosition() versi lama)
 *
 * PENTING: helper ini HANYA untuk field BlockPosition yang di versi lama
 * memakai varian "unsigned Y". Field yang di versi lama sudah memakai
 * getSignedBlockPosition()/putSignedBlockPosition() TIDAK butuh helper ini,
 * karena perilakunya sudah sama persis dengan versi baru.
 */

namespace pocketmine\multiversion\legacy;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

final class LegacyBlockPosition{

	private function __construct(){
		//NOOP
	}

	/**
	 * Setara dengan CommonTypes::getBlockPosition() di bedrock-protocol 55.0.0 (1.26.0).
	 */
	public static function read(ByteBufferReader $in) : BlockPosition{
		$x = VarInt::readSignedInt($in);
		$y = Binary::signInt(VarInt::readUnsignedInt($in)); //Y ditulis unsigned walau nilainya bisa negatif
		$z = VarInt::readSignedInt($in);
		return new BlockPosition($x, $y, $z);
	}

	/**
	 * Setara dengan CommonTypes::putBlockPosition() di bedrock-protocol 55.0.0 (1.26.0).
	 */
	public static function write(ByteBufferWriter $out, BlockPosition $blockPosition) : void{
		VarInt::writeSignedInt($out, $blockPosition->getX());
		VarInt::writeUnsignedInt($out, Binary::unsignInt($blockPosition->getY()));
		VarInt::writeSignedInt($out, $blockPosition->getZ());
	}
}
