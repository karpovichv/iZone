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


namespace iZone\Provider;


use iZone\iZone;
use iZone\Zone;
use pocketmine\Player;

class DummyProvider implements DataProvider {

    private $_plugin;

    public function __construct(iZone $plugin)
    {
        $this->_plugin = $plugin;
    }

    public function addZone(Zone $zone)
    {
        return false;
    }

    public function getAllZone()
    {
        return [];
    }

    public function removeZone(Zone $zone)
    {
        return false;
    }

    public function setPermission(Player $player, $permission)
    {
        return false;
    }

    public function getPermissions(Player $player)
    {
        return [];
    }

    public function unsetPermission(Player $player, $permission)
    {
        return false;
    }

    public function close()
    {
    }
}