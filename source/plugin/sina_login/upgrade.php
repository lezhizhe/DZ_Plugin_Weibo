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
if(version_compare('16027', '2.0.3', '<')) {
	$sql = <<<EOF
		CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_config` (
		  `skey` varchar(32) NOT NULL,
		  `data` text NOT NULL,
		  `dateline` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`skey`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
EOF;

	runquery($sql);
	$skey = DB::result_first("SELECT `skey` FROM ".DB::table('plugin_sina_sync_bind_config')." WHERE `skey`='config'");
	if(!$skey) {
		if(file_exists(DISCUZ_ROOT.'source/plugin/sina_login/config.inc.php')) {
			DB::query('TRUNCATE TABLE '.DB::table('plugin_sina_sync_bind_config'));
			include_once DISCUZ_ROOT.'source/plugin/sina_login/config.inc.php';
			$settings = $plugin_sina_config;
			$settings = serialize($settings);
			$sql = "INSERT INTO ".DB::table('plugin_sina_sync_bind_config')." (`skey`, `data`, `dateline`) VALUES ('config', '".$settings."', '".TIMESTAMP."')";
			DB::query($sql);
			@unlink(DISCUZ_ROOT.'source/plugin/sina_login/config.inc.php');
		}
	}

}
$field = DB::result_first("SHOW COLUMNS FROM ".DB::table('plugin_sina_sync_bind_thread')." LIKE 'uid'");
if($field) {
	$sql = <<<EOF
		ALTER TABLE  `pre_plugin_sina_sync_bind_pushback` ADD  `id` INT( 10 ) NOT NULL FIRST;
		ALTER TABLE  `pre_plugin_sina_sync_bind_pushback` ADD  `type` CHAR( 12 ) NOT NULL AFTER `id`;
		ALTER TABLE  `pre_plugin_sina_sync_bind_pushback` ADD INDEX (`id` , `type`);
		ALTER TABLE `pre_plugin_sina_sync_bind_thread` DROP `uid`;
		CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_sina`(
		  `sina_uid` bigint(20) unsigned NOT NULL,
		  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `profile` text NOT NULL,
		  PRIMARY KEY (`sina_uid`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
EOF;
	runquery($sql);
}

$sql = <<<EOF
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

CREATE TABLE IF NOT EXISTS `pre_plugin_sina_sync_bind_log` (
  `error` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `dateline` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
EOF;
runquery($sql);

$finish = TRUE;