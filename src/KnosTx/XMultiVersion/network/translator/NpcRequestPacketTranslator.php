<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\translator;

use KnosTx\XMultiVersion\network\ProtocolConstants;
use pocketmine\network\mcpe\protocol\NpcRequestPacket;

class NpcRequestPacketTranslator{

    public static function deserialize(NpcRequestPacket $packet, int $protocol) {
        $packet->entityRuntimeId = $packet->getEntityRuntimeId();
        $packet->requestType = $packet->getByte();
        $packet->commandString = $packet->getString();
        $packet->actionType = $packet->getByte();
        if($protocol >= ProtocolConstants::BEDROCK_1_20_80){
            $packet->sceneName = $packet->getString();
        }
    }
}