<?php

/*
 *
 *  ____           _            __           _____
 * |  _ \    ___  (_)  _ __    / _|  _   _  |_   _|   ___    __ _   _ __ ___
 * | |_) |  / _ \ | | | '_ \  | |_  | | | |   | |    / _ \  / _` | | '_ ` _ \
 * |  _ <  |  __/ | | | | | | |  _| | |_| |   | |   |  __/ | (_| | | | | | | |
 * |_| \_\  \___| |_| |_| |_| |_|    \__, |   |_|    \___|  \__,_| |_| |_| |_|
 *                                   |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Zuri attempts to enforce "vanilla Minecraft" mechanics, as well as preventing
 * players from abusing weaknesses in Minecraft or its protocol, making your server
 * more safe. Organized in different sections, various checks are performed to test
 * players doing, covering a wide range including flying and speeding, fighting
 * hacks, fast block breaking and nukers, inventory hacks, chat spam and other types
 * of malicious behaviour.
 *
 * @author ReinfyTeam
 * @link https://github.com/ReinfyTeam/
 *
 *
 */

declare(strict_types=1);

namespace ReinfyTeam\Zuri\utils;

use AllowDynamicProperties;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission as PMPermission;
use pocketmine\permission\PermissionManager as PMPermissionManager;
use pocketmine\player\Player;
use pocketmine\utils\NotCloneable;
use pocketmine\utils\NotSerializable;
use pocketmine\utils\SingletonTrait;
use ReinfyTeam\Zuri\ZuriAC;

#[AllowDynamicProperties] class PermissionManager {
	use NotSerializable;
	use NotCloneable;
	use SingletonTrait;

	/** @var string[] */
	private array $perm = [];

	public const int USER = 0;
	public const int OPERATOR = 1;
	public const int CONSOLE = 3;
	public const int NONE = -1;

	public function register(string $permission, int $permAccess, array $childPermission = []) : void {
		$this->perm[] = $permission;

		$permManager = PMPermissionManager::getInstance();
		$perm = new PMPermission($permission, "Zuri Anticheat Custom Permission", $childPermission);

		$parentPermissionMap = [
			PermissionManager::USER => [DefaultPermissions::ROOT_USER, true],
			PermissionManager::OPERATOR => [DefaultPermissions::ROOT_OPERATOR, true],
			PermissionManager::CONSOLE => [DefaultPermissions::ROOT_CONSOLE, true],
			PermissionManager::NONE => [DefaultPermissions::ROOT_USER, false],
		];

		if (isset($parentPermissionMap[$permAccess])) {
			[$root, $access] = $parentPermissionMap[$permAccess];
			if (($parent = $permManager->getPermission($root)) !== null) {
				$parent->addChild($permission, $access);
			}
		}

		$permManager->addPermission($perm);
	}


	public function addPlayerPermissions(Player $player, array $permissions) : void {
		if ($this->attachment === null) {
			$this->attachment = $player->addAttachment(ZuriAC::getInstance());
		}
		$this->attachment->setPermissions($permissions);
		$player->getNetworkSession()->syncAvailableCommands();
	}

	public function resetPlayerPermissions() : void {
		if ($this->attachment === null) {
			return;
		}
		$this->attachment->clearPermissions();
	}

	public function getAllPermissions() : array {
		return $this->perm;
	}
}