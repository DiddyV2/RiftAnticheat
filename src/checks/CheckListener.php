<?php

namespace Toxic\checks;

use pocketmine\event\Listener;

class CheckListener implements Listener {

    private array $checkCache = [];

    public function getChecks(): array{
        return Check::$enabledChecks;
    }

    // talking abt dispatch, the game "dispatch" is hella good, anyone reading this should play it.
    private function dispatch(string $method, $event): void {
        foreach ($this->checkCache as $obj){
            if ($obj["methods"][$method] ?? false){
                $obj["check"]->$method($event);
            }
        }
    }

    private function buildCache(): void {
        $this->checkCache = [];
        foreach ($this->getChecks() as $check){
            $methods = [];
            foreach (get_class_methods($check) as $m){
                $methods[$m] = true;
            }
            $this->checkCache[] = ["check" => $check, "methods" => $methods];
        }
    }

    public function __construct(){
        $this->buildCache();
    }

    public function reloadCache(): void {
        $this->buildCache();
    }

    public function movement($e){ $this->dispatch('onMove', $e); }
    public function interact($e){ $this->dispatch('onInteract', $e); }
    public function consume($e){ $this->dispatch('onConsume', $e); }
    public function quit($e){ $this->dispatch('onQuit', $e); }
    public function death($e){ $this->dispatch('onDeath', $e); $this->dispatch('onPlayerDeath', $e); }
    public function respawn($e){ $this->dispatch('onRespawn', $e); }
    public function kick($e){ $this->dispatch('onKick', $e); }
    public function jump($e){ $this->dispatch('onJump', $e); }
    public function toggleS($e){ $this->dispatch('onToggleSwim', $e); }
    public function toggleG($e){ $this->dispatch('onToggleGlide', $e); }
    public function toggleB($e){ $this->dispatch('onToggleBed', $e); }
    public function damage($e){ $this->dispatch('onDamage', $e); }
    public function damageB($e){ $this->dispatch('onDamageBy', $e); $this->dispatch('onDamage', $e); }
    public function regen($e){ $this->dispatch('onRegen', $e); }
    public function teleport($e){ $this->dispatch('onTeleport', $e); }
    public function projectileh($e){ $this->dispatch('onProjectileHit', $e); }
    public function place($e){ $this->dispatch('onPlace', $e); }
    public function break($e){ $this->dispatch('onBreak', $e); }
    public function packets($e){ $this->dispatch('onReceivePackets', $e); }
    public function invO($e){ $this->dispatch('invOpen', $e); }
    public function invC($e){ $this->dispatch('invClose', $e); }
    public function invI($e){ $this->dispatch('invTransaction', $e); }
}
