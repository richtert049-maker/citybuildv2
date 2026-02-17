<?php

declare(strict_types=1);

namespace CityBuildPlugin\command;

use CityBuildPlugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

final class RankCommand extends Command{

    public function __construct(private Main $plugin){
        parent::__construct("rang", "Ränge verwalten", "/rang <create|setprefix|setmaxplots|permission|setplayer|info|list>");
        $this->setPermission("citybuild.rank.manage");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if(!$sender->hasPermission("citybuild.rank.manage")){
            $sender->sendMessage("§cKeine Rechte.");
            return true;
        }

        $rankManager = $this->plugin->getRankManager();
        $sub = strtolower($args[0] ?? "help");

        switch($sub){
            case "create":
                if(count($args) < 3){
                    $sender->sendMessage("§c/rang create <name> <prefix> [maxPlots]");
                    return true;
                }
                $name = $args[1];
                $prefix = $args[2];
                $maxPlots = isset($args[3]) ? max(1, (int)$args[3]) : 1;
                $ok = $rankManager->createRank($name, $prefix, $maxPlots);
                $sender->sendMessage($ok ? "§aRang erstellt." : "§cRang existiert bereits.");
                return true;

            case "setprefix":
                if(count($args) < 3){
                    $sender->sendMessage("§c/rang setprefix <rang> <prefix>");
                    return true;
                }
                $ok = $rankManager->setPrefix($args[1], $args[2]);
                $sender->sendMessage($ok ? "§aPrefix gesetzt." : "§cRang nicht gefunden.");
                return true;

            case "setmaxplots":
                if(count($args) < 3){
                    $sender->sendMessage("§c/rang setmaxplots <rang> <anzahl>");
                    return true;
                }
                $ok = $rankManager->setMaxPlots($args[1], (int)$args[2]);
                $sender->sendMessage($ok ? "§aMaxPlots aktualisiert." : "§cRang nicht gefunden.");
                return true;

            case "permission":
                if(count($args) < 4){
                    $sender->sendMessage("§c/rang permission <add|remove> <rang> <permission>");
                    return true;
                }
                $mode = strtolower($args[1]);
                $rank = $args[2];
                $permission = $args[3];
                $ok = $mode === "add"
                    ? $rankManager->addPermission($rank, $permission)
                    : $rankManager->removePermission($rank, $permission);
                $sender->sendMessage($ok ? "§aPermissions angepasst." : "§cAktion fehlgeschlagen.");
                return true;

            case "setplayer":
                if(count($args) < 3){
                    $sender->sendMessage("§c/rang setplayer <spieler> <rang>");
                    return true;
                }
                $ok = $rankManager->setPlayerRank($args[1], $args[2]);
                if($ok){
                    $target = $this->plugin->getServer()->getPlayerExact($args[1]);
                    if($target !== null){
                        $prefix = $rankManager->getPrefix($target);
                        $target->setNameTag($prefix . " §f" . $target->getName());
                    }
                }
                $sender->sendMessage($ok ? "§aRang gesetzt." : "§cRang nicht gefunden.");
                return true;

            case "info":
                if(count($args) < 2){
                    $sender->sendMessage("§c/rang info <rang>");
                    return true;
                }
                $rank = strtolower($args[1]);
                $all = $rankManager->getAllRanks();
                if(!isset($all[$rank]) || !is_array($all[$rank])){
                    $sender->sendMessage("§cRang nicht gefunden.");
                    return true;
                }
                $sender->sendMessage("§eRang {$rank}: " . json_encode($all[$rank], JSON_UNESCAPED_UNICODE));
                return true;

            case "list":
                $sender->sendMessage("§eRänge: " . implode(", ", array_keys($rankManager->getAllRanks())));
                return true;

            default:
                $sender->sendMessage("§e/rang create|setprefix|setmaxplots|permission|setplayer|info|list");
                return true;
        }
    }
}