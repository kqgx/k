<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2021-03-22 09:59:28 --> Unable to connect to the database
ERROR - 2021-03-22 10:13:17 --> Query error: Table 'd2tsa2x3.imt_1_product' doesn't exist - Invalid query: SELECT `news`.`id`, `news`.`updatetime`, `news`.`thumb`, `news`.`title`, `news`.`url`, `news`.`description`, `news`.`introduction`
FROM `imt_1_product` as `news`
LEFT JOIN `imt_1_product_flag` as `flag` ON `news`.`id`=`flag`.`id`
WHERE `news`.`status` = 9
AND `flag`.`flag` = 1
ORDER BY `news`.`displayorder` desc, `news`.`id` DESC
 LIMIT 6
ERROR - 2021-03-22 10:13:18 --> Query error: Table 'd2tsa2x3.imt_1_product' doesn't exist - Invalid query: SELECT `news`.`id`, `news`.`updatetime`, `news`.`thumb`, `news`.`title`, `news`.`url`, `news`.`description`, `news`.`introduction`
FROM `imt_1_product` as `news`
LEFT JOIN `imt_1_product_flag` as `flag` ON `news`.`id`=`flag`.`id`
WHERE `news`.`status` = 9
AND `flag`.`flag` = 1
ORDER BY `news`.`displayorder` desc, `news`.`id` DESC
 LIMIT 6
ERROR - 2021-03-22 10:13:18 --> Query error: Table 'd2tsa2x3.imt_1_product' doesn't exist - Invalid query: SELECT `news`.`id`, `news`.`updatetime`, `news`.`thumb`, `news`.`title`, `news`.`url`, `news`.`description`, `news`.`introduction`
FROM `imt_1_product` as `news`
LEFT JOIN `imt_1_product_flag` as `flag` ON `news`.`id`=`flag`.`id`
WHERE `news`.`status` = 9
AND `flag`.`flag` = 1
ORDER BY `news`.`displayorder` desc, `news`.`id` DESC
 LIMIT 6
ERROR - 2021-03-22 10:13:19 --> Query error: Table 'd2tsa2x3.imt_1_product' doesn't exist - Invalid query: SELECT `news`.`id`, `news`.`updatetime`, `news`.`thumb`, `news`.`title`, `news`.`url`, `news`.`description`, `news`.`introduction`
FROM `imt_1_product` as `news`
LEFT JOIN `imt_1_product_flag` as `flag` ON `news`.`id`=`flag`.`id`
WHERE `news`.`status` = 9
AND `flag`.`flag` = 1
ORDER BY `news`.`displayorder` desc, `news`.`id` DESC
 LIMIT 6
ERROR - 2021-03-22 13:28:19 --> Severity: error --> Exception: Call to undefined method Module_Content_model::_update_status() /D/wwwroot/d2tsa2x3/models/Module/Content/controllers/admin/Content.php 866
ERROR - 2021-03-22 13:28:38 --> Severity: error --> Exception: Call to undefined method Module_Content_model::_update_status() /D/wwwroot/d2tsa2x3/models/Module/Content/controllers/admin/Content.php 845
ERROR - 2021-03-22 14:15:19 --> Severity: error --> Exception: syntax error, unexpected end of file /D/wwwroot/d2tsa2x3/data/views/d6a09a9f1c5587b5c1c834a8346c8ecb 40
