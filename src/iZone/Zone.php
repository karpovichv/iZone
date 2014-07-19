<?php
namespace iZone;

use iZone\Task\DeleteMember;
use iZone\Task\PermissionMember;

use pocketmine\Player;
use pocketmine\level\Position;

//These permissions can be granted for a specified time (In Seconds)
define("OWNER_PERM", 5.0);
define("MOD_PERM", 3.0); // With this permission you can place, destroy and activate block. You can kick and add guest to the area.
define("FRIEND_PERM", 2.5);// With this permission you can place,destroy and activate block in the area, but you can't add guest to the area
define("WORK_PERM", 2.0); // With this permission you can place and destroy
define("SEE_PERM", 1.0); // With this permission you only can see the area
define("SERV_PLAYER_PERM", 0.0); // A Server User

/**
 * Class Zone
 * @package iZone
 */
class Zone extends Position
{
    private $plugin;

    private $x2, $y2, $z2;

    private $owner;

    private $users;

    private $setup = false;


    /**
     * @param MainClass $plugin
     * @param int $name
     * @param Player $owner
     * @param Position $pos1
     * @param Position $pos2
     */
    public function __construct(MainClass $plugin, $name, Player $owner, Position $pos1, Position $pos2)
    {
        $this->plugin = $plugin;

        if($pos1->getLevel()->getName() === $pos2->getLevel()->getName())
        {
            $this->x = min($pos1->x, $pos2->x);
            $this->y = min($pos1->y, $pos2->y);
            $this->z = min($pos1->x, $pos2->z);

            $this->x2 = max($pos1->x, $pos2->x);
            $this->y2 = max($pos1->y, $pos2->y);
            $this->z2 = max($pos1->z, $pos2->z);
        }
        else
            return;

        $this->owner = $owner;
        $this->setup = true;
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isIn(Position $position)
    {
        if($this->setup == false)
            return false;


        if($this->x <= $position->x && $position->x  <= $this->x2)
        {
            if($this->y <= $position->y && $position->y <= $this->y2)
            {
                if($this->z <= $position->z && $position->z <= $this->z2)
                {
                    if($position->getLevel()->getName() === $this->getLevel()->getName())
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
        if($this->setup == false)
            return false;

        if($this->x - $radius <= $position->x && $position->x  <= $this->x2 + $radius)
        {
            if($this->y - $radius <= $position->y && $position->y <= $this->y2 + $radius)
            {
                if($this->z - $radius <= $position->z && $position->z <= $this->z2 + $radius)
                {
                    if($position->getLevel()->getName() === $this->getLevel()->getName())
                        return true;
                }
            }
        }
        return false;
    }


    /**
     * @param Player $user
     * @param $perm
     * @param int $time
     * @param bool $reset
     */
    public function setPermission(Player $user, $perm, $time = 0, $reset = false)
    {

        $perm = $this->getPermCode($perm);
        if(array_key_exists($user->getName(), $this->users))
        {
            if($reset === true && $time > 0)
            {
                $lperm = $this->users[$user->getName()];
                $this->users[$user->getName()] = $perm;
                $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new PermissionMember($this, $user, $lperm), 20 * $time);
            }
            elseif($time > 0 && $reset === false)
            {
                $this->users[$user->getName()] = $perm;
                $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new DeleteMember($this, $user), 20 * $time);
            }
            else
                $this->users[$user->getName()] = $perm;
        }
    }

    /**
     * @param Player $user
     *
     * @return float
     */
    public function getPermission(Player $user)
    {
        if(array_key_exists($user->getName(), $this->users))
            return $this->users[$user->getName()];
        else
            return SERV_PLAYER_PERM;
    }

    /**
     * @param $perm
     *
     * @return float
     */
    public function getPermCode($perm)
    {
        switch(strtolower($perm))
        {
            case 'administrator':
            case 'admin':
            case 'owner':
            case 'own':
            case 'o':
            case 'a':
                return OWNER_PERM;
                break;

            case 'moderator':
            case 'mod':
            case 'm':
                return MOD_PERM;
                break;

            case 'friend':
            case 'f':
                return FRIEND_PERM;
                break;

            case 'worker':
            case 'work':
            case 'w':
                return WORK_PERM;
                break;

            case 'spectator':
            case 'spec':
            case 'see':
            case 's':
            default:
                return SEE_PERM;
                break;

        }
    }

    /**
     * @param Player $user
     * @param $perm
     * @param int $time
     */
    public function addGuest(Player $user, $perm, $time = 0)
    {
        $perm = $this->getPermCode($perm);
        $this->users[$user->getName()] = $perm;
        $user->sendMessage("[iZone] You have been added to the private area of: " . $this->owner->getName());

        if($time > 0)
            $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new DeleteMember($this, $user), 20 * $time);
    }

    /**
     * @param Player $user
     */
    public function deleteGuest(Player $user)
    {
        if(array_key_exists($user->getName(), $this->users))
        {
            unset($this->users[$user->getName()]);
            $user->sendMessage("[iZone] You have been removed from the private area of: " . $this->owner->getName());
        }
    }


    /**
     * @return Player
     */
    public function getOwner()
    {
        return $this->owner;
    }
} 