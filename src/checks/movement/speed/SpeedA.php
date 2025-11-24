<?php

namespace Toxic\checks\movement\speed;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use Toxic\checks\Check;
use Toxic\Session;
use Toxic\utils\Blocks;
use Toxic\utils\Maths;

class SpeedA extends Check
{
    public function getId(): int
    {
        return 0;
    }

    public function getName(): string
    {
        return "Speed";
    }

    public function getMaxViolations(): int
    {
        return 5;
    }

    public function getSubtype(): string
    {
        return "A";
    }

    public function getType(): string
    {
        return "Movement";
    }

    public function kick(): bool
    {
        return true;
    }

    public function onMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);
        if ($session == null)
            return;

        $from = $event->getFrom();
        $to = $event->getTo();

        // Basic sanity checks


        // #REVIEW
        // this is actually so shi and lazy, like lets be real aint no one gonna be taking 40 ticks to teleport
        // ez BYPASS
        if ($session->getTeleportTicks() < 40)
            return;
        // what in the actual hell? why no proper acknolegment on this, EZ BYPASS 
        if ($session->getMotionTicks() < 40)
            return;
        // okay this is bs 🙏, we already got motion tf we need to account here aswell
        if ($session->getAttackTicks() < 40)
            return;
        // i mean kinda understandable, but why not use Player->isFlying
        if ($session->getFlightTicks() < 40)
            return;
        // i mean kinda understandable, but why not use Player->isGliding
        if ($session->getGlideTicks() < 40)
            return;

        // TF?, bypasses mate, bypasses
        if ($to->y > $from->y)
            return;

        if (Blocks::isOnStairs($to, 0) || Blocks::isOnStairs($to, 1))
            return;

        $dx = $to->getX() - $from->getX();
        $dz = $to->getZ() - $from->getZ();
        $horizontalDist = sqrt($dx * $dx + $dz * $dz);

        $speed = $horizontalDist / 0.1;

        $isSprinting = $player->isSprinting();
        $isJumping = $session->isJumping();

        $maxSpeed = match (true) {
            $isSprinting && $isJumping => 12.2,  // original value: 13.1 (kept incase for future)
            !$isSprinting && !$isJumping => 7.9, // original value: 8.4
            !$isSprinting && $isJumping => 7.5,  // original value: 8.0
            $isSprinting && !$isJumping => 10.2, // original value: 11.1
            default => 9.0
        };

        // Reward system: reduce VL if well under limit
        if ($speed <= ($maxSpeed * 0.70)) {
            $session->reduceViolation($this->getName(), $this->getSubtype(), 0.25);
            return;
        }

        // Flag if exceeding threshold
        if ($speed > $maxSpeed) {
            $this->flag($player, "Speed = $speed / $maxSpeed", 1, false);
        } else {
            $this->reward($player, );
        }
    }
}