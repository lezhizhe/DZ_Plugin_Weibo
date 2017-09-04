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
$data_filter = sina_plugin_tools::get_pushback_filter();
$syncpushback_filter = '';
$pre = '';
foreach($data_filter as $key => $value) {
	$syncpushback_filter = $syncpushback_filter.$pre.$key.'<#>'.implode('|', $value);
	$pre = "\r\n"; 
}
$pluginsettings['sysncpushback']['pushbackshare_uids'] = implode("\r\n", $pluginsettings['sysncpushback']['pushbackshare_uids']);

if(submitcheck('configsubmit')) {
	$_POST['sysncpushback']['pushbackshare_uids']	= trim($_POST['sysncpushback']['pushbackshare_uids']);
	if($_POST['sysncpushback']['pushbackshare_uids']) {
		$_POST['sysncpushback']['pushbackshare_uids'] = explode("\r\n", $_POST['sysncpushback']['pushbackshare_uids']);
	} else {
		$_POST['sysncpushback']['pushbackshare_uids'] = array();
	}
	$_POST['syncpushback']['option']	=	array_values($_POST['syncpushback']['option']);
	$pluginsettings['sysncpushback']	=	$_POST['sysncpushback'];
	sina_plugin_tools::set_plugin_admin_settings($pluginsettings);

	$fileter = $_POST['pushback_fileter'];
	$pushback_fileter = array();
	if($fileter) {
		$fileter = explode("\r\n", $fileter);
		foreach($fileter as $key => $value) {
			$value = trim($value);
			if($value) {
				list($search, $replace) = explode('<#>', $value, 2);
				$search;
				$pushback_fileter[$search] = explode('|', $replace);
			}
		}
	}
	sina_plugin_tools::set_pushback_filter($pushback_fileter);
	cpmsg('plugins_setting_succeed', $_G['siteurl'].ADMINSCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod'], 'succeed');
}

showformheader('plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod']);
showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback'), 'class="tb tb2 "');
$syncoptions = array(
		array('threads', lang('forum/template', 'threads')),
		array('portal', lang('template', 'portal')),
		array('blog', lang('template', 'blog')),
);
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_desc'),
	array('sysncpushback[option]', $syncoptions), 
	$pluginsettings['sysncpushback']['option'],
	'mcheckbox'
);
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushbackshare'), 'sysncpushback[pushbackshare]', $pluginsettings['sysncpushback']['pushbackshare'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushbackshare_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushbackshare_uids'), 'sysncpushback[pushbackshare_uids]', $pluginsettings['sysncpushback']['pushbackshare_uids'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushbackshare_uids_desc'));

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_time'), 'sysncpushback[ltime]', $pluginsettings['sysncpushback']['ltime'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_time_desc'), 'style="width:100px;"');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushbackrepost'), 'sysncpushback[pushbackrepost]', $pluginsettings['sysncpushback']['pushbackrepost'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushbackrepost_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_nocomment'), 'sysncpushback[pushback_nocomment]', $pluginsettings['sysncpushback']['pushback_nocomment'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_nocomment_desc'));

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_filter'), 'pushback_fileter', $syncpushback_filter, 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pushback_filter_desc'));

showtablefooter();
showsubmit('configsubmit', 'config');
showformfooter();
