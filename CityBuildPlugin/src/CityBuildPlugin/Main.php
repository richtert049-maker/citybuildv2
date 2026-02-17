<?php

declare(strict_types=1);

namespace CityBuildPlugin;

use CityBuildPlugin\command\PlotCommand;
use CityBuildPlugin\command\RankCommand;
use CityBuildPlugin\listener\ChatAndProtectionListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\WorldCreationOptions;

final class Main extends PluginBase{

    private const PLOT_WORLD_NAME = "plotwelt";
    private const PLOT_WORLD_SIZE = 2500;
    private const PLOT_SIZE = 25;

    private PlotManager $plotManager;
    private RankManager $rankManager;

    public function onEnable() : void{
        @mkdir($this->getDataFolder());

        $this->saveResource("config.yml");
        $this->loadConfigs();

        $worldManager = $this->getServer()->getWorldManager();
        if(!$worldManager->isWorldGenerated(self::PLOT_WORLD_NAME)){
            $worldManager->generateWorld(self::PLOT_WORLD_NAME, WorldCreationOptions::create());
        }
        $worldManager->loadWorld(self::PLOT_WORLD_NAME);

        $this->rankManager = new RankManager(
            $this,
            new Config($this->getDataFolder() . "ranks.yml", Config::YAML, [
                "default" => [
                    "prefix" => "§7[Spieler]",
                    "max_plots" => 1,
                    "permissions" => []
                ],
                "vip" => [
                    "prefix" => "§a[VIP]",
                    "max_plots" => 2,
                    "permissions" => ["citybuild.plot.multiclaim"]
                ]
            ]),
            new Config($this->getDataFolder() . "players.yml", Config::YAML, [])
        );

        $this->plotManager = new PlotManager(
            $this,
            new Config($this->getDataFolder() . "plots.yml", Config::YAML, []),
            self::PLOT_WORLD_NAME,
            self::PLOT_WORLD_SIZE,
            self::PLOT_SIZE
        );

        $this->getServer()->getCommandMap()->register("citybuild", new PlotCommand($this));
        $this->getServer()->getCommandMap()->register("citybuild", new RankCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new ChatAndProtectionListener($this), $this);

        $world = $worldManager->getWorldByName(self::PLOT_WORLD_NAME);
        if($world !== null){
            $this->getServer()->setDefaultLevel($world);
        }

        $this->getLogger()->info("CityBuildPlugin aktiviert. Plotwelt: 2500x2500, Plotgröße: 25x25.");
    }

    private function loadConfigs() : void{
        $configPath = $this->getDataFolder() . "config.yml";
        if(!is_file($configPath)){
            $default = [
                "plot_world" => self::PLOT_WORLD_NAME,
                "plot_world_size" => self::PLOT_WORLD_SIZE,
                "plot_size" => self::PLOT_SIZE,
                "spawn" => [
                    "x" => 0,
                    "y" => 100,
                    "z" => 0
                ]
            ];
            (new Config($configPath, Config::YAML, $default))->save();
        }
    }

    public function getPlotManager() : PlotManager{
        return $this->plotManager;
    }

    public function getRankManager() : RankManager{
        return $this->rankManager;
    }

}

