<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network;

use KnosTx\XMultiVersion\network\convert\XMultiVersionItemTypeDictionary;
use KnosTx\XMultiVersion\network\convert\XMultiVersionRuntimeBlockMapping;
use KnosTx\XMultiVersion\network\translator\AddItemActorPacketTranslator;
use KnosTx\XMultiVersion\network\translator\AddPlayerPacketTranslator;
use KnosTx\XMultiVersion\network\translator\AnimateEntityPacketTranslator;
use KnosTx\XMultiVersion\network\translator\AvailableCommandsPacketTranslator;
use KnosTx\XMultiVersion\network\translator\CraftingDataPacketTranslator;
use KnosTx\XMultiVersion\network\translator\CreativeContentPacketTranslator;
use KnosTx\XMultiVersion\network\translator\GameRulesChangedPacketTranslator;
use KnosTx\XMultiVersion\network\translator\InventoryContentPacketTranslator;
use KnosTx\XMultiVersion\network\translator\InventorySlotPacketTranslator;
use KnosTx\XMultiVersion\network\translator\InventoryTransactionPacketTranslator;
use KnosTx\XMultiVersion\network\translator\MobArmorEquipmentPacketTranslator;
use KnosTx\XMultiVersion\network\translator\MobEquipmentPacketTranslator;
use KnosTx\XMultiVersion\network\translator\NpcRequestPacketTranslator;
use KnosTx\XMultiVersion\network\translator\PlayerListPacketTranslator;
use KnosTx\XMultiVersion\network\translator\PlayerSkinPacketTranslator;
use KnosTx\XMultiVersion\network\translator\ResourcePacksInfoPacketTranslator;
use KnosTx\XMultiVersion\network\translator\SetTitlePacketTranslator;
use KnosTx\XMultiVersion\network\translator\StartGamePacketTranslator;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\WorldChunkPacket;
use pocketmine\network\mcpe\protocol\WorldEventPacket;
use pocketmine\network\mcpe\protocol\WorldSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\NpcRequestPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

class Translator{

    public static function fromClient(DataPacket $packet, int $protocol, Player $player = null) : DataPacket{
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case LoginPacket::NETWORK_ID:
                /** @var LoginPacket $packet */
                if($protocol < ProtocolConstants::BEDROCK_1_21_30) {
                    $packet->clientData["SkinGeometryDataEngineVersion"] = "";
                }
                return $packet;
            case PlayerSkinPacket::NETWORK_ID:
                /** @var PlayerSkinPacket $packet */
                if($protocol < ProtocolConstants::BEDROCK_1_21_30) {
                    self::decodeHeader($packet);
                    PlayerSkinPacketTranslator::deserialize($packet, $protocol);
                }
                return $packet;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
                self::decodeHeader($packet);
                InventoryTransactionPacketTranslator::deserialize($packet, $protocol);
                return $packet;
            case WorldSoundEventPacket::NETWORK_ID:
                /** @var WorldSoundEventPacket $packet */
                $packet->decode();
                switch($packet->sound) {
                    case WorldSoundEventPacket::SOUND_PLACE:
                    case WorldSoundEventPacket::SOUND_BREAK_BLOCK:
                        $block = XMultiVersionRuntimeBlockMapping::fromStaticRuntimeId($packet->extraData, $protocol);
                        $packet->extraData = RuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1]);
                        return $packet;
                }
                return $packet;
            case NpcRequestPacket::NETWORK_ID:
                /** @var NpcRequestPacket $packet */
                self::decodeHeader($packet);
                NpcRequestPacketTranslator::deserialize($packet, $protocol);
                return $packet;
            case MobEquipmentPacket::NETWORK_ID:
                /** @var MobEquipmentPacket $packet */
                self::decodeHeader($packet);
                MobEquipmentPacketTranslator::deserialize($packet, $protocol);
                return $packet;
            case MobArmorEquipmentPacket::NETWORK_ID:
                /** @var MobArmorEquipmentPacket $packet */
                self::decodeHeader($packet);
                MobArmorEquipmentPacketTranslator::deserialize($packet, $protocol);
                return $packet;
        }
        return $packet;
    }

    public static function fromServer(DataPacket $packet, int $protocol, Player $player = null, bool &$translated = true) : ?DataPacket {
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case ResourcePackStackPacket::NETWORK_ID:
                /** @var ResourcePackStackPacket $packet */
                $packet->baseGameVersion = "1.16.220";
                return $packet;
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet */
                $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);
                $packet->blockRuntimeId = XMultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                return $packet;
            case WorldSoundEventPacket::NETWORK_ID:
                /** @var WorldSoundEventPacket $packet */
                switch($packet->sound) {
                    case WorldSoundEventPacket::SOUND_PLACE:
                    case WorldSoundEventPacket::SOUND_BREAK_BLOCK:
                        $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->extraData);
                        $packet->extraData = XMultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                        return $packet;
                }
                return $packet;
            case AddActorPacket::NETWORK_ID:
                /** @var AddActorPacket $packet */
                switch($packet->type) {
                    case "minecraft:falling_block":
                        if(isset($packet->metadata[Entity::DATA_VARIANT])){
                            $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->metadata[Entity::DATA_VARIANT][1]);
                            $packet->metadata[Entity::DATA_VARIANT] = [Entity::DATA_TYPE_INT, XMultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol)];
                        }
                        return $packet;
                }
                return $packet;
            case WorldEventPacket::NETWORK_ID:
                /** @var WorldEventPacket $packet */
                switch($packet->evid) {
                    case WorldEventPacket::EVENT_PARTICLE_DESTROY:
                        $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->data);
                        $packet->data = XMultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                        return $packet;
                    case WorldEventPacket::EVENT_PARTICLE_PUNCH_BLOCK:
                        $position = $packet->position;
                        $block = $player->getWorldNonNull()->getBlock($position);
                        if($block->getId() === 0) {
                            return null;
                        }
                        $face = $packet->data & ~$block->getRuntimeId();
                        $packet->data = XMultiVersionRuntimeBlockMapping::toStaticRuntimeId($block->getId(), $block->getDamage(), $protocol) | $face;
                        return $packet;
                }
                return $packet;
            case WorldChunkPacket::NETWORK_ID:
                /** @var WorldChunkPacket $packet */
                if($protocol <= ProtocolConstants::BEDROCK_1_21_0) {
                    if($player->getWorld() !== null){
                        return Chunk112::serialize($player->getWorld(), $packet);
                    }
                    return null;
                }
                return $packet;
            case AnimateEntityPacket::NETWORK_ID:
                /** @var AnimateEntityPacket $packet */
                self::encodeHeader($packet);
                AnimateEntityPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case CraftingDataPacket::NETWORK_ID:
                /** @var CraftingDataPacket $packet */
                self::encodeHeader($packet);
                CraftingDataPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case PlayerListPacket::NETWORK_ID:
                /** @var PlayerListPacket $packet */
                self::encodeHeader($packet);
                PlayerListPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case StartGamePacket::NETWORK_ID:
                /** @var StartGamePacket $packet */
                $packet->itemTable = XMultiVersionItemTypeDictionary::getInstance()->getEntries($protocol);
                self::encodeHeader($packet);
                StartGamePacketTranslator::serialize($packet, $protocol);
                return $packet;
            case PlayerSkinPacket::NETWORK_ID:
                /** @var PlayerSkinPacket $packet */
                self::encodeHeader($packet);
                PlayerSkinPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case AddItemActorPacket::NETWORK_ID:
                /** @var AddItemActorPacket $packet */
                self::encodeHeader($packet);
                AddItemActorPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case InventoryContentPacket::NETWORK_ID:
                /** @var InventoryContentPacket $packet */
                self::encodeHeader($packet);
                InventoryContentPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case MobEquipmentPacket::NETWORK_ID:
                /** @var MobEquipmentPacket $packet */
                self::encodeHeader($packet);
                MobEquipmentPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case MobArmorEquipmentPacket::NETWORK_ID:
                /** @var MobArmorEquipmentPacket $packet */
                self::encodeHeader($packet);
                MobArmorEquipmentPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case AddPlayerPacket::NETWORK_ID:
                /** @var AddPlayerPacket $packet */
                self::encodeHeader($packet);
                AddPlayerPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case InventorySlotPacket::NETWORK_ID:
                /** @var InventorySlotPacket $packet */
                self::encodeHeader($packet);
                InventorySlotPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
                self::encodeHeader($packet);
                InventoryTransactionPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case CreativeContentPacket::NETWORK_ID:
                /** @var CreativeContentPacket $packet */
                self::encodeHeader($packet);
                CreativeContentPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case AvailableCommandsPacket::NETWORK_ID:
                /** @var AvailableCommandsPacket $packet */
                self::encodeHeader($packet);
                AvailableCommandsPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case SetTitlePacket::NETWORK_ID:
                /** @var SetTitlePacket $packet */
                self::encodeHeader($packet);
                SetTitlePacketTranslator::serialize($packet, $protocol);
                return $packet;
            case ResourcePacksInfoPacket::NETWORK_ID:
                /** @var ResourcePacksInfoPacket $packet */
                self::encodeHeader($packet);
                ResourcePacksInfoPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case GameRulesChangedPacket::NETWORK_ID:
                /** @var GameRulesChangedPacket $packet */
                self::encodeHeader($packet);
                GameRulesChangedPacketTranslator::serialize($packet, $protocol);
                return $packet;
        }
        $translated = false;
        return $packet;
    }

    public static function encodeHeader(DataPacket $packet) {
        $packet->reset();
        $packet->putUnsignedVarInt(
            $packet::NETWORK_ID |
            ($packet->senderSubId << 10) |
            ($packet->recipientSubId << 12)
        );
        $packet->isEncoded = true;
    }

    public static function decodeHeader(DataPacket $packet) {
        $packet->isEncoded = false;
        $packet->offset = 0;
        $header = $packet->getUnsignedVarInt();
        $pid = $header & $packet::PID_MASK;
        if($pid !== $packet::NETWORK_ID){
            throw new \UnexpectedValueException("Expected " . $packet::NETWORK_ID . " for packet ID, got $pid");
        }
        $packet->senderSubId = ($header >> 10) & 0x03;
        $packet->recipientSubId = ($header >> 12) & 0x03;
    }
}