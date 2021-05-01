<?php

declare(strict_types=1);

namespace brokiem\swimming;

use brokiem\swimming\event\PlayerToggleSwimEvent;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Swimming extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getPlayer();

        if ($packet instanceof PlayerActionPacket) {
            if ($packet->action === PlayerActionPacket::ACTION_START_SWIMMING) {
                $this->toggleSwim($player, true);
            } elseif ($packet->action === PlayerActionPacket::ACTION_STOP_SWIMMING) {
                $this->toggleSwim($player, false);
            }
        }
    }

    public function toggleSwim(Player $player, bool $swim): bool {
        $ev = new PlayerToggleSwimEvent($player, $swim);
        if (!$player->isUnderwater()) {
            $ev->setCancelled();
        }

        $ev->call();
        if ($ev->isCancelled()) {
            return false;
        }

        $this->setSwimming($player, $swim);
        return true;
    }

    public function setSwimming(Player $player, bool $value = true): void {
        if ($value !== $this->isSwimming($player)) {
            $player->setGenericFlag(Entity::DATA_FLAG_SWIMMING, $value);
        }
    }

    public function isSwimming(Player $player): bool {
        return $player->getGenericFlag(Entity::DATA_FLAG_SWIMMING);
    }

    public function onPlayerRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();

        $this->setSwimming($player, false);
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();

        if ($this->isSwimming($player)) {
            $distance = sqrt((($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2));
            $player->exhaust(0.015 * $distance, PlayerExhaustEvent::CAUSE_SWIMMING);

            if (!$player->isUnderwater()) {
                $this->setSwimming($player, false);
            }
        }
    }
}