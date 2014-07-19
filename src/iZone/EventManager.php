<?php

namespace iZone;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\player\PlayerInteractEvent;

class EventManager implements Listener
{
    private $plugin;

	public function __construct(MainClass $base)
	{
        $this->plugin = $base;
	}


    /**
     * @param BlockPlaceEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function OnBlockPlace(BlockPlaceEvent $event)
    {
        $list = &$this->plugin->getAllZones();
        $player = $event->getPlayer();

        foreach($list as $zone)
        {

            if($zone->isIn($event->getBlock()))
            {
                if( $zone->getPermission($player) > SEE_PERM)
                    break;

                $event->setCancelled();
                $event->getPlayer()->sendMessage("[iZone] This is a private area.");
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
    public function OnBlockBreak(BlockBreakEvent $event)
    {
        $list = &$this->plugin->getAllZones();
        $player = $event->getPlayer();

        foreach($list as $zone)
        {

            if($zone->isIn($event->getBlock()))
            {
                if( $zone->getPermission($player) > SEE_PERM)
                    break;

                $event->setCancelled();
                $event->getPlayer()->sendMessage("[iZone] This is a private area.");
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
    public function OnEntityExplode(EntityExplodeEvent $event)
    {
        if($this->plugin->getConfig()->get("protect-from-exploxion") != true)
            return false;

        $radius = $this->plugin->getConfig()->get("explosion-radius");

        foreach($this->plugin->getAllZones() as $v)
        {
            if($v->isOnRadius($event->getPosition(), $radius))
            {
               $v->getOwner()->sendMessage($this->plugin->getConfig()->get('[iZone] Someone is trying to blow up you private area.'));
                $event->setCancelled();
                break;
            }
        }
        return true;
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function OnPlayerInteract(PlayerInteractEvent $event)
    {
        $list = $this->plugin->getAllZones();
        $player = $event->getPlayer();

        foreach($list as $zone)
        {
            if($zone->isIn($event->getBlock()))
            {
                if($zone->getPermission($player) > WORK_PERM)
                    break;

                $event->setCancelled();
                $event->getPlayer()->sendMessage("[iZone] This is a private area.");
                break;
            }

        }
    }

}