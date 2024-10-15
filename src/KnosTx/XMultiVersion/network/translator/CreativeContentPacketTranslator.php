<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\translator;

use KnosTx\XMultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use function count;

class CreativeContentPacketTranslator{

    public static function serialize(CreativeContentPacket $packet, int $protocol) {
        $packet->putUnsignedVarInt(count($packet->getEntries()));
        foreach($packet->getEntries() as $entry){
            $packet->writeGenericTypeNetworkId($entry->getEntryId());
            Serializer::putItemStackWithoutStackId($packet, $entry->getItem(), $protocol);
        }
    }
}