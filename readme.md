PocketMine-MP Plugin. Protect Multiple Lands with this plugins

### Config

| Configuration | Type | Default | Description |
| :---: | :---: | :---: | :--- |
| non-op-create | boolean | __false__ | This option if true will allow players no ops, that could create private areaame. |
| explosion-protection | boolean | __true__ | This option allows you to protect private area of an explosion. |
| explosion-radius-protection | integer | __8__ | This will be the radius of the explosion protection. |
| default-area-size | integer | __10__ | This will be the default size for a private area.. |

---

### Commands

| Command  | Alias    | Description |
| -------- | -------- | :---------- |
|  izone create [radius]                               |izc [radius] | **The main coordinates will be the player who executed the command. We will do a radius with the given number in the command.** |
|  izone create [player]                               | izc [radius] | **The main coordinates will be the player who executed the command and the final coordinates will be the player specified in the command. The onwer of the private area which will execute the command.**| 
|  izone create [player1] [player2]                    | izc [player1] [player2] | **The private area is created between the two player specified in the command. The first specified player will own the private area.**| 
|  izone create [x] [y] [z]                            | izc [x] [y] [z] | **The private area is created between the player and the specified coordinates. The owner who will execute the command.**|
|  izone create [player] [x] [y] [z]                   | izc [player] [x] [y] [z]  | **A private area is created between the player and the specified coordinates. The owner will be specified in the command.**|
|  izone create [x1] [y1] [z1] [x2] [y2] [z2]          | izc [x1] [y1] [z1] [x2] [y2] [z2] | **A private area will be created between the coordinates specified in the command. The owner of the private zone who will run the command.**|
|  izone create [player] [x1] [y1] [z1] [x2] [y2] [z2] | izc [player] [x1] [y1] [z1] [x2] [y2] [z2]   | **A private area will be created between the coordinates specified in the command. The owner will be specified in the command.**|
| izone delete [owner]	| izd [owner] | **This command delete a private area of the onwer specified in the command.**|
| izone delete [x] [y] [z] | izd [x] [y] [z] | **This command delete the private area with the coordinate specified in the  command** |
| izone delete [x1] [y1] [z1] [x2] [y2] [z2] | izd [x1] [y1] [z1] [x2] [y2] [z2] | **This command delete the private area with the coordinate specified in the  command** |
| izone addg [player] | izag [player] | **This command will add the player to your private area specified as guest** |
| izone addg [player] [rank] | izag [player] [rank] | **This command adds the specified player to the private area. The player will have the rank specified in the command** |
| izone addg [player] [rank] [time] | izag [player] [rank] [time] | **This command adds the specified player to the private area. The player will have the rank specified in the command by the time specified in the command (Seconds)** |
| izone permg [player] [rank] | izpg [player] [rank] | **This command modifies the rank of player. In your private area** |
| izone permg [player] [rank] [time] | izpg [player] [rank] [time] | **This command modifies the rank of player for the indicated time. In your private area.** |
| izone deleteg [player] | izdg [player] | **This will remove the player from the guest list of your private area** |
| izone help | izh | **This command will show all of this command in-game** |

