<?php

declare(strict_types=1);

namespace CityBuildPlugin\command;

use CityBuildPlugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class PlotCommand extends Command{

    public function __construct(private Main $plugin){
        parent::__construct("plot", "Plotverwaltung für CityBuild", "/plot <auto|claim|home|info|trust|untrust|list>");
        $this->setPermission("citybuild.plot.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage("§cNur im Spiel nutzbar.");
            return true;
        }

        $sub = strtolower($args[0] ?? "help");
        $plotManager = $this->plugin->getPlotManager();
        $rankManager = $this->plugin->getRankManager();

        if(!in_array($sub, ["auto", "claim", "home", "info", "trust", "untrust", "list"], true)){
            $sender->sendMessage("§eNutze: /plot auto|claim|home|info|trust|untrust|list");
            return true;
        }

        switch($sub){
            case "claim":
                $plot = $plotManager->getPlotAt((int)$sender->getPosition()->getX(), (int)$sender->getPosition()->getZ());
                if($plot === null || !$plotManager->isPlotWorld($sender->getWorld()->getFolderName())){
                    $sender->sendMessage("§cDu bist nicht in der Plotwelt.");
                    return true;
                }
                $owned = $plotManager->countPlayerPlots($sender);
                if($owned >= $rankManager->getMaxPlots($sender) && !$rankManager->hasRankPermission($sender, "citybuild.plot.multiclaim")){
                    $sender->sendMessage("§cPlotlimit erreicht. Erhöhe es mit einem Rang.");
                    return true;
                }
                if($plotManager->claim($sender, $plot["id"])){
                    $sender->sendMessage("§aPlot {$plot["id"]} erfolgreich geclaimt.");
                }else{
                    $sender->sendMessage("§cDieses Plot gehört bereits jemandem.");
                }
                return true;

            case "auto":
                $owned = $plotManager->countPlayerPlots($sender);
                if($owned >= $rankManager->getMaxPlots($sender) && !$rankManager->hasRankPermission($sender, "citybuild.plot.multiclaim")){
                    $sender->sendMessage("§cPlotlimit erreicht. Erhöhe es mit einem Rang.");
                    return true;
                }
                $plotId = $plotManager->getFirstFreePlotId();
                if($plotId === null){
                    $sender->sendMessage("§cKeine freien Plots mehr verfügbar.");
                    return true;
                }
                $plotManager->claim($sender, $plotId);
                $pos = $plotManager->getPlotCenter($plotId);
                if($pos !== null){
                    $sender->teleport($pos);
                }
                $sender->sendMessage("§aDu hast Plot {$plotId} erhalten.");
                return true;

            case "home":
                $owned = $plotManager->getOwnedPlots($sender);
                if(count($owned) === 0){
                    $sender->sendMessage("§cDu besitzt kein Plot.");
                    return true;
                }
                $plotId = $args[1] ?? $owned[0];
                if(!in_array($plotId, $owned, true) && !$sender->isOp()){
                    $sender->sendMessage("§cDieses Plot gehört nicht dir.");
                    return true;
                }
                $pos = $plotManager->getPlotCenter($plotId);
                if($pos === null){
                    $sender->sendMessage("§cPlotwelt ist nicht geladen.");
                    return true;
                }
                $sender->teleport($pos);
                $sender->sendMessage("§aTeleportiert zu Plot {$plotId}.");
                return true;

            case "info":
                $plot = $plotManager->getPlotAt((int)$sender->getPosition()->getX(), (int)$sender->getPosition()->getZ());
                if($plot === null){
                    $sender->sendMessage("§cDu stehst außerhalb der Plotwelt.");
                    return true;
                }
                $owner = $plotManager->getOwner($plot["id"]);
                $sender->sendMessage("§ePlot: {$plot["id"]} | Besitzer UUID: " . ($owner ?? "frei"));
                return true;

            case "trust":
            case "untrust":
                $targetName = $args[1] ?? "";
                if($targetName === ""){
                    $sender->sendMessage("§cNutze: /plot {$sub} <Spieler>");
                    return true;
                }
                $target = $this->plugin->getServer()->getPlayerExact($targetName);
                if($target === null){
                    $sender->sendMessage("§cSpieler muss online sein.");
                    return true;
                }
                $plot = $plotManager->getPlotAt((int)$sender->getPosition()->getX(), (int)$sender->getPosition()->getZ());
                if($plot === null){
                    $sender->sendMessage("§cDu bist auf keinem Plot.");
                    return true;
                }
                $ok = $sub === "trust"
                    ? $plotManager->trust($sender, $target, $plot["id"])
                    : $plotManager->untrust($sender, $target, $plot["id"]);
                $sender->sendMessage($ok ? "§aErfolgreich ausgeführt." : "§cAktion nicht möglich.");
                return true;

            case "list":
                $owned = $plotManager->getOwnedPlots($sender);
                $sender->sendMessage("§eDeine Plots: " . (count($owned) > 0 ? implode(", ", $owned) : "keine"));
                return true;
        }

        return true;
    }
}