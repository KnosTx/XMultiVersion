<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\convert;

use KnosTx\XMultiVersion\Loader;
use KnosTx\XMultiVersion\network\ProtocolConstants;
use KnosTx\XMultiVersion\network\Translator;
use pocketmine\inventory\CraftingManager;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Server;
use pocketmine\timings\Timings;

class XMultiVersionCraftingManager extends CraftingManager{

    /** @var BatchPacket[] */
    protected $XMultiVersionCraftingDataCache = [];

    const PROTOCOL = [
        ProtocolConstants::BEDROCK_1_21_30,
        ProtocolConstants::BEDROCK_1_21_20,
        ProtocolConstants::BEDROCK_1_21_0,
        ProtocolConstants::BEDROCK_1_20_80
    ];

    public function buildCraftingDataCache(): void{
        Timings::$craftingDataCacheRebuildTimer->startTiming();
        $c = Server::getInstance()->getCraftingManager();
        foreach(self::PROTOCOL as $protocol){
            if(Loader::getInstance()->isProtocolDisabled($protocol)) {
                continue;
            }
            $pk = new CraftingDataPacket();
            $pk->cleanRecipes = true;

            foreach($c->shapelessRecipes as $list){
                foreach($list as $recipe){
                    $pk->addShapelessRecipe($recipe);
                }
            }
            foreach($c->shapedRecipes as $list){
                foreach($list as $recipe){
                    $pk->addShapedRecipe($recipe);
                }
            }

            foreach($c->furnaceRecipes as $recipe){
                $pk->addFurnaceRecipe($recipe);
            }

            $pk = Translator::fromServer($pk, $protocol);

            $batch = new BatchPacket();
            $batch->addPacket($pk);
            $batch->setCompressionWorld(Server::getInstance()->networkCompressionWorld);
            $batch->encode();

            $this->XMultiVersionCraftingDataCache[$protocol] = $batch;
        }
        Timings::$craftingDataCacheRebuildTimer->stopTiming();
    }

    public function getCraftingDataPacketA(int $protocol): BatchPacket{
        return $this->XMultiVersionCraftingDataCache[$protocol];
    }
}