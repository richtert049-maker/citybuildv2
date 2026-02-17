<?php

declare(strict_types=1);

namespace CityBuildPlugin;

use pocketmine\player\Player;
use pocketmine\utils\Config;

final class RankManager{

    public function __construct(
        private Main $plugin,
        private Config $ranksConfig,
        private Config $playersConfig
    ){}

    public function createRank(string $name, string $prefix, int $maxPlots) : bool{
        $id = strtolower($name);
        if($this->ranksConfig->exists($id)){
            return false;
        }
        $this->ranksConfig->set($id, [
            "prefix" => $prefix,
            "max_plots" => max(1, $maxPlots),
            "permissions" => []
        ]);
        $this->ranksConfig->save();
        return true;
    }

    public function setPrefix(string $rank, string $prefix) : bool{
        $data = $this->ranksConfig->get(strtolower($rank), null);
        if(!is_array($data)){
            return false;
        }
        $data["prefix"] = $prefix;
        $this->ranksConfig->set(strtolower($rank), $data);
        $this->ranksConfig->save();
        return true;
    }

    public function setMaxPlots(string $rank, int $value) : bool{
        $data = $this->ranksConfig->get(strtolower($rank), null);
        if(!is_array($data)){
            return false;
        }
        $data["max_plots"] = max(1, $value);
        $this->ranksConfig->set(strtolower($rank), $data);
        $this->ranksConfig->save();
        return true;
    }

    public function addPermission(string $rank, string $permission) : bool{
        $data = $this->ranksConfig->get(strtolower($rank), null);
        if(!is_array($data)){
            return false;
        }
        $permissions = $data["permissions"] ?? [];
        if(in_array($permission, $permissions, true)){
            return false;
        }
        $permissions[] = $permission;
        $data["permissions"] = $permissions;
        $this->ranksConfig->set(strtolower($rank), $data);
        $this->ranksConfig->save();
        return true;
    }

    public function removePermission(string $rank, string $permission) : bool{
        $data = $this->ranksConfig->get(strtolower($rank), null);
        if(!is_array($data)){
            return false;
        }
        $permissions = array_values(array_filter($data["permissions"] ?? [], static fn(string $item) : bool => $item !== $permission));
        $data["permissions"] = $permissions;
        $this->ranksConfig->set(strtolower($rank), $data);
        $this->ranksConfig->save();
        return true;
    }

    public function setPlayerRank(string $playerName, string $rank) : bool{
        if(!$this->ranksConfig->exists(strtolower($rank))){
            return false;
        }
        $this->playersConfig->set(strtolower($playerName), strtolower($rank));
        $this->playersConfig->save();
        return true;
    }

    public function getPlayerRank(Player|string $player) : string{
        $name = $player instanceof Player ? $player->getName() : $player;
        return (string)$this->playersConfig->get(strtolower($name), "default");
    }

    public function getPrefix(Player|string $player) : string{
        $rank = $this->getPlayerRank($player);
        $data = $this->ranksConfig->get(strtolower($rank), []);
        return is_array($data) ? (string)($data["prefix"] ?? "§7[Spieler]") : "§7[Spieler]";
    }

    public function getMaxPlots(Player $player) : int{
        $rank = $this->getPlayerRank($player);
        $data = $this->ranksConfig->get(strtolower($rank), []);
        if(!is_array($data)){
            return 1;
        }
        return max(1, (int)($data["max_plots"] ?? 1));
    }

    public function hasRankPermission(Player $player, string $permission) : bool{
        if($player->isOp()){
            return true;
        }

        $rank = $this->getPlayerRank($player);
        $data = $this->ranksConfig->get(strtolower($rank), []);
        if(!is_array($data)){
            return false;
        }

        return in_array($permission, $data["permissions"] ?? [], true);
    }

    public function getAllRanks() : array{
        return $this->ranksConfig->getAll();
    }

    public function rankExists(string $rank) : bool{
        return $this->ranksConfig->exists(strtolower($rank));
    }
}