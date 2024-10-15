<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\translator;

use KnosTx\XMultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;

class GameRulesChangedPacketTranslator{

    public static function serialize(GameRulesChangedPacket $packet, int $protocol) {
        Serializer::putGameRules($packet, $packet->gameRules, $protocol);
    }
}