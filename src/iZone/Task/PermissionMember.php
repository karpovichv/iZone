<?php
/**
 * Created by PhpStorm.
 * User: InusualZ
 * Date: 4/1/14
 * Time: 3:47 PM
 */

namespace iZone\Task;

use iZone\iZone;
use iZone\Zone;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class PermissionMember extends PluginTask {

    private $zone;
    private $player;
    private $permission;

    public function __construct(iZone $plugin, Zone &$zone, Player $player, $permission)
    {
        parent::__construct($plugin);
        $this->zone = $zone;
        $this->player = $player;
        $this->permission = $permission;
    }

    public function onRun($currentTick)
    {
        $this->getOwner()->removePermission($this->player, $this->zone->getName() . ADMIN);
        $this->getOwner()->addPermission($this->player, $this->permission);
        $this->player->sendMessage("[iZone] Your permission have been changed in the zone: " . $this->zone->getName());
    }
}