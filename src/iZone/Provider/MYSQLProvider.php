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
use iZone\Task\MySQLiTask;
use iZone\Zone;
use pocketmine\level\Position;
use pocketmine\Player;

class MYSQLProvider implements DataProvider
{
    private $_plugin;
    private $database;

    public function __construct(iZone $plugin)
    {
        $this->_plugin = $plugin;
        $config = $plugin->getConfig()->get("dataProviderSettings", []);

        if(!isset($config["host"]) or !isset($config["user"]) or !isset($config["password"]) or !isset($config["database"])){
            $this->_plugin->getLogger()->critical("Invalid MySQL settings");
            $this->_plugin->setDataProvider(new DummyProvider($this->_plugin));
            return;
        }

        $this->database = new \mysqli($config["host"], $config["user"], $config["password"], $config["database"], isset($config["port"]) ? $config["port"] : 3306);
        if($this->database->connect_error){
            $this->_plugin->getLogger()->critical("Couldn't connect to MySQL: ". $this->database->connect_error);
            $this->_plugin->setDataProvider(new DummyProvider($this->_plugin));
            return;
        }

        $resource = $this->_plugin->getResource("mysqli.sql");
        $this->database->query(stream_get_contents($resource));
        fclose($resource);

        $this->_plugin->getServer()->getScheduler()->scheduleRepeatingTask(new MySQLiTask($this->_plugin, $this->database), 600); //Each 30 seconds
        $this->_plugin->getLogger()->info("Connected to MySQL server");
    }

    public function addZone(Zone $zone)
    {
        $position = $zone->getPosition();

        $name = $this->database->escape_string($zone->getName());
        $player = $this->database->escape_string($zone->getOwner());
        $level = $zone->getLevelName();
        $minx = $position[0];
        $miny = $position[1];
        $minz = $position[2];
        $maxx = $position[3];
        $maxy = $position[4];
        $maxz = $position[5];

        return $this->database->query("INSERT INTO izone_zones (name, player_owner, level_name, minX, minY, minZ, maxX, maxY, maxZ) VALUES ({$name}, {$player}, {$level}, {$minx}, {$miny}, {$minz}, {$maxx}, {$maxy}, {$maxz})");

    }

    public function removeZone(Zone $zone)
    {
        $name = $this->database->escape_string($zone->getName());
        if(!$this->database->query("DELETE FROM izone_zones WHERE name = {$name}"))
            return false;

        $zoneName = $this->database->escape_string($zone->getName());
        return $this->database->query("DELETE FROM izone_player_permissions WHERE zone_name = {$zoneName}");

    }

    public function getAllZone()
    {
        $result = $this->database->query("SELECT * FROM izone_zones");
        if($result instanceof \mysqli_result)
        {
            $zones = [];
            $data = $result->fetch_assoc();
            $result->close();
            foreach($data as $zone)
            {
                $level = $this->_plugin->getServer()->getLevelByName($zone["level_name"]);
                if($level == null)
                    continue;

                $pos1 = new Position($zone["minX"], $zone["minY"], $zone["minZ"], $level);
                $pos2 = new Position($zone["maxX"], $zone["maxY"], $zone["maxZ"], $level);
                $zones[$zone["name"]] = new Zone($this->_plugin, $zone["name"], $zone["player_owner"], $pos1, $pos2);
            }
            return $zones;
        }
        return [];
    }

    public function setPermission(Player $player, $permission)
    {
        $zoneName = explode(".", $permission)[0];
        $player = $this->database->escape_string($player->getName());
        $permission = $this->database->escape_string($permission);
        return $this->database->query("INSERT INTO izone_player_permissions (player_name, zone_name permission_name) VALUES ({$player}, {$zoneName}, {$permission})");
    }

    public function unsetPermission(Player $player, Zone $zone)
    {
        $player = $this->database->escape_string($player->getName());
        $zoneName = $this->database->escape_string($zone->getName());
        return $this->database->query("DELETE FROM izone_player_permissions WHERE player_name = {$player} AND zone_name = {$zoneName}");
    }

    public function getPermissions(Player $player)
    {
        $playerName = $this->database->escape_string($player->getName());
        $result = $this->database->query("SELECT permission_name FROM izone_player_permissions WHERE player_name = {$playerName}");
        if($result instanceof \mysqli_result)
        {
            $data = $result->fetch_all(MYSQLI_NUM);
            $result->close();
            return $data;
        }
        return [];
    }

    public function close()
    {
        $this->database->close();
    }
}