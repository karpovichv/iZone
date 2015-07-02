<?php
/*
 * iZone
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author InusualZ
 *
 */


namespace iZone\Event;


use iZone\iZone;
use iZone\Zone;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class ZoneSetPermissionEvent extends iZoneEvent implements Cancellable
{
    public static $handlerList = null;

    private $_player;
    private $_permission;

    public function __construct(iZone $plugin, Zone $zone, Player $player, $permission)
    {
        parent::__construct($plugin, $zone);
        $this->_player = $player;
        $this->_permission = $permission;

    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->_player;
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->_permission;
    }
}