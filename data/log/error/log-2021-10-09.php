<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2021-10-09 16:01:52 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near ')' at line 6 - Invalid query: SELECT `dis_price`, `quantity`
FROM `imt_1_order_goods` as `g`
INNER JOIN `imt_1_order` as `o` ON `g`.`oid`=`o`.`id`
JOIN `imt_1_mall` as `m` ON `g`.`cid`=`m`.`id`
WHERE `order_status` NOT IN(0, 3, 7, 8, 9)
AND `buy_uid` IN()
ERROR - 2021-10-09 16:03:56 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near ')' at line 6 - Invalid query: SELECT `dis_price`, `quantity`
FROM `imt_1_order_goods` as `g`
INNER JOIN `imt_1_order` as `o` ON `g`.`oid`=`o`.`id`
JOIN `imt_1_mall` as `m` ON `g`.`cid`=`m`.`id`
WHERE `order_status` NOT IN(0, 3, 7, 8, 9)
AND `buy_uid` IN()
