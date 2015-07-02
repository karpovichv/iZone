<?php
namespace iZone;


/**
 * Mini Rank System
 *  - These ranks can be granted for a specified time (In Seconds)
 *
 * Admin:       No need to explain
 * Moderator:   You can place, destroy and activate block. You can kick and add guest to the area.
 * Friend:      You can place,destroy and activate block in the area, but you can't add guest to the area
 * Worker:      You can place and destroy
 * Spectator:   You only can see the area
*/

define("ADMIN", ".admin");
define("MODERATOR", ".moderator");
define("FRIEND", ".friend");
define("WORKER", ".worker");
define("SPECTATOR", ".spectator");

use pocketmine\Player;
use pocketmine\level\Position;

/**
 * Class Zone
 * @package iZone
 */
class Zone
{
    /** @var iZone */
    private $plugin;

    /** @var string */
    private $owner;

    /** @var string */
    private $levelName;

    /** @var string */
    private $name;


    private $minX, $minY, $minZ, $maxX, $maxY, $maxZ;

    /**
     * @param iZone $plugin
     * @param int $name
     * @param Player $owner
     * @param Position $pos1
     * @param Position $pos2
     */
    public function __construct(iZone $plugin, $name, $owner, Position $pos1, Position $pos2)
    {
        $this->plugin = $plugin;
        $this->name = $name;

        $this->minX = min($pos1->x, $pos2->x);
        $this->minY = min($pos1->y, $pos2->y);
        $this->minZ = min($pos1->x, $pos2->z);

        $this->maxX = max($pos1->x, $pos2->x);
        $this->maxY = max($pos1->y, $pos2->y);
        $this->maxZ = max($pos1->z, $pos2->z);

        $this->owner = (($owner instanceof Player) ? $owner->getName() : $owner);
        $this->levelName = $pos1->getLevel()->getName();


        //Register owner's permissions
        $this->plugin->addPermission($owner, $name . ".admin");
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isIn(Position $position)
    {
        if($this->minX <= $position->x && $position->x  <= $this->maxX)
        {
            if($this->minY <= $position->y && $position->y <= $this->maxY)
            {
                if($this->minZ <= $position->z && $position->z <= $this->maxZ)
                {
                    if($position->getLevel()->getName() === $this->levelName)
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Position $position
     * @param $radius
     *
     * @return bool
     */
    public function isOnRadius(Position $position, $radius)
    {
        if($this->minX - $radius <= $position->x && $position->x  <= $this->maxX + $radius)
        {
            if($this->minY - $radius <= $position->y && $position->y <= $this->maxY + $radius)
            {
                if($this->minZ - $radius <= $position->z && $position->z <= $this->maxZ + $radius)
                {
                    if($position->getLevel()->getName() === $this->levelName)
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLevelName()
    {
        return $this->levelName;
    }

    public function getPosition()
    {
        return [ $this->minX, $this->minY, $this->minZ, $this->maxX, $this->maxY, $this->maxZ ];
    }

} 