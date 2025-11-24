<?php

namespace Toxic\checks;

use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Server;
use Toxic\Rift;
use Toxic\Session;

abstract class Check {

    public const PREFIX = TF::DARK_GRAY . "[" . TF::RED . TF::BOLD . "Rift" . TF::RESET . TF::DARK_GRAY . "]" . TF::RESET . " ";

    public static array $enabledChecks = [];
    public static array $allChecks = [];

    abstract public function getSubtype(): string;
    abstract public function getName(): string;
    abstract public function getMaxViolations(): int;
    abstract public function kick(): bool;
    abstract public function getId(): int;
    abstract public function getType(): string;

    public array $flag = [];
    public array $lastFlag = [];

    public static function add(Check $check){
        self::$enabledChecks[] = $check;
        self::$allChecks[] = $check;
    }

    public static function remove(Check $check){
        $key = array_search($check, self::$enabledChecks, true);
        if($key !== false) unset(self::$enabledChecks[$key]);
    }

    public function flag(Player $player, string $type, int $amount = 1, bool $noFlagMessage = false){
        $uuid = $player->getUniqueId()->__toString();
        $id = $this->getId();

        if(!isset($this->flag[$uuid][$id])) $this->flag[$uuid][$id] = 0;
        if(!isset($this->lastFlag[$uuid][$id])) $this->lastFlag[$uuid][$id] = microtime(true);

        $this->flag[$uuid][$id] += $amount;
        $vl = $this->flag[$uuid][$id];

        if($vl > $this->getMaxViolations()){
            $this->emptyFlags($player, $type, $id);

            if(!$noFlagMessage){
                $this->notify($player, true);
                Rift::getInstance()->getLogger()->info(self::PREFIX . TF::WHITE . $player->getName() . " " . TF::DARK_RED . "has been kicked " . TF::AQUA . "[" . $this->getType() . "] " . TF::DARK_PURPLE . $this->getName() . TF::AQUA . "/" . TF::WHITE . $this->getSubtype() . ". " . TF::WHITE . "[x" . TF::BLUE . $vl . TF::WHITE . "]");
            }

            if($this->kick()){
                $player->kick(TF::DARK_GRAY . "You were kicked from the game: " . self::PREFIX . ">> " . TF::GOLD . "Unfair Advantage. " . TF::DARK_GRAY . "[" . TF::DARK_RED . $this->getName() . TF::DARK_GRAY . "]");
            }
            return;
        }

        if(!$noFlagMessage){
            $this->notify($player);
            Rift::getInstance()->getLogger()->info(self::PREFIX . TF::WHITE . $player->getName() . " " . TF::DARK_RED . "has failed " . TF::AQUA . "[" . $this->getType() . "] " . TF::DARK_PURPLE . $this->getName() . TF::AQUA . "/" . TF::WHITE . $this->getSubtype() . ". " . TF::WHITE . "[x" . TF::BLUE . $vl . TF::WHITE . "]");
        }
    }

    public function reward(Player $player, float $amount = 1){
        $uuid = $player->getUniqueId()->__toString();
        $id = $this->getId();

        if(!isset($this->flag[$uuid][$id])) return;

        $this->flag[$uuid][$id] -= $amount;
        if($this->flag[$uuid][$id] < 0) $this->flag[$uuid][$id] = 0;
    }

    public function emptyFlags(Player $player, string $type, int $id){
        $uuid = $player->getUniqueId()->__toString();
        $this->flag[$uuid][$id] = 0;
    }

    public function notify(Player $player, bool $kick = false){
        foreach(Server::getInstance()->getOnlinePlayers() as $staff){
            if(!$this->bypass($staff)){
                $uuid = $player->getUniqueId()->__toString();
                $vl = $this->flag[$uuid][$this->getId()];
                if(!$kick){
                    $staff->sendMessage(self::PREFIX . TF::WHITE . $player->getName() . " " . TF::DARK_RED . "has failed " . TF::AQUA . "[" . $this->getType() . "] " . TF::DARK_PURPLE . $this->getName() . TF::AQUA . "/" . TF::WHITE . $this->getSubtype() . ". " . TF::WHITE . "[x" . TF::BLUE . $vl . TF::WHITE . "]");
                }else{
                    $staff->sendMessage(self::PREFIX . TF::WHITE . $player->getName() . " " . TF::DARK_RED . "has been kicked " . TF::AQUA . "[" . $this->getType() . "] " . TF::DARK_PURPLE . $this->getName() . TF::AQUA . "/" . TF::WHITE . $this->getSubtype() . ". " . TF::WHITE . "[x" . TF::BLUE . $vl . TF::WHITE . "]");
                }
            }
        }
    }

    public function bypass(Player $player): bool{
        $session = Session::get($player);
        if($session !== null){
            if($session->hasCheckAlertsWithBypass()) return false;
        }
        return $player->hasPermission("rift.bypass");
    }
}
