<?php

declare(strict_types=1);

namespace ReinfyTeam\Zuri;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use ReinfyTeam\Zuri\checks\Check;
use ReinfyTeam\Zuri\config\ConfigManager;
use ReinfyTeam\Zuri\player\PlayerAPI;
use ReinfyTeam\Zuri\utils\discord\Discord;
use function array_filter;
use function array_values;

final class API {
    private static ?ConfigManager $config = null;
    private static ?array $moduleCache = null;

    public static function getVersion(): string {
        return self::getPluginInstance()->getDescription()->getVersion();
    }

    public static function getPlayer(string|Player $player): ?PlayerAPI {
        $target = $player instanceof Player ? $player : Server::getInstance()->getPlayerExact($player);
        return $target !== null ? PlayerAPI::getAPIPlayer($target) : null;
    }

    public static function getModule(string $name, string $subType): ?Check {
        if (self::$moduleCache === null) {
            self::buildModuleCache();
        }
        
        return self::$moduleCache[$name][$subType] ?? null;
    }

    private static function buildModuleCache(): void {
        self::$moduleCache = [];
        foreach (ZuriAC::getChecks() as $module) {
            self::$moduleCache[$module->getName()][$module->getSubType()] = $module;
        }
    }

    public static function getAllChecks(bool $includeSubChecks = true): array {
        if ($includeSubChecks) {
            return ZuriAC::getChecks();
        }
        
        $unique = [];
        foreach (ZuriAC::getChecks() as $module) {
            $unique[$module->getName()] ??= $module;
        }
        return array_values($unique);
    }

    public static function getAllDisabledChecks(bool $includeSubChecks = true): array {
        return array_filter(
            self::getAllChecks($includeSubChecks),
            static fn(Check $module): bool => !$module->enable()
        );
    }

    public static function getAllEnabledChecks(bool $includeSubChecks = true): array {
        return array_filter(
            self::getAllChecks($includeSubChecks),
            static fn(Check $module): bool => $module->enable()
        );
    }

    public static function getConfig(): ConfigManager {
        return self::$config ??= new ConfigManager();
    }

    public static function getModuleInfo(string $name, string $subType): ?array {
        $module = self::getModule($name, $subType);
        return $module !== null ? [
            'name' => $module->getName(),
            'subType' => $module->getSubType(),
            'punishment' => $module->getPunishment(),
            'maxViolations' => $module->maxViolations()
        ] : null;
    }

    public static function getPluginInstance(): ZuriAC {
        return ZuriAC::getInstance();
    }

    public static function getDiscordWebhookConfig(): Config {
        return Discord::getWebhookConfig();
    }
}
