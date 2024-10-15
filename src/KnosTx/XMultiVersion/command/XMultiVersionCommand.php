<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\command;

use KnosTx\XMultiVersion\Loader;
use KnosTx\XMultiVersion\XMultiVersion;
use KnosTx\XMultiVersion\network\ProtocolConstants;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use function count;
use function strlen;
use function substr;

class XMultiVersionCommand extends PluginCommand implements PluginOwned {

    use PluginOwnedTrait;

    const PREFIX = TextFormat::YELLOW . "[" . TextFormat::GREEN . "Multi" . TextFormat::GOLD . "Version" . TextFormat::YELLOW . "] " . TextFormat::LIGHT_PURPLE;

    public function __construct(string $name, Loader $owner){
        parent::__construct($name, $owner);
        $this->setDescription("XMultiVersion command");
        $this->setAliases(["mv"]);
        $this->setPermission("XMultiVersion.command");
        $this->owningPlugin = $owner;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(count($args) === 0) {
            return;
        }
        switch($args[0]) {
            case "player":
                if(count($args) !== 2) {
                    $sender->sendMessage(self::PREFIX . "Usage: /XMultiVersion player <name>");
                    return;
                }
                $target = Server::getInstance()->getPlayerExact($args[1]);
                if(!$target instanceof Player) {
                    $sender->sendMessage(self::PREFIX . "Player " . $args[1] . " is not found!");
                    return;
                }
                $protocol = XMultiVersion::getProtocol($target);
                $ver = ProtocolConstants::MINECRAFT_VERSION[$protocol];
                $sender->sendMessage(self::PREFIX . $target->getName() . " is using version " . $ver . " (Protocol: " . $protocol . " )");
                return;
            case "all":
                foreach(Server::getInstance()->getOnlinePlayers() as $player) {
                    $protocol = XMultiVersion::getProtocol($player);
                    $ver = ProtocolConstants::MINECRAFT_VERSION[$protocol];
                    $msg = $player->getName() . " [Protocol: " . $protocol . ", Version: " . $ver . "]";
                    $sender->sendMessage(self::PREFIX . $msg . "\n");
                }
                return;
            case "version":
                $sender->sendMessage(" You are running 0.1.0 of XMultiVersion version");
                return;
            default:
                $sender->sendMessage(self::PREFIX . " Usage: /XMultiVersion <player|all>");
        }
    }
}
