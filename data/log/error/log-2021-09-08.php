<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2021-09-08 09:26:57 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 09:31:00 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 09:33:19 --> Severity: error --> Exception: syntax error, unexpected end of file /D/wwwroot/MjEwNjAz/models/Member/model.php 189
ERROR - 2021-09-08 09:33:24 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 09:50:36 --> Query error: Unknown column 'fQc4910048' in 'where clause' - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `invitation_code` IN(SELECT randcode FROM imt_member WHERE invitation_code=fQc4910048)
ERROR - 2021-09-08 09:51:57 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near '' at line 5 - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND invitation_code IN(SELECT randcode FROM imt_member WHERE invitation_code = `fQc4910048`
ERROR - 2021-09-08 09:53:37 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 09:53:41 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 09:53:42 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near '' at line 5 - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND invitation_code IN(SELECT randcode FROM imt_member WHERE invitation_code = `fQc4910048`
ERROR - 2021-09-08 09:57:47 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near '' at line 5 - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND invitation_code IN(SELECT randcode FROM imt_member WHERE invitation_code = 'fQc4910048'
ERROR - 2021-09-08 10:01:29 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 10:12:11 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 10:48:29 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 10:52:41 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 10:53:35 --> Severity: error --> Exception: syntax error, unexpected 'value_1' (T_STRING), expecting ')' /D/wwwroot/MjEwNjAz/data/views/cc40f9c3e3abffe3b847d00ea8a0ceb4 107
ERROR - 2021-09-08 10:54:54 --> Severity: Warning --> vsprintf(): Too few arguments /D/wwwroot/MjEwNjAz/web/helpers/my_helper.php 22
ERROR - 2021-09-08 10:55:05 --> Severity: Warning --> vsprintf(): Too few arguments /D/wwwroot/MjEwNjAz/web/helpers/my_helper.php 22
ERROR - 2021-09-08 10:55:28 --> Severity: Warning --> vsprintf(): Too few arguments /D/wwwroot/MjEwNjAz/web/helpers/my_helper.php 22
ERROR - 2021-09-08 11:06:55 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 11:14:53 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 16:59:42 --> Query error: Unknown column 'month_hits' in 'order clause' - Invalid query: SELECT `imt_member_data`.*, `imt_member`.*, `imt_member`.`uid` as `uid`
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
ORDER BY `month_hits` desc
 LIMIT 10
ERROR - 2021-09-08 17:09:36 --> Severity: error --> Exception: Call to undefined function data() /D/wwwroot/MjEwNjAz/models/Member/controllers/admin/Member.php 393
ERROR - 2021-09-08 18:09:17 --> Query error: Column 'uid' in where clause is ambiguous - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `uid` = 23
ERROR - 2021-09-08 18:09:45 --> Query error: Column 'uid' in where clause is ambiguous - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `uid` = 25
ERROR - 2021-09-08 18:09:52 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 18:09:55 --> Query error: Column 'uid' in where clause is ambiguous - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `uid` = 23
ERROR - 2021-09-08 18:11:07 --> Query error: Column 'uid' in where clause is ambiguous - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `uid` = 23
ERROR - 2021-09-08 18:12:17 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 18:12:19 --> Query error: Column 'uid' in where clause is ambiguous - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `uid` = 34
ERROR - 2021-09-08 18:12:30 --> Query error: Column 'uid' in where clause is ambiguous - Invalid query: SELECT count(*) as total
FROM `imt_member`
LEFT JOIN `imt_member_data` ON `imt_member_data`.`uid` = `imt_member`.`uid`
WHERE `groupid` = 7
AND `uid` = 23
ERROR - 2021-09-08 18:25:55 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 18:26:29 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
ERROR - 2021-09-08 18:26:33 --> Severity: Warning --> Use of undefined constant IS_SHARE - assumed 'IS_SHARE' (this will throw an Error in a future version of PHP) /D/wwwroot/MjEwNjAz/data/views/5413c5a4ec96557edf885c25ebfa344e 72
