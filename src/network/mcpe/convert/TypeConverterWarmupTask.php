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

namespace pocketmine\network\mcpe\convert;

use pocketmine\scheduler\AsyncTask;
use function microtime;

/**
 * Forces TypeConverter::getInstance() (and therefore BlockStateDictionary/ItemTypeDictionary construction) to run
 * once inside a fresh async worker thread as soon as that worker starts, instead of on-demand the first time a
 * player requests a chunk. Building the block palette (16000+ entries) is measurably much slower (1-2 seconds)
 * the first time it runs in a brand new worker thread than on an already-warmed thread, likely due to per-thread
 * JIT warm-up. Without this, the first player to request chunks from a freshly-started worker can time out
 * waiting for chunk data and get disconnected before the first chunk is ever produced.
 */
final class TypeConverterWarmupTask extends AsyncTask{

	public function onRun() : void{
		\GlobalLogger::get()->debug("TypeConverterWarmupTask: starting warmup at " . microtime(true));
		TypeConverter::getInstance();
		\GlobalLogger::get()->debug("TypeConverterWarmupTask: warmup complete at " . microtime(true));
	}
}
