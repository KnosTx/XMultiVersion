<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\translator;

use KnosTx\XMultiVersion\network\ProtocolConstants;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

class SetTitlePacketTranslator{

    public static function serialize(SetTitlePacket $packet, int $protocol) {
        $packet->putVarInt($packet->type);
        $packet->putString($packet->text);
        $packet->putVarInt($packet->fadeInTime);
        $packet->putVarInt($packet->stayTime);
        $packet->putVarInt($packet->fadeOutTime);
        if($protocol >= ProtocolConstants::BEDROCK_1_21_80){
            $packet->putString($packet->xuid);
            $packet->putString($packet->platformOnlineId);
        }
    }
}