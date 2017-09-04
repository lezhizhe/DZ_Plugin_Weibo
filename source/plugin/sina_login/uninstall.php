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
if($_G['cookie']['sina_login_savedata'] == 'no') {
	$sql = <<<EOF
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_user`;
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_thread`;
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_sina`;
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_pushback`;
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_pushback_repost`;
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_config`;
		DROP TABLE IF EXISTS `pre_plugin_sina_sync_bind_log`;
EOF;
	runquery($sql);
}
dsetcookie('sina_login_savedata');
$finish = TRUE;