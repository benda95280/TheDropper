<?php

namespace TheDropper;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\level\Position;

class teleportBack extends PluginTask {

    public function __construct($plugin, Player $player, $x, $y, $z, $level) {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->level = $level;
        parent::__construct($plugin, $player);
    }

    public function onRun($tick) {
        $this->level->loadChunk($this->x, $this->z);
        $this->player->teleport(new Position($this->x + 0.5, $this->y, $this->z + 0.5, $this->level), 0, 0);
    }

}
