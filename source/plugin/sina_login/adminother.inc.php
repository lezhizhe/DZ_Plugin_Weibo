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
	
	$_POST['shareoption']['sharecontent'] = sina_plugin_tools::substr($_POST['shareoption']['sharecontent'], 0, 140, CHARSET);
	$pluginsettings['shareoption']	= $_POST['shareoption'];
	sina_plugin_tools::set_plugin_admin_settings($pluginsettings);
	cpmsg('plugins_setting_succeed', $_G['siteurl'].ADMINSCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod'], 'succeed');
}

showformheader('plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod']);

showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption'), 'class="tb tb2 "');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_open'), 'shareoption[open]', $pluginsettings['shareoption']['open'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_open_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_sharecontent'), 'shareoption[sharecontent]', $pluginsettings['shareoption']['sharecontent'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_sharecontent_desc'));

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_sharepic'), 'shareoption[sharepic]', $pluginsettings['shareoption']['sharepic'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_sharepic_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_shareforce'), 'shareoption[shareforce]', $pluginsettings['shareoption']['shareforce'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shareoption_shareforce_desc'));
showtablefooter();

showsubmit('configsubmit', 'config');
showformfooter();
?>
