<?php

namespace iZone;

use iZone\Event\ZoneCreatedEvent;
use iZone\Event\ZoneRemovedEvent;
use iZone\Event\ZoneRemovePermissionEvent;
use iZone\Event\ZoneSetPermission;
use iZone\Event\ZoneSetPermissionEvent;
use iZone\Provider\DataProvider;
use iZone\Provider\DummyProvider;
use iZone\Provider\MYSQLProvider;
use iZone\Provider\SQLProvider;
use iZone\Provider\YAMLProvider;
use iZone\Task\PermissionMember;
use pocketMine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketMine\command\CommandSender;
use pocketmine\level\Position;
use pocketMine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\permission\PermissionAttachment;


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
                if(empty($name) || isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone already exist or name cannot be empty");
                    return true;
                }


                if(count($args) == 0)
                {
                    if(isset($this->positions1[spl_object_hash($sender)]) && isset($this->positions2[spl_object_hash($sender)]))
                    {
                        $pos1 = $this->positions1[spl_object_hash($sender)];
                        $pos2 = $this->positions2[spl_object_hash($sender)];
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
                        if($ev->isCancelled()){
                            return false;
                        }

                        $this->zones[$name] = $zone;
                        $this->dataProvider->addZone($zone);
                        $sender->sendMessage("[iZone] The zone {$name} have been have successfully created");
                        unset($this->positions1[spl_object_hash($sender)]);
                        unset($this->positions2[spl_object_hash($sender)]);
                        return true;
                    }

                    $radius = $this->getConfig()->get("default-size");
                    $pos1 =  new Position($sender->x - $radius, $sender->y - $radius, $sender->z - $radius, $sender->getLevel());
                    $pos2 =  new Position($sender->x + $radius, $sender->y + $radius, $sender->z + $radius, $sender->getLevel());
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
                    if($ev->isCancelled()){
                        return false;
                    }

                    $this->zones[$name] = $zone;
                    $this->dataProvider->addZone($zone);
                    $sender->sendMessage("[iZone] The zone {$name} have been have successfully created");
                    return true;
                }
                elseif(count($args) == 1)
                {
                    $radius = intval(array_shift($args));
                    $pos1 =  new Position($sender->x - $radius, $sender->y - $radius, $sender->z - $radius, $sender->getLevel());
                    $pos2 =  new Position($sender->x + $radius, $sender->y + $radius, $sender->z + $radius, $sender->getLevel());
                    $zone =  new Zone($this, $name, $sender, $pos1, $pos2);
                    foreach($this->zones as $z)
                    {
                        if($z->intersectsWith($zone))
                        {
                            $sender->sendMessage("[iZone] You can not interfere with other zones");
                            return true;
                        }
                    }

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneCreatedEvent($this, $zone));
                    if($ev->isCancelled()){
                        return false;
                    }

                    $this->zones[$name] = $zone;
                    $this->dataProvider->addZone($zone);
                    $sender->sendMessage("[iZone] The zone {$name} have been have successfully created");
                    return true;
                }
                elseif(count($args) == 3)
                {
                    $x = intval(array_shift($args));
                    $y = intval(array_shift($args));
                    $z = intval(array_shift($args));

                    $pos2 =  new Position($x, $y, $z, $sender->getLevel());

                    $zone = new Zone($this, $name, $sender, $sender, $pos2);
                    foreach($this->zones as $z)
                    {
                        if($z->intersectsWith($zone))
                        {
                            $sender->sendMessage("[iZone] You can not interfere with other zones");
                            return true;
                        }
                    }

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneCreatedEvent($this, $zone));
                    if($ev->isCancelled()){
                        return false;
                    }

                    $this->zones[$name] = $zone;
                    $this->dataProvider->addZone($zone);
                    $sender->sendMessage("[iZone] The zone {$name} have been have successfully created");
                    return true;
                }
                elseif(count($args) == 6)
                {

                    $x = intval(array_shift($args));
                    $y = intval(array_shift($args));
                    $z = intval(array_shift($args));

                    $x2 = intval(array_shift($args));
                    $y2 = intval(array_shift($args));
                    $z2 = intval(array_shift($args));

                    $pos1 =  new Position($x, $y, $z, $sender->getLevel());
                    $pos2 =  new Position($x2, $y2, $z2, $sender->getLevel());

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
                    if($ev->isCancelled()){
                        return false;
                    }

                    $this->zones[$name] = $zone;
                    $this->dataProvider->addZone($zone);
                    $sender->sendMessage("[iZone] The zone {$name} have been have successfully created");

                    return true;
                }
                break;

            case "destroy":
                $name = array_shift($args);
                if(!isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone {$name} doesn't exist");
                    return true;
                }

                if($sender->isOp() || $sender->hasPermission($name . ADMIN))
                {
                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneRemovedEvent($this, $this->zones[$name], $sender));
                    if($ev->isCancelled()){
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

            case "set":

                $name = array_shift($args);
                $user = array_shift($args);
                $perm = array_shift($args);
                $time = array_shift($args);

                if(empty($name) || $name == null || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone doesn't exist");
                    return true;
                }

                if(empty($user) || $user == null)
                {
                    $sender->sendMessage("[iZone] The player name cannot be empty");
                    return true;
                }

                $user = $this->getServer()->getPlayer($user);
                if($user == null)
                {
                    $sender->sendMessage("[iZone] The player does not exist or is online");
                    return true;
                }


                if($sender->isOp() || $sender->hasPermission($name . MODERATOR))
                {
                    $perm = $name . "." . $perm;

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneSetPermissionEvent($this, $this->zones[$name], $user, $perm));
                    if($ev->isCancelled()){
                        return false;
                    }

                    $this->removePermission($user, $name .  ADMIN);
                    $this->addPermission($user, $perm);
                    $this->dataProvider->setPermission($user, $perm);


                    if($time != null)
                        $this->getServer()->getScheduler()->scheduleDelayedTask(new PermissionMember($this, $this->getZone($name), $user, $name . SPECTATOR), 20 * $time);

                    $sender->sendMessage("[iZone] The player has been added!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have right to do that");
                return true;
            break;

            case "unset":
                $name = array_shift($args);
                $user = array_shift($args);
                $permission = array_shift($args);

                if($name == null || empty($name) || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The zone don't exist");
                    return true;
                }


                if($user == null || empty($user))
                {
                    $sender->sendMessage("[iZone] The player name cannot be empty");
                    return true;
                }

                if($permission == null || empty($permission))
                {
                    $sender->sendMessage("[iZone] The permission that you are trying to remove from player can't be empty");
                    return true;
                }

                $user = $this->getServer()->getPlayer($user);
                if($user == null)
                {
                    $sender->sendMessage("The player don't exist or not is online!");
                    return true;
                }

                if($sender->isOp() || $sender->hasPermission($name . MODERATOR))
                {
                    $permission = $name . "." . $permission;

                    $this->getServer()->getPluginManager()->callEvent($ev = new ZoneRemovePermissionEvent($this, $this->zones[$name], $user, $permission));
                    if($ev->isCancelled()){
                        return false;
                    }

                    $this->removePermission($user, $permission);
                    $this->dataProvider->unsetPermission($user, $permission);
                    $sender->sendMessage("[iZone] The player has been removed!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have permission to do that");
                return true;
            break;


            case "coord":
                $sender->sendMessage("[iZone] Coordinates: X: {$sender->x} Y: {$sender->y} Z: {$sender->z}");
                return true;
            break;

            case "help":
            default:
                $sender->sendMessage("Usage: /izone <command> [parameters...] {optional...}");
                $sender->sendMessage("Usage: /izone create [name] {int}");
                $sender->sendMessage("Usage: /izone create [name] [x] [y] [z] ");
                $sender->sendMessage("Usage: /izone create [name] [x1] [y1] [z1] [x2] [y2] [z2] ");
                $sender->sendMessage("Usage: /izone destroy [name]");
                $sender->sendMessage("Usage: /izone set [zone] [player] [rank] {time}");
                $sender->sendMessage("Usage: /izone unset [zone] [player] [rank]");
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
        if(count($permission) != 2)
        {
            $this->getLogger()->error("Error parsing permission");
            return false;
        }

        switch($permission[1])
        {
            case "owner":
            case "admin":
            case "5":
                $attachment->setPermission($permission[0] . ADMIN, true);
            case "moderator":
            case "mod":
            case "4":
                $attachment->setPermission($permission[0] . MODERATOR, true);
            case "friend":
            case "frnd":
            case "3":
                $attachment->setPermission($permission[0] . FRIEND, true);
            case "worker":
            case "work":
            case "2":
                $attachment->setPermission($permission[0] . WORKER, true);
            default:
                $attachment->setPermission($permission[0] . SPECTATOR, true);
                break;
        }
        return true;
    }

    public function removePermission($player, $permission)
    {
        $player = ($player instanceof Player ? $player : $this->getServer()->getPlayer($player));
        if($player == null || empty($player))
        {
            $this->getLogger()->error("Unable to find player while trying to remove permission.");
            return false;
        }

        $attachment = &$this->playersAttachment[spl_object_hash($player)];
        $permission = explode(".", $permission);
        if(count($permission) != 2)
        {
            $this->getLogger()->error("Error parsing permission");
            return false;
        }


        switch($permission[1])
        {
            case "owner":
            case "admin":
            case "5":
                $attachment->unsetPermission($permission[0] . ADMIN);
            case "moderator":
            case "mod":
            case "4":
                $attachment->unsetPermission($permission[0] . MODERATOR);
            case "friend":
            case "frnd":
            case "3":
                $attachment->unsetPermission($permission[0] . FRIEND);
            case "worker":
            case "work":
            case "2":
                $attachment->unsetPermission($permission[0] . WORKER);
            default:
                $attachment->unsetPermission($permission[0] . SPECTATOR);
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


    public function getRank($rank)
    {
        switch($rank)
        {
            case "owner":
            case "admin":
            case "5":
                return ADMIN;
            case "moderator":
            case "mod":
            case "4":
                return MODERATOR;
            case "friend":
            case "frnd":
            case "3":
                return FRIEND;
            case "worker":
            case "work":
            case "2":
                return WORKER;
            default:
                return SPECTATOR;
                break;
        }
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