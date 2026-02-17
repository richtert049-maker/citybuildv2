<?php
namespace CityBuildPlugin\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use CityBuildPlugin\Main;

class SpawnCommand extends Command {
    private Main $plugin;

    public function __construct(Main $plugin){
        parent::__construct("spawn","Teleport to spawn");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender,string $label,array $args): bool {
        if(!$sender instanceof Player) return false;
        $world = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
        if($world !== null){
            $sender->teleport($world->getSpawnLocation());
            $sender->sendMessage("§eDu wurdest zum Spawn teleportiert!");
        } else {
            $sender->sendMessage("§cSpawn konnte nicht gefunden werden!");
        }
        return true;
    }
}
