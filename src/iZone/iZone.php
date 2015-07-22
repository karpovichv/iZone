<?php

namespace iZone;

use iZone\Event\ZoneCreatedEvent;
use iZone\Event\ZoneRemovedEvent;
use iZone\Event\ZoneRemovePermissionEvent;
use iZone\Event\ZoneSetPermissionEvent;
use iZone\Provider\DataProvider;
use iZone\Provider\DummyProvider;
use iZone\Provider\MYSQLProvider;
use iZone\Provider\SQLProvider;
use iZone\Provider\YAMLProvider;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\permission\PermissionAttachment;

/**
 * Area permissions:
 *
 * Owner:       You can add and remove members, remove the area. Yes, there could be more than one owner.
 * Member:      You can place and break blocks, use chests, doors etc.
*/

define("OWNER", ".owner");
define("MEMBER", ".member");


/**
 * Class iZone
 * @package iZone
 */
class iZone extends PluginBase implements CommandExecutor
{
    /** @var Zone[] */
    private $zones;

    /** @var Position[] */
    private $positions1;

    /**
     * @var Position[]
     */
    private $positions2;

    /**
     * @var PermissionAttachment[]
     */
    private $playersAttachment;


    /** @var DataProvider */
    private $dataProvider;

    public function __construct()
    {
        $this->zones = [];
        $this->positions1 = [];
        $this->positions2 = [];
        $this->playersAttachment = [];

    }

    public function onEnable()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        $this->getServer()->getPluginManager()->registerEvents(new EventManager($this), $this);

        $provider = $this->getConfig()->get("data-provider", "none");
        switch(strtolower($provider))
        {
            case "mysql":
                $this->dataProvider = new MYSQLProvider($this);
                break;
            case "sql:":
                $this->dataProvider = new SQLProvider($this);
                break;
            case "yml":
                $this->dataProvider = new YAMLProvider($this);
                break;

            case "none":
            default:
                $this->dataProvider = new DummyProvider($this);
                break;
        }

        $this->zones = $this->dataProvider->getAllZone();
    }


    public function onDisable()
    {
        $this->dataProvider->close();
        $this->zones = [];
        $this->positions1 =  [];
        $this->positions2 = [];
        $this->playersAttachment =  [];
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if($command->getName() != "izone" || !($sender instanceof Player))
            return false;

        switch(strtolower(array_shift($args)))
        {
            case "pos1":
                $this->positions1[spl_object_hash($sender)] = Position::fromObject($sender, $sender->getLevel());
                $sender->sendMessage("[iZone] Registered Position #1");
                return true;
            break;

            case "pos2":
                $this->positions2[spl_object_hash($sender)] = Position::fromObject($sender, $sender->getLevel());
                $sender->sendMessage("[iZone] Registered Position #2");
                return true;
            break;

            case "create":
                if(!$sender->isOp() && !$this->getConfig()->get("non-op-create", false))
                {
                    $sender->sendMessage("[iZone] You don't have the right to create a private zone");
                    return true;
                }

                $name = array_shift($args);
                if($name == null || empty($name) || isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone already exist or name cannot be empty");
                    return true;
                }
                
				if(!isset($this->positions1[spl_object_hash($sender)]) || !isset($this->positions2[spl_object_hash($sender)]))
				{
					$sender->sendMessage("[iZone] Set two positions with /izone pos1 and /izone pos2");
					return true;
				}
				
				$pos1 = $this->positions1[spl_object_hash($sender)];
				$pos2 = $this->positions2[spl_object_hash($sender)];
				
				if(abs($pos1->getX() - $pos2->getX()) > $this->getConfig()->get("maximum-x", 30))
				{
					$sender->sendMessage("[iZone] The area has exceeded the maximum x-length!");
                    return true;
				}
				else if(abs($pos1->getY() - $pos2->getY()) > $this->getConfig()->get("maximum-y", 30))
				{
					$sender->sendMessage("[iZone] The area has exceeded the maximum y-height!");
                    return true;
				}
				else if(abs($pos1->getZ() - $pos2->getZ()) > $this->getConfig()->get("maximum-z", 30))
				{
					$sender->sendMessage("[iZone] The area has exceeded the maximum z-width!");
                    return true;
				}
				
				$zone = new Zone($this, $name, $sender, $pos1, $pos2);
				foreach($this->zones as $z)
				{
					if($z->intersectsWith($zone))
					{
						$sender->sendMessage("[iZone] You can not interfere with other zones");
						return true;
					}
				}

				$this->getServer()->getPluginManager()->callEvent($ev = new ZoneCreatedEvent($this, $zone));
				if($ev->isCancelled())
				{
					return false;
				}

				$this->zones[$name] = $zone;
				$this->dataProvider->addZone($zone);
				$sender->sendMessage("[iZone] The zone {$name} have been have successfully created");
				unset($this->positions1[spl_object_hash($sender)]);
				unset($this->positions2[spl_object_hash($sender)]);
				return true;
			break;

            case "remove":
                $name = array_shift($args);
                if($name == null || empty($name) || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone doesn't exist");
                    return true;
                }

                if($sender->isOp() || $sender->hasPermission($name . OWNER))
                {
                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneRemovedEvent($this, $this->zones[$name], $sender));
                    if($ev->isCancelled())
                    {
                        return false;
                    }

                    $this->dataProvider->removeZone($this->zones[$name]);
                    $owner = $this->zones[$name]->getOwner();
                    unset($this->zones[$name]);

                    $owner = $this->getServer()->getPlayer($owner);
                    if($owner == null)
                    {
                        $sender->sendMessage("[iZone] The zone {$name} have been removed");
                        return true;
                    }

                    $owner->sendMessage("[iZone] The zone {$name} have been removed.");
                    if($owner->getName() !== $sender->getName())
                        $sender->sendMessage("[iZone] The zone {$name} have been removed");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have the right to do that");
                return true;
            break;

            case "addmember":
				$name = array_shift($args);
                $user = array_shift($args);
                $as = array_shift($args);

                if(empty($name) || $name == null || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone doesn't exist");
                    return true;
                }

				if($sender->isOp() || $sender->hasPermission($name . OWNER))
                {
					if(empty($user) || $user == null || ($user = $this->getServer()->getPlayer($user)) == null)
					{
						$sender->sendMessage("[iZone] The player doesn't exist or is offline!");
						return true;
					}
					
					if($user->hasPermission($name . MEMBER))
					{
						//Force them to remove the member mannually
						$sender->sendMessage("[iZone] The player is already member of this zone!");
						return true;
					}
					
                    $perm = ($as == "owner") ? $name . OWNER : $name . MEMBER;

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneSetPermissionEvent($this, $this->zones[$name], $user, $perm));
                    if($ev->isCancelled())
                    {
                        return false;
                    }

                    $this->addPermission($user, $perm);
                    $this->dataProvider->setPermission($user, $perm);
					$sender->sendMessage("[iZone] The player has been added!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have right to do that");
                return true;
            break;

            case "removemember":
                $name = array_shift($args);
                $user = array_shift($args);

                if($name == null || empty($name) || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone doesn't exist");
                    return true;
                }

                if($sender->isOp() || $sender->hasPermission($name . OWNER))
                {
					if($user == null || empty($user) || ($user = $this->getServer()->getPlayer($user)) == null)
					{
						$sender->sendMessage("[iZone] The player doesn't exist or not is offline!");
						return true;
					}
					
					if(!$user->hasPermission($name . MEMBER))
					{
						$sender->sendMessage("[iZone] The player is not a member of this zone!");
						return true;
					}
					
					$ownerName = $this->zones[$name]->getOwner();
					
					if($user->getName() == $ownerName)
					{
						$sender->sendMessage("[iZone] The owner couldn't be removed from the zone!");
						return true;
					} 
					
					$isOwner = $user->hasPermission($name . OWNER);
					
					if($isOwner && $sender->getName() != $ownerName)
					{
						$sender->sendMessage("[iZone] You can't remove an owner!");
						return true;
					}

                    $permission = ($isOwner) ? $name . OWNER : $name . MEMBER;

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneRemovePermissionEvent($this, $this->zones[$name], $user, $permission));
                    if($ev->isCancelled())
                    {
                        return false;
                    }

                    $this->removePermission($user, $permission);
                    $this->dataProvider->unsetPermission($user, $this->zones[$name]);
                    $sender->sendMessage("[iZone] The player has been removed!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have permission to do that");
                return true;
            break;
            
            case "leave":
				$name = array_shift($args);
				
				if($name == null || empty($name) || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone doesn't exist");
                    return true;
                }
                
                if($sender->hasPermission($name . MEMBER))
				{
					if($this->zones[$name]->getOwner() == $sender->getName())
					{
						$sender->sendMessage("[iZone] You can't leave your own zone!");
						return true;
					}
					
					$permission = ($sender->hasPermission($name . OWNER)) ? $name . OWNER : $name . MEMBER;

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneRemovePermissionEvent($this, $this->zones[$name], $sender, $permission));
                    if($ev->isCancelled())
                    {
                        return false;
                    }

                    $this->removePermission($sender, $permission);
                    $this->dataProvider->unsetPermission($sender, $this->zones[$name]);
                    $sender->sendMessage("[iZone] You have left the zone!");
                    return true;
				}
				
				$sender->sendMessage("[iZone] You are not a member of this zone!");
				return true;
            break;
            
            case "setpvp":
				$name = array_shift($args);
                $state = array_shift($args);
                
                if(!$sender->isOp() && !$sender->hasPermission($name . OWNER))
				{
					$sender->sendMessage("[iZone] You don't have right to do that");
					return true;
				}

				if(strtolower($state) == "on" || $state == "1")
				{
					if(isset($this->zones[$name]))
					{
						$this->zones[$name]->pvpAvailable = true;
						$sender->sendMessage("[iZone] Enabled PVP in {$name}");
						return true;
					}
					$sender->sendMessage("[iZone] The zone {$name} does not exist!");
					return true;
				}
				else if(strtolower($state == "off") || $state == "0")
				{
					if(isset($this->zones[$name]))
					{
						$this->zones[$name]->pvpAvailable = false;
						$sender->sendMessage("[iZone] Disabled PVP in {$name}");
						return true;
					}
					$sender->sendMessage("[iZone] The zone {$name} does not exist!");
					return true;
				}
				
				$sender->sendMessage("[iZone] Unable to identify the command");
				return true;
            break;

            case "coord":
                $sender->sendMessage("[iZone] Coordinates: X: {$sender->x} Y: {$sender->y} Z: {$sender->z}");
                return true;
            break;

            case "help":
            default:
                $sender->sendMessage("Usage: /izone <command> [parameters] {optional}");
                $sender->sendMessage("Usage: /izone create [name]");
                $sender->sendMessage("Usage: /izone remove [name]");
                $sender->sendMessage("Usage: /izone addmember [zone] [player] {owner}");
                $sender->sendMessage("Usage: /izone removemember [zone] [player]");
                $sender->sendMessage("Usage: /izone leave [zone]");
                $sender->sendMessage("Usage: /izone setpvp [zone] [on/off]");
                $sender->sendMessage("Usage: /izone coord");
                return true;
            break;
        }

        return false;
    }

    public function addPermission($player, $permission)
    {
        $player = ($player instanceof Player ? $player : $this->getServer()->getPlayer($player));
        if($player == null || empty($player))
        {
            $this->getLogger()->error("Unable to find player {$player} while trying to add permission");
            return false;
        }
        
        $attachment = &$this->playersAttachment[spl_object_hash($player)];
        $permission = explode(".", $permission);
        
        switch($permission[1])
        {
            case "owner":
                $attachment->setPermission($permission[0] . OWNER, true);
            default:
                $attachment->setPermission($permission[0] . MEMBER, true);
                break;
        }
        return true;
    }
    public function removePermission($player, $permission)
    {
        $player = ($player instanceof Player ? $player : $this->getServer()->getPlayer($player));
        if($player == null || empty($player))
        {
            $this->getLogger()->error("Unable to find player {$player} while trying to remove permission");
            return false;
        }
        
        $attachment = &$this->playersAttachment[spl_object_hash($player)];
        $permission = explode(".", $permission);
        
        switch($permission[1])
        {
            case "owner":
                $attachment->unsetPermission($permission[0] . OWNER);
            default:
                $attachment->unsetPermission($permission[0] . MEMBER);
                break;
        }
        return true;
    }


    public function addAttachment(Player $player)
    {
        if(isset($this->playersAttachment[spl_object_hash($player)]))
        {
            $this->getLogger()->error("Already added attachment");
            return false;
        }

        $this->playersAttachment[spl_object_hash($player)] = $player->addAttachment($this);

        //Load saved permissions
        $permissions = $this->dataProvider->getPermissions($player);
        if(count($permissions) == 0)
            return true;

        foreach($permissions as $permission)
            $this->addPermission($player, $permission);
        return true;
    }

    public function removeAttachment($player)
    {
        $player = ($player instanceof Player ? $player : $this->getServer()->getPlayer($player));
        if($player == null || empty($player))
        {
            $this->getLogger()->error("Unable to find player while trying to remove attachment.");
            return true;
        }

        if(!isset($this->playersAttachment[spl_object_hash($player)]))
        {
            $this->getLogger()->error("Unable to find attachment.");
            return false;
        }

        $player->removeAttachment($this->playersAttachment[spl_object_hash($player)]);
        unset($this->playersAttachment[spl_object_hash($player)]);
        return true;
    }

    /**
     * @param DataProvider $provider
     */
    public function setDataProvider(DataProvider $provider)
    {
        $this->dataProvider = $provider;
    }

    /**
     * @return DataProvider
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @param string $name
     * @return Zone|null
     */
    public function getZone($name)
    {
        return isset($this->zones[$name]) ? $this->zones[$name] : null;
    }

    /**
     * @return Zone[]
     */
    public function getAllZones()
    {
        return $this->zones;
    }

    /**
     * @param Player $owner
     *
     * @return bool
     */
    public function removeZone(Player $owner)
    {
        if(isset($this->zones[$owner->getName()]))
        {
            unset($this->zones[$owner->getName()]);
            return true;
        }

        return false;
    }

}
