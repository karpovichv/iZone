<?php
/**
 * Created by PhpStorm.
 * User: InusualZ
 * Date: 4/1/14
 * Time: 3:47 PM
 */

namespace iZone\Task;

use iZone\Zone;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class PermissionMember extends PluginTask {

    private $zone, $user, $permission;

    public function __construct(Zone &$zone, Player $user, $permission)
    {
        $this->zone = $zone;
        $this->user = $user;
        $this->permission = $permission;
    }

    public function onRun($currentTick)
    {
        $this->zone->setPermission($this->user, $this->permission);
        $this->user->sendMessage("[iZone] You permission have been changed in the private are of: " . $this->zone->owner->getDisplayName());
    }
}