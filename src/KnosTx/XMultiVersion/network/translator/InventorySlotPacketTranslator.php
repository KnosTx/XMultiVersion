<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\translator;

use KnosTx\XMultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;

class InventorySlotPacketTranslator{

    public static function serialize(InventorySlotPacket $packet, int $protocol) {
        $packet->putUnsignedVarInt($packet->windowId);
        $packet->putUnsignedVarInt($packet->inventorySlot);
        Serializer::putItem($packet, $protocol, $packet->item->getItemStack(), $packet->item->getStackId());
    }
}