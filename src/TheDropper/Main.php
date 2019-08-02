<?php

namespace TheDropper;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use TheDropper\teleportBack;

class Main extends PluginBase implements Listener {

    public $mode = 0;
    public $prefix;
    public $signregister = false;
    public $levelname = "";
    public $line3 = "";
    public $line4 = "";
    public $spawnCount = 0;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getLogger()->info("§aActivated.");
        $this->prefix = $this->getConfig()->get("prefix");
    }

    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                if ($this->getConfig()->exists($player->getLevel()->getFolderName())) {
                    $event->setCancelled(true);
                    $block = $player->getLevel()->getBlock(new Vector3($player->x, $player->y, $player->z));
                    if ($block->getId() === Block::WATER) {
                        $current = (int) $this->getConfig()->getNested($player->getLevel()->getFolderName() . ".players." . strtolower($player->getName()));
                        $all = count($this->getConfig()->getNested($player->getLevel()->getFolderName())) - 1;
                        $next = $current + 1;
                        if ($next <= $all) {
                            $this->tpTo($player, $next, $player->getLevel());
                            $player->sendMessage(TextFormat::GRAY . "You're now at Level $next.");
                            $this->getConfig()->setNested($player->getLevel()->getFolderName() . ".players." . strtolower($player->getName()), $next);
                            $this->getConfig()->save();
                        } else {
                            $player->sendMessage(TextFormat::GRAY . "You've done all levels.");
                            $players = $this->getConfig()->getNested($player->getLevel()->getFolderName() . ".players");
                            unset($players[strtolower($player->getName())]);
                            $this->getConfig()->setNested($player->getLevel()->getFolderName() . ".players", $players);
                            $this->getConfig()->save();
                            $this->getServer()->getScheduler()->scheduleDelayedTask(new teleportBack($this, $player, $this->getServer()->getDefaultLevel()->getSafeSpawn()->getX(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getY(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getZ(), $this->getServer()->getDefaultLevel()), 20);
                        }
                    } else {
                        $current = (int) $this->getConfig()->getNested($player->getLevel()->getFolderName() . ".players." . strtolower($player->getName()));
                        $this->tpTo($player, $current, $player->getLevel());
                    }
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $sign = $event->getPlayer()->getLevel()->getTile($block);
        if ($sign instanceof Sign) {
            if ($this->signregister === false) {
                $text = $sign->getText();
                if ($text[0] === $this->prefix) {
                    $level = $text[1];
                    $this->tpTo($player, 1, $this->getServer()->getLevelByName($level));
                    $this->getConfig()->setNested($level . ".players." . strtolower($player->getName()), 1);
                    $this->getConfig()->save();
                    $player->sendMessage(TextFormat::GRAY . "Successfully entered $level.");
                }
            } else {
                $sign->setText($this->prefix, $this->levelname, $this->line3, $this->line4);
                $this->signregister = false;
                $player->sendMessage(TextFormat::GRAY . "Successfully set a sign for " . TextFormat::AQUA . $this->levelname . TextFormat::GRAY . ".");
                $this->levelname = "";
                $this->line3 = "";
                $this->line4 = "";
            }
        } else {
            if ($this->mode != 0) {
                $left = $this->spawnCount - $this->mode;
                $this->getConfig()->setNested($player->getLevel()->getFolderName() . "." . $this->mode, array($block->getX(), $block->getY() + 2, $block->getZ()));
                $this->getConfig()->save();
                if ($left <= 0) {
                    $this->mode = 0;
                    $this->spawncount = 0;
                    $player->sendMessage(TextFormat::GRAY . "The last Spawn has been set. Set the sign using " . TextFormat::AQUA . "/td regsign <worldname>" . TextFormat::GRAY . ".");
                    $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                } else {
                    $player->sendMessage(TextFormat::GRAY . "Spawn $this->mode" . " has been set. $left Spawns left.");
                    $this->mode++;
                }
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
        switch ($cmd->getName()) {
            case "td":
                if (!$sender instanceof Player) {
                    $sender->sendMessage("You have to be a player to perform this command.");
                    return true;
                }
                if (!(isset($args[0]))) {
                    $sender->sendMessage(TextFormat::GRAY . "Usage: " . $cmd->getUsage());
                    return true;
                }
                if (strtolower($args[0]) === "addarena") {
                    if (!(isset($args[1])) or ! (isset($args[2]))) {
                        $sender->sendMessage(TextFormat::GRAY . "Usage: /td addarena <worldname> <anzahl>");
                        return true;
                    }
                    if (file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1])) {
                        $this->getServer()->loadLevel($args[1]);
                        $this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getZ());
                        $this->spawnCount = $args[2];
                        $this->mode = 1;
                        $sender->sendMessage(TextFormat::GRAY . "Tap the spawns now! $this->spawnCount left.");
                        $level = $this->getServer()->getLevelByName($args[1]);
                        $sender->teleport($level->getSafeSpawn());
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::GRAY . "$args[1] isn't a level!");
                        return true;
                    }
                }
                if (strtolower($args[0]) === "regsign") {
                    if ($this->getServer()->getLevelByName($args[1]) instanceof Level) {
                        $this->levelname = $args[1];
                        $this->line3 = isset($args[2]) ? $args[2] : "";
                        $this->line4 = isset($args[3]) ? $args[3] : "";
                        $this->signregister = true;
                        $sender->sendMessage(TextFormat::GRAY . "Tap a sign now!");
                        return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::GRAY . "$args[1] isn't a level!");
                    return true;
                }
        }
    }

    public function tpTo(Player $player, $to, Level $level) {
        $spawns = $this->getConfig()->getNested($level->getFolderName() . "." . $to);
        $x = $spawns[0];
        $y = $spawns[1];
        $z = $spawns[2];
        $this->getServer()->getScheduler()->scheduleDelayedTask(new teleportBack($this, $player, $x, $y, $z, $level), 5);
    }

}
