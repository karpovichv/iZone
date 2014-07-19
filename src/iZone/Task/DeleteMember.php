<?php
/**
 * Created by PhpStorm.
 * User: InusualZ
 * Date: 4/1/14
 * Time: 3:06 PM
 */

namespace iZone\Task;

use iZone\Zone;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;


class DeleteMember extends PluginTask
{
    private $zone, $user;

    public function __construct(Zone &$zone, Player $user)
    {
        $this->zone = $zone;
        $this->user = $user;
    }

    public function onRun($currentTick)
    {
        $this->zone->deleteGuest($this->user);
        $this->user->sendMessage("[iZone] You have been removed from the private area of: " . $this->zone->owner->getDisplayName());
    }
}