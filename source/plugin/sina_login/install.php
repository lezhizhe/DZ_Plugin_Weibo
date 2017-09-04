<?php
/**
 *	[http://www.lezhizhe.net] (C)2012-2099 lezhizhe_net.
 *  This is NOT a freeware, use is subject to license terms.
 *
 * @author				lezhizhe_net<caoziqiang163@126.com>
 * @copyright 			lezhizhe.net
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_config` (
  `skey` varchar(32) NOT NULL,
  `data` text NOT NULL,
  `dateline` int(10) unsigned NOT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_user` (
  `sina_uid` bigint(20) unsigned NOT NULL,
  `uid` mediumint(8) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `access_token` char(32) NOT NULL,
  `oauth_time` int(10) NOT NULL,
  `expires_in` mediumint(8) NOT NULL,
  `settings` varchar(255) NOT NULL,
  `profile` text NOT NULL,
  PRIMARY KEY (`sina_uid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_thread` (
  `mid` bigint(20) unsigned NOT NULL,
  `tid` bigint(20) unsigned NOT NULL,
  `sina_uid` bigint(20) unsigned NOT NULL,
  `type` char(15) NOT NULL,
  `iscomment` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `synctime` int(10) unsigned NOT NULL,
  `lastpushbacktime` int(10) unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`mid`,`tid`),
  KEY `tid` (`tid`,`type`),
  KEY `lastpushbacktime` (`lastpushbacktime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		
CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_pushback` (
  `id` int(10) NOT NULL,
  `type` char(12) NOT NULL,
  `mid` bigint(10) unsigned NOT NULL,
  `cid` bigint(10) unsigned NOT NULL,
  `sina_uid` bigint(10) unsigned NOT NULL,
  `synctime` int(10) unsigned NOT NULL,
  KEY `cid` (`cid`),
  KEY `mid` (`mid`,`cid`),
  KEY `id` (`id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_pushback_repost` (
  `id` int(10) NOT NULL,
  `type` char(12) NOT NULL,
  `mid` bigint(10) unsigned NOT NULL,
  `rid` bigint(10) unsigned NOT NULL,
  `sina_uid` bigint(10) unsigned NOT NULL,
  `synctime` int(10) unsigned NOT NULL,
  KEY `rid` (`rid`),
  KEY `mid` (`mid`,`rid`),
  KEY `id` (`id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_sina` (
  `sina_uid` bigint(20) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `profile` text NOT NULL,
  PRIMARY KEY (`sina_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_log` (
  `error` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `dateline` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
EOF;
runquery($sql);

$sqlsina_sync_bind = "INSERT INTO  `pre_common_credit_rule` (`rid` ,`rulename` ,`action` ,`cycletype` ,`cycletime` ,`rewardnum` ,`norepeat` ,`extcredits1` ,`extcredits2` ,`extcredits3` ,`extcredits4` ,`extcredits5` ,`extcredits6` ,`extcredits7` ,`extcredits8` ,`fids`) VALUES (NULL ,  '".$installlang['sina_sync_bind']."',  'sina_sync_bind',  '1',  '0',  '1',  '0',  '1',  '2',  '1',  '0',  '0',  '0',  '0',  '0',  '')";
$sqlsina_sync_login = "INSERT INTO  `pre_common_credit_rule` (`rid` ,`rulename` ,`action` ,`cycletype` ,`cycletime` ,`rewardnum` ,`norepeat` ,`extcredits1` ,`extcredits2` ,`extcredits3` ,`extcredits4` ,`extcredits5` ,`extcredits6` ,`extcredits7` ,`extcredits8` ,`fids`) VALUES (NULL ,  '".$installlang['sina_sync_login']."',  'sina_sync_login',  '1',  '0',  '1',  '0',  '0',  '2',  '0',  '0',  '0',  '0',  '0',  '0',  '')";
$sqlsina_sync_extend = "INSERT INTO  `pre_common_credit_rule` (`rid` ,`rulename` ,`action` ,`cycletype` ,`cycletime` ,`rewardnum` ,`norepeat` ,`extcredits1` ,`extcredits2` ,`extcredits3` ,`extcredits4` ,`extcredits5` ,`extcredits6` ,`extcredits7` ,`extcredits8` ,`fids`) VALUES (NULL ,  '".$installlang['sina_sync_extend']."',  'sina_sync_extend',  '4',  '0',  '1',  '0',  '0',  '2',  '0',  '0',  '0',  '0',  '0',  '0',  '')";
$ruleaction = array('sina_sync_bind', 'sina_sync_login', 'sina_sync_extend');

foreach($ruleaction as $action) {
	$rid = DB::result_first("SELECT `rid` FROM ".DB::table('common_credit_rule')." WHERE `action`='$action'");
	if($rid > 0) {
		continue;
	}
	runquery(${'sql'.$action});
}

$username = $installlang['commentusername'];

$member = C::t('common_member')->fetch_by_username($username);
if(!$member) {
	$uid = $email = '';
	$password = random(6);
	loaducenter();
	$user = uc_get_user($username);
	if($user) {
		list($uid, $username, $email) = $user;
	} else {
		$email = 'sinaweibopinglun_'.random(6).'@weibo.com.cn';
		$uid = uc_user_register(addslashes($username), $password, $email, '', '', $_G['clientip']);
	}
	if($_G ['setting']['regverify']) {
		$groupid = 8;
	} else {
		$groupid = $_G ['setting']['newusergroupid'];
	}
	$init_arr = array('credits' => explode(',', $_G ['setting']['initcredits']), 'profile'=>array(), 'emailstatus' => 1);
	C::t('common_member')->insert($uid, $username, $password, $email, $_G['clientip'], $groupid, $init_arr);
} else {
	$uid = $member['uid'];
}

$skey = DB::result_first("SELECT `skey` FROM ".DB::table('plugin_sina_sync_bind_config')." WHERE `skey`='config'");
if(!$skey) {
	include_once DISCUZ_ROOT.'source/plugin/sina_login/config.default.inc.php';
	$pluginsettings = $plugin_sina_config;
	$pluginsettings['commentuid'] = $uid;
	$pluginsettings['commentusername'] = $username;
	$pluginsettings = serialize($pluginsettings);
	DB::query('TRUNCATE TABLE '.DB::table('plugin_sina_sync_bind_config'));
	$sql = "INSERT INTO ".DB::table('plugin_sina_sync_bind_config')." (`skey`, `data`, `dateline`) VALUES ('config', '".$pluginsettings."', '".TIMESTAMP."')";
	DB::query($sql);
}
$finish = TRUE;
