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

use iZone\Zone;
use pocketmine\Player;

interface DataProvider
{
    /**
     * @param Zone $zone
     * @return bool
     */
    public function addZone(Zone $zone);

    /**
     * @return Zone[]
     */
    public function getAllZone();

    /**
     * @param Zone $zone
     * @return bool
     */
    public function removeZone(Zone $zone);

    /**
     * @param Player $player
     * @param $permission
     * @return bool
     */
    public function setPermission(Player $player, $permission);

    /**
     * @param Player $player
     * @return string[]
     */
    public function getPermissions(Player $player);

    /**
     * @param Player $player
     * @param $permission
     * @return bool
     */
    public function unsetPermission(Player $player, $permission);

    /**
     * @return
     */
    public function close();
}