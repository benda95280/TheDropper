<?php

namespace TheDropper;

// use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;


class teleportBack extends Task {

    public function __construct($plugin, Player $player, $x, $y, $z, $level) {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->level = $level;
    }

    public function onRun($tick) {
        $this->level->loadChunk($this->x, $this->z);
        $this->player->teleport(new Position($this->x + 0.5, $this->y, $this->z + 0.5, $this->level), 0, 0);
		$inv = $this->player->getInventory();
        $inv->clearAll();
        $item = Item::get(358, 0, 1);
        $inv->setItem(0, $item);

    }

}
