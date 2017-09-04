<?php
/**
 *	[http://www.lezhizhe.net] (C)2012-2099 lezhizhe_net.
 *  This is NOT a freeware, use is subject to license terms.
 *
 * @author				lezhizhe_net<caoziqiang163@126.com>
 * @copyright 			lezhizhe.net
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

require_once dirname(__FILE__).'/class/tools.class.php';

$pluginsettings = sina_plugin_tools::get_plugin_admin_settings();

if(submitcheck('configsubmit')) {
	
	$pluginsettings['appkey']		= trim($_POST['appkey']);
	$pluginsettings['appsecret']	= trim($_POST['appsecret']);
	
	sina_plugin_tools::set_plugin_admin_settings($pluginsettings);
	cpmsg('plugins_setting_succeed', $_G['siteurl'].ADMINSCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod'], 'succeed');
}
showformheader('plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod']);

showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_key_secret'), 'class="tb tb2 "');
showsetting('AppKey', 'appkey', $pluginsettings['appkey'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_key_desc'));
showsetting('AppSecret', 'appsecret', $pluginsettings['appsecret'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_secret_desc'));
showtablefooter();

showsubmit('configsubmit', 'config');
showformfooter();
?>
