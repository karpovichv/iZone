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
use pocketmine\Player;

class ZoneRemovedEvent extends iZoneEvent
{
    private $_remover;

    public function __construct(iZone $plugin, Zone $zone, Player $remover)
    {
        parent::__construct($plugin, $zone);
        $this->_remover = $remover;
    }

    /**
     * @return Player
     */
    public function getRemover()
    {
        return $this->_remover;
    }

}