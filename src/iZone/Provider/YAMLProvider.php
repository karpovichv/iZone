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
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\Config;

class YAMLProvider implements DataProvider
{
    /** @var iZone _plugin */
    private $_plugin;

    /**
     * @var Config
     */
    private $permConfig;

    /**
     * @var Config
     */
    private $zonesConfig;

    public function __construct(iZone $plugin)
    {
        $this->_plugin = $plugin;
        $this->zonesConfig = new Config($plugin->getDataFolder() . "zones.yml", Config::YAML);
        $this->permConfig = new Config($plugin->getDataFolder() . "permissions.yml", Config::YAML);
    }

    public function addZone(Zone $zone)
    {
        $this->zonesConfig->set(spl_object_hash($zone),
            [
                $zone->getName(),
                $zone->getOwner(),
                $zone->getLevelName(),
                $zone->getPosition()
            ]
        );
    }

    public function removeZone(Zone $zone)
    {
        $this->zonesConfig->remove(spl_object_hash($zone));
    }

    public function setPermission(Player $player, $permission)
    {
        $data = $this->permConfig->get($player->getName() . ".permissions", []);
        $data[] = $permission;
        $this->permConfig->set($player->getName() .  ".permissions", $data);
    }

    public function unsetPermission(Player $player, $permission)
    {
        $data = $this->permConfig->get($player->getName() . ".permissions", []);
        if(count($data) == 0 || array_search($permission, $data) === false)
            return;

        unset($data[array_search($permission, $data)]);

    }

    public function close()
    {
        $this->zonesConfig->save(false);
        $this->permConfig->save(false);
    }

    public function getPermissions(Player $player)
    {
        return $this->permConfig->get($player->getName() . ".permissions", []);
    }

    public function getAllZone()
    {
        $data = $this->zonesConfig->getAll(true);
        $zones = [];
        foreach($data as $key => $value)
        {
            $level = $this->_plugin->getServer()->getLevelByName($value[3]);
            if($level == null)
                continue;

            $pos1 = new Position($value[4][0], $value[4][1], $value[4][2], $level);
            $pos2 = new Position($value[4][3], $value[4][4], $value[4][5], $level);
            $zones[$value[0]] = new Zone($this->_plugin, $value[0], $value[1], $pos1, $pos2);
        }
        return $zones;
    }
}