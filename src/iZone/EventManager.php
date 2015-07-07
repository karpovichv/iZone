<?php

namespace iZone;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;

class EventManager implements Listener
{
    /** @var iZone */
    private $plugin;

	public function __construct(iZone $base)
	{
        $this->plugin = $base;
	}


    /**
     * @param PlayerJoinEvent $event
     *
     * @priority LOWEST
     * @ignoreCancelled true
     */
    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $this->plugin->addAttachment($event->getPlayer());
    }


    /**
     * @param PlayerQuitEvent $event
     *
     * @priority LOWEST
     * @ignoreCancelled true
     */
    public function onPlayerQuit(PlayerQuitEvent $event)
    {
        $this->plugin->removeAttachment($event->getPlayer());
    }


    /**
     * @param BlockPlaceEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if($player->isOp())
            return;

        foreach($this->plugin->getAllZones() as $zone)
        {
            if($zone->isIn($event->getBlock()))
            {
                if($player->hasPermission($zone->getName() . WORKER))
                    break;

                $event->setCancelled(true);
                $player->sendMessage("[iZone] This is a private area.");
                break;
            }
        }
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event)
    {

        $player = $event->getPlayer();
        if($player->isOp())
            return;

        foreach($this->plugin->getAllZones() as $zone)
        {
            if($zone->isIn($event->getBlock()))
            {
                if($player->hasPermission($zone->getName() . WORKER))
                    break;

                $event->setCancelled(true);
                $player->sendMessage("[iZone] This is a private area.");
                break;
            }
        }
    }


    /**
     * @param EntityExplodeEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function onEntityExplode(EntityExplodeEvent $event)
    {
        if($this->plugin->getConfig()->get("protect-from-explosion", true) != true)
            return;

        $radius = $this->plugin->getConfig()->get("explosion-radius", 8);
        foreach($this->plugin->getAllZones() as $zone)
        {
            if($zone->isOnRadius($event->getPosition(), $radius))
            {
                $owner = $this->plugin->getServer()->getPlayer($zone->getOwner());
                if($owner instanceof Player)
                    $owner->sendMessage('[iZone] Something explode near zone: ' . $zone->getName());

                $event->setCancelled(true);
                break;
            }
        }
        return;



    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        if($player->isOp())
            return;

        foreach ($this->plugin->getAllZones() as $zone) {
            if ($zone->isIn($event->getBlock())) {
                if ($player->hasPermission($zone->getName() . FRIEND))
                    break;

                $event->setCancelled(true);
                $player->sendMessage("[iZone] This is a private area.");
                break;
            }
        }
    }


    /**
     * @param InventoryPickupItemEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPickupItem(InventoryPickupItemEvent $event)
    {
        $player = $event->getInventory()->getHolder();
        if($player instanceof Player and !$player->isOp())
        {
            foreach ($this->plugin->getAllZones() as $zone) {
                if ($zone->isIn($event->getItem())) {
                    if ($player->hasPermission($zone->getName() . FRIEND))
                        break;

                    $event->setCancelled(true);
                    $player->sendMessage("[iZone] This is a private area.");
                    break;
                }
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event)
    {
        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(($damager instanceof Player && !$damager->isOp())&& $damaged instanceof Player)
        {
            foreach ($this->plugin->getAllZones() as $zone)
            {
                if ($zone->isIn($damaged))
                {
                    if ($zone->pvpAvailable)
                        break;

                    $event->setCancelled(true);
                    $damager->sendMessage("[iZone] You are trying to attack in a private zone");
                    break;
                }
            }
        }
    }

}