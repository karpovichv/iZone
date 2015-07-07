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

class SQLProvider implements DataProvider
{

    private $database;

    private $plugin;

    public function __construct(iZone $plugin)
    {
        $this->plugin = $plugin;

        if(!file_exists($plugin->getDataFolder() . "Database.db"))
        {
            $this->database = new \SQLite3($plugin->getDataFolder() . "Database.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $resource = $this->plugin->getResource("sqlite3.sql");
            $this->database->exec(stream_get_contents($resource));
            fclose($resource);
        }
        else
        {
            $this->database = new \SQLite3($plugin->getDataFolder() . "Database.db", SQLITE3_OPEN_READWRITE);
        }
    }

    public function addZone(Zone $zone)
    {
        $position = $zone->getPosition();

        $prepare = $this->database->prepare("INSERT INTO Zones (name, player_owner, level_name, minX, minY, minZ, maxX, maxY, maxZ, pvpAvailable) VALUES (:name, :player, :level, :minx, :miny, :minz, :maxx, :maxy, :maxz, :pvp)");
        $prepare->bindValue(":name", \SQLite3::escapeString($zone->getName()), SQLITE3_TEXT);
        $prepare->bindValue(":player", \SQLite3::escapeString($zone->getOwner()), SQLITE3_TEXT);
        $prepare->bindValue(":level", $zone->getLevelName(), SQLITE3_TEXT);
        $prepare->bindValue(":minx", $position[0], SQLITE3_INTEGER);
        $prepare->bindValue(":miny", $position[1], SQLITE3_INTEGER);
        $prepare->bindValue(":minz", $position[2], SQLITE3_INTEGER);
        $prepare->bindValue(":maxx", $position[3], SQLITE3_INTEGER);
        $prepare->bindValue(":maxy", $position[4], SQLITE3_INTEGER);
        $prepare->bindValue(":maxz", $position[5], SQLITE3_INTEGER);
        $prepare->bindValue(":pvp", $zone->pvpAvailable);
        $prepare->execute();

    }

    public function removeZone(Zone $zone)
    {
        $prepare = $this->database->prepare("DELETE FROM Zones WHERE name = :name");
        $prepare->bindValue(":name", \SQLite3::escapeString($zone->getName()), SQLITE3_TEXT);
        $prepare->execute();

        $prepare = $this->database->prepare("DELETE FROM Permissions WHERE zone_name = :zone");
        $prepare->bindValue(":zone", \SQLite3::escapeString($zone->getName()), SQLITE3_TEXT);
        $prepare->execute();

        return true;
    }

    public function getAllZone()
    {
        $result = $this->database->exec("SELECT * FROM Zones");
        if($result instanceof \SQLite3Result)
        {
            $zones = [];
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            foreach($data as $zone)
            {
                $level = $this->plugin->getServer()->getLevelByName($zone["level_name"]);
                if($level == null)
                    continue;

                $pos1 = new Position($zone["minX"], $zone["minY"], $zone["minZ"], $level);
                $pos2 = new Position($zone["maxX"], $zone["maxY"], $zone["maxZ"], $level);
                $zones[$zone["name"]] = new Zone($this->plugin, $zone["name"], $zone["player_owner"], $pos1, $pos2, $zone["pvpAvailable"]);
            }
            return $zones;
        }
        return [];

    }

    public function setPermission(Player $player, $permission)
    {
        $zone = explode(".", $permission)[0];
        $prepare = $this->database->prepare("INSERT INTO Permissions (player_name, zone_name,  permission_name) VALUES (:name, :zone, :permission)");
        $prepare->bindValue(":name", \SQLite3::escapeString($player->getName()), SQLITE3_TEXT);
        $prepare->bindValue(":zone", \SQLite3::escapeString($zone), SQLITE3_TEXT);
        $prepare->bindValue(":permission", \SQLite3::escapeString($permission), SQLITE3_TEXT);
        $prepare->execute();
    }

    public function unsetPermission(Player $player, Zone $zone)
    {
        $prepare = $this->database->prepare("DELETE FROM Permissions WHERE player_name = :name AND zone_name = :zone");
        $prepare->bindValue(":name", \SQLite3::escapeString($player->getName()), SQLITE3_TEXT);
        $prepare->bindValue(":zone", \SQLite3::escapeString($zone->getName()), SQLITE3_TEXT);
        $prepare->execute();
    }

    public function getPermissions(Player $player)
    {
        $prepare = $this->database->prepare("SELECT permission_name FROM Permissions WHERE player_name = :name");
        $prepare->bindValue(":name", \SQLite3::escapeString($player->getName()), SQLITE3_TEXT);
        $result = $prepare->execute();
        return (($result instanceof \SQLite3Result) ? $result->fetchArray(SQLITE3_NUM) : []);
    }

    public function close()
    {
        $this->database->close();
    }
}