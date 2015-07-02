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
    public function addZone(Zone $zone);

    public function removeZone(Zone $zone);

    public function setPermission(Player $player, $permission);

    public function unsetPermission(Player $player, $permission);

    public function close();
}