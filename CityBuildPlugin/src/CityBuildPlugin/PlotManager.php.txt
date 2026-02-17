<?php

declare(strict_types=1);

namespace CityBuildPlugin;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\Position;

final class PlotManager{

    public function __construct(
        private Main $plugin,
        private Config $plotsConfig,
        private string $worldName,
        private int $worldSize,
        private int $plotSize
    ){}

    public function isPlotWorld(string $worldName) : bool{
        return strtolower($this->worldName) === strtolower($worldName);
    }

    public function getPlotAt(int $x, int $z) : ?array{
        $half = (int)($this->worldSize / 2);
        $startX = -$half;
        $startZ = -$half;

        if($x < $startX || $z < $startZ || $x >= $startX + $this->worldSize || $z >= $startZ + $this->worldSize){
            return null;
        }

        $plotX = intdiv($x - $startX, $this->plotSize);
        $plotZ = intdiv($z - $startZ, $this->plotSize);

        return ["x" => $plotX, "z" => $plotZ, "id" => $this->plotId($plotX, $plotZ)];
    }

    public function getOwner(string $plotId) : ?string{
        $data = $this->plotsConfig->get($plotId, null);
        if(!is_array($data)){
            return null;
        }
        return $data["owner"] ?? null;
    }

    public function claim(Player $player, string $plotId) : bool{
        if($this->getOwner($plotId) !== null){
            return false;
        }

        $this->plotsConfig->set($plotId, [
            "owner" => strtolower($player->getUniqueId()->toString()),
            "trusted" => []
        ]);
        $this->plotsConfig->save();
        return true;
    }

    public function isOwnerOrTrusted(Player $player, string $plotId) : bool{
        if($player->hasPermission("citybuild.plot.admin")){
            return true;
        }
        $plot = $this->plotsConfig->get($plotId, null);
        if(!is_array($plot)){
            return false;
        }
        $uuid = strtolower($player->getUniqueId()->toString());
        if(($plot["owner"] ?? "") === $uuid){
            return true;
        }
        return in_array($uuid, $plot["trusted"] ?? [], true);
    }

    public function countPlayerPlots(Player $player) : int{
        $uuid = strtolower($player->getUniqueId()->toString());
        $count = 0;
        foreach($this->plotsConfig->getAll() as $plot){
            if(is_array($plot) && ($plot["owner"] ?? "") === $uuid){
                $count++;
            }
        }
        return $count;
    }

    public function getFirstFreePlotId() : ?string{
        $plotsPerSide = intdiv($this->worldSize, $this->plotSize);
        for($x = 0; $x < $plotsPerSide; $x++){
            for($z = 0; $z < $plotsPerSide; $z++){
                $id = $this->plotId($x, $z);
                if($this->getOwner($id) === null){
                    return $id;
                }
            }
        }
        return null;
    }

    public function getOwnedPlots(Player $player) : array{
        $uuid = strtolower($player->getUniqueId()->toString());
        $owned = [];
        foreach($this->plotsConfig->getAll() as $plotId => $plot){
            if(is_array($plot) && ($plot["owner"] ?? "") === $uuid){
                $owned[] = (string)$plotId;
            }
        }
        return $owned;
    }

    public function trust(Player $owner, Player $target, string $plotId) : bool{
        $plot = $this->plotsConfig->get($plotId, null);
        if(!is_array($plot)){
            return false;
        }
        if(($plot["owner"] ?? "") !== strtolower($owner->getUniqueId()->toString())){
            return false;
        }

        $trusted = $plot["trusted"] ?? [];
        $uuid = strtolower($target->getUniqueId()->toString());
        if(in_array($uuid, $trusted, true)){
            return false;
        }
        $trusted[] = $uuid;
        $plot["trusted"] = $trusted;
        $this->plotsConfig->set($plotId, $plot);
        $this->plotsConfig->save();
        return true;
    }

    public function untrust(Player $owner, Player $target, string $plotId) : bool{
        $plot = $this->plotsConfig->get($plotId, null);
        if(!is_array($plot)){
            return false;
        }
        if(($plot["owner"] ?? "") !== strtolower($owner->getUniqueId()->toString())){
            return false;
        }

        $uuid = strtolower($target->getUniqueId()->toString());
        $trusted = array_values(array_filter($plot["trusted"] ?? [], static fn(string $item) : bool => $item !== $uuid));
        $plot["trusted"] = $trusted;
        $this->plotsConfig->set($plotId, $plot);
        $this->plotsConfig->save();
        return true;
    }

    public function getPlotCenter(string $plotId) : ?Position{
        [$plotX, $plotZ] = array_map("intval", explode(":", $plotId));
        $half = (int)($this->worldSize / 2);
        $minX = -$half + ($plotX * $this->plotSize);
        $minZ = -$half + ($plotZ * $this->plotSize);

        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->worldName);
        if($world === null){
            return null;
        }

        $centerX = $minX + intdiv($this->plotSize, 2);
        $centerZ = $minZ + intdiv($this->plotSize, 2);
        $y = $world->getHighestBlockAt($centerX, $centerZ) + 2;

        return new Position($centerX + 0.5, $y, $centerZ + 0.5, $world);
    }

    private function plotId(int $x, int $z) : string{
        return $x . ":" . $z;
    }
}