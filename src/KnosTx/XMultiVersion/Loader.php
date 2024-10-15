<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion;

use KnosTx\XMultiVersion\command\XMultiVersionCommand;
use KnosTx\XMultiVersion\network\convert\XMultiVersionCraftingManager;
use KnosTx\XMultiVersion\network\convert\XMultiVersionRuntimeBlockMapping;
use KnosTx\XMultiVersion\task\CheckUpdateTask;
use pocketmine\inventory\CraftingManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use function in_array;

class Loader extends PluginBase{

    /** @var string */
    public static $resourcesPath;

    /** @var self */
    private static $instance;

    /** @var XMultiVersionCraftingManager */
    public $craftingManager;

    /** @var bool */
    public $canJoin = false;

    public static function getInstance() : self{
        return self::$instance;
    }

    public function onEnable() : void{
        self::$instance = $this;

        foreach($this->getResources() as $k => $v) {
            $this->saveResource($k, $k !== "config.yml");
        }

        Config::init($this->getDataFolder() . "config.yml");

        self::$resourcesPath = $this->getDataFolder();
        XMultiVersionRuntimeBlockMapping::init();

        // wait until other plugin register custom craft
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void {
            $this->craftingManager = new XMultiVersionCraftingManager();
            $this->canJoin = true;
        }), 1);

        $this->getServer()->getCommandMap()->register("XMultiVersion", new XMultiVersionCommand("XMultiVersion", $this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        CheckUpdateTask::init($this->getDescription()->getVersion());
    }

    public function isProtocolDisabled(int $protocol): bool{
        $config = Config::$DISABLED_PROTOCOLS;
        return in_array($protocol, $config, true);
    }
}
