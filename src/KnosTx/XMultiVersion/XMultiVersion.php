<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion;

use KnosTx\XMultiVersion\session\SessionManager;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\player\Player;

class XMultiVersion{

    public static function getProtocol(Player $player): int{
        return SessionManager::getProtocol($player) ?? ProtocolInfo::CURRENT_PROTOCOL;
    }
}