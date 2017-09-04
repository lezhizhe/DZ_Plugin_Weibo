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
$pluginsettings['weibofollow']['recomenduids'] = implode("\r\n", $pluginsettings['weibofollow']['recomenduids']);

if(submitcheck('configsubmit')) {
	
	$_POST['recomenduids']	= trim($_POST['recomenduids']);
	if($_POST['recomenduids']) {
		$pluginsettings['weibofollow']['recomenduids'] = explode("\r\n", $_POST['recomenduids']);
	} else {
		$pluginsettings['weibofollow']['recomenduids'] = array();
	}
	
	$pluginsettings['weibofollow']['followopen']	=	intval($_POST['followopen']);
	$pluginsettings['weibofollow']['officialuid']	=	trim($_POST['officialuid']);
	$pluginsettings['weibofollow']['focus_position']=	trim($_POST['focus_position']);
	$pluginsettings['weibofollow']['focus_width']	=	trim($_POST['focus_width']);
	$pluginsettings['weibofollow']['focus_qqwidth']	=	trim($_POST['focus_qqwidth']);
	$pluginsettings['weibofollow']['focus_color']	=	trim($_POST['focus_color']);
	$pluginsettings['weibofollow']['focus_style']	=	intval($_POST['focus_style']);
	sina_plugin_tools::set_plugin_admin_settings($pluginsettings);
	cpmsg('plugins_setting_succeed', $_G['siteurl'].ADMINSCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod'], 'succeed');
}

showformheader('plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod']);

showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_recomend_focus'), 'class="tb tb2 "');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_radio'), 'followopen', $pluginsettings['weibofollow']['followopen'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_radio_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_official_id'), 'officialuid', $pluginsettings['weibofollow']['officialuid'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_official_id_desc'));
$position_arr = array(
	'status_extra' 			=>  lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_position_1'),
	'global_header'			=>  lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_position_2'),
	'global_cpnav_extra2'	=>	lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_position_3'),
    'index_nav_extra'       =>  lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_position_4'),
);
$focus_position = '<select name="focus_position">';
$selected = false;
foreach ($position_arr as $key => $val) {
	$selected = $pluginsettings['weibofollow']['focus_position'] == $key ? ' selected' : '';
	$focus_position	.=	'<option value="'.$key.'"'.$selected.'>'.$val.'</option>';
	$selected = false;
}
$focus_position .= '</select>';

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_position'), '', '', $focus_position, '', 0);
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_width'), 'focus_width', $pluginsettings['weibofollow']['focus_width'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_width_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_qqwidth'), 'focus_qqwidth', $pluginsettings['weibofollow']['focus_qqwidth'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_qqwidth_desc'));

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_color'), 'focus_color', $pluginsettings['weibofollow']['focus_color'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_color_desc'));

$style_arr = array(
	'1' =>  lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_style_1'),
	'2'	=>  lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_style_2'),
	'3'	=>	lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_style_3')
);
$focus_style = '<select name="focus_style">';
$selected = false;
foreach ($style_arr as $key => $val) {
	$selected = $pluginsettings['weibofollow']['focus_style'] == $key ? ' selected' : '';
	$focus_style	.=	'<option value="'.$key.'"'.$selected.'>'.$val.'</option>';
	$selected = false;
}
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_focus_style'), '', '', $focus_style, '', 0);

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_recomend'), 'recomenduids', $pluginsettings['weibofollow']['recomenduids'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_recomend_desc'));

showtablefooter();

showsubmit('configsubmit', 'config');

showformfooter();
