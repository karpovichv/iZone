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
use pocketmine\event\plugin\PluginEvent;

abstract class iZoneEvent extends PluginEvent
{

    private $_zone;

    public function __construct(iZone $plugin, Zone $zone)
    {
        parent::__construct($plugin);
        $this->_zone = $zone;
    }

    public function getZone()
    {
        return $this->_zone;
    }
}