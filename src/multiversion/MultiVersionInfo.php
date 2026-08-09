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
 * Menyimpan daftar protokol Minecraft: Bedrock Edition yang diizinkan
 * untuk terhubung ke server ini, di luar protokol utama yang didukung
 * oleh vendor/pocketmine/bedrock-protocol (ProtocolInfo::CURRENT_PROTOCOL).
 *
 * PENTING: Menambahkan protokol di sini HANYA membuat client versi
 * tersebut TIDAK di-kick saat handshake. Ini TIDAK secara otomatis
 * menerjemahkan format packet/block/item antar versi. Selama tidak ada
 * kode translasi packet tambahan, client versi lama mungkin mengalami
 * bug (item hilang, block salah, dsb) tergantung seberapa jauh
 * perbedaan protokolnya.
 */

namespace pocketmine\multiversion;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function array_keys;
use function array_values;
use function count;
use function in_array;

final class MultiVersionInfo{

	private function __construct(){
		//NOOP
	}

	/**
	 * Daftar protokol tambahan yang didukung, di luar ProtocolInfo::CURRENT_PROTOCOL.
	 *
	 * Format: protocol_id => versi Minecraft (untuk keperluan tampilan /version dsb)
	 *
	 * Diurutkan dari yang PALING LAMA ke yang PALING BARU (penting untuk
	 * getOldestSupportedVersion()).
	 *
	 * @var array<int, string>
	 */
	private const ADDITIONAL_SUPPORTED_PROTOCOLS = [
		924 => "1.26.0",
		944 => "1.26.10",
		975 => "1.26.20",
	];

	/**
	 * @return int[]
	 */
	public static function getSupportedProtocols() : array{
		return [
			ProtocolInfo::CURRENT_PROTOCOL,
			...array_keys(self::ADDITIONAL_SUPPORTED_PROTOCOLS)
		];
	}

	public static function isProtocolSupported(int $protocol) : bool{
		return in_array($protocol, self::getSupportedProtocols(), true);
	}

	/**
	 * Versi Minecraft terendah yang didukung (untuk ditampilkan di /version).
	 * Jatuh balik ke versi utama jika tidak ada protokol tambahan.
	 */
	public static function getOldestSupportedVersion() : string{
		if(count(self::ADDITIONAL_SUPPORTED_PROTOCOLS) === 0){
			return ProtocolInfo::MINECRAFT_VERSION_NETWORK;
		}
		return array_values(self::ADDITIONAL_SUPPORTED_PROTOCOLS)[0];
	}

	/**
	 * Versi Minecraft terbaru yang didukung (untuk ditampilkan di /version).
	 */
	public static function getNewestSupportedVersion() : string{
		return ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	}

	/**
	 * String rentang versi untuk ditampilkan, misal "1.26.0 - 1.26.30".
	 * Jika hanya ada 1 versi yang didukung, hanya menampilkan versi itu saja.
	 */
	public static function getVersionRangeString() : string{
		$oldest = self::getOldestSupportedVersion();
		$newest = self::getNewestSupportedVersion();
		if($oldest === $newest){
			return $newest;
		}
		return $oldest . " - " . $newest;
	}
}
