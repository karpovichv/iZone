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

    /**
     * @var Zone
     */
    private $_zone;

    /** @var Player  */
    private $_player;

    /**
     * @param iZone $plugin
     * @param Zone $zone
     * @param Player $player
     */
    public function __construct(iZone $plugin, Zone $zone, Player $player)
    {
        parent::__construct($plugin);
        $this->_zone = $zone;
        $this->_player = $player;
    }

    /**
     * @param $currentTick
     */
    public function onRun($currentTick)
    {
        $this->getOwner()->removePermission($this->_player, $this->_zone->getName() . ADMIN);
        $this->_player->sendMessage("[iZone] You have been removed from the zone " . $this->_zone->getName());
    }
}