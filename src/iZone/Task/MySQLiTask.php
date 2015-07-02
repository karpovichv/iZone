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


namespace iZone\Task;


use iZone\iZone;
use pocketmine\scheduler\PluginTask;

class MySQLiTask extends PluginTask
{

    private $_database;

    public function __construct(iZone $plugin, \mysqli $database)
    {
        parent::__construct($plugin);
        $this->_database = $database;
    }

    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     */
    public function onRun($currentTick)
    {
        $this->_database->ping();
    }
}