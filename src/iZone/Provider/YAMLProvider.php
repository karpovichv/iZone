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
        $this->zonesConfig->set($zone->getName(),
            [
                $zone->getName(),
                $zone->getOwner(),
                $zone->getLevelName(),
                $zone->getPosition(),
                $zone->pvpAvailable
            ]
        );
    }

    public function removeZone(Zone $zone)
    {
        $this->zonesConfig->remove($zone->getName());
        $permData = $this->permConfig->getAll(false);
        foreach($permData as $key => $value)
        {
            if(array_key_exists($zone->getName(), $value))
            {
                unset($permData[$key][$zone->getName()]);
                $this->permConfig->setAll($permData);
                return true;
            }
        }
        
        return false;
    }

    public function setPermission(Player $player, $permission)
    {
        $zone =  explode(".", $permission)[0];
        $data = $this->permConfig->get($player->getName() . ".permissions", []);
        $data[$zone] = $permission;
        $this->permConfig->set($player->getName() .  ".permissions", $data);
        return true;
    }

    public function unsetPermission(Player $player, Zone $zone)
    {
        $zoneName = $zone->getName();
        $data = $this->permConfig->get($player->getName() . ".permissions", []);
        if(count($data) == 0 || !array_key_exists($zoneName, $data))
            return false;

        unset($data[$zoneName]);
        $this->permConfig->set($player->getName() .  ".permissions", $data);
        return true;

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
        $data = $this->zonesConfig->getAll(false);
        $zones = [];
        foreach($data as $id => $value)
        {
            $level = $this->_plugin->getServer()->getLevelByName($value[2]);
            if($level == null)
                continue;

            $pos1 = new Position($value[3][0], $value[3][1], $value[3][2], $level);
            $pos2 = new Position($value[3][3], $value[3][4], $value[3][5], $level);
            $zones[$value[0]] = new Zone($this->_plugin, $value[0], $value[1], $pos1, $pos2, $value[4]);
        }
        return $zones;
    }
}
