<?php
/**
 * Created by PhpStorm.
 * User: InusualZ
 * Date: 4/1/14
 * Time: 3:06 PM
 */

namespace iZone\Task;

use iZone\iZone;

use iZone\Zone;
use pocketmine\scheduler\PluginTask;
use pocketmine\Player;


class DeleteMember extends PluginTask
{
    /** @var iZone */
    private $_plugin;

    private $_zone;

    /** @var Player  */
    private $_player;

    public function __construct(iZone $plugin, Zone $zone, Player $player)
    {
        $this->_plugin = $plugin;
        $this->_zone = $zone;
        $this->_player = $player;
    }

    public function onRun($currentTick)
    {
        $this->_plugin->removePermission($this->_player, $this->_zone->getName() . ".admin");
        $this->_player->sendMessage("[iZone] You have been removed from the zone " . $this->_zone->getName());
    }
}