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
	
	if(!is_array($_POST['showoption'])) {
		$_POST['showoption'] = array();
	}
	if($_POST['headerlogin']) {
		array_push($_POST['showoption'], 'headerlogin');
	}
	if($_POST['viewthreadweiboinfo']) {
		array_push($_POST['showoption'], 'viewthreadweiboinfo');
	}
	$pluginsettings['showoption'] = $_POST['showoption'];
	$pluginsettings['quick_register'] = $_POST['quick_register'];
	$pluginsettings['use_signature'] = $_POST['use_signature'];
	$pluginsettings['expireoption']['ftime'] = intval($_POST['expireoption']['ftime']);
	$pluginsettings['expireoption']['creditid'] = intval($_POST['expireoption']['creditid']);
	$pluginsettings['expireoption']['credit'] = intval($_POST['expireoption']['credit']);
	$pluginsettings['medalid'] = $_POST['medalid'];
	
	sina_plugin_tools::set_plugin_admin_settings($pluginsettings);
	cpmsg('plugins_setting_succeed', $_G['siteurl'].ADMINSCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod'], 'succeed');
}




showformheader('plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod']);
showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show'), 'class="tb tb2 "');

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show_headlogin'), 'headerlogin', in_array('headerlogin', $pluginsettings['showoption']), 'radio', '', 0, '<img alt="'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'sina_weibo_login').'" src="'.$_G['siteurl'].'source/plugin/'.sina_plugin_tools::get_plugin_name().'/img/weibo_login.png">'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show_headlogin_desc').'<br/>'.htmlentities('<a href="'.sina_plugin_tools::get_rewrite_url('init').'"><img src="source/plugin/'.sina_plugin_tools::get_plugin_name().'/img/weibo_login.png"></a>'));

showsetting('&#24086;&#23376;&#35814;&#24773;&#39029;&#22836;&#20687;&#19979;&#26041;&#26174;&#31034;&#24494;&#21338;&#35814;&#24773;', 'viewthreadweiboinfo', in_array('viewthreadweiboinfo', $pluginsettings['showoption']), 'radio', '', 0, '&#24086;&#23376;&#35814;&#24773;&#39029;&#22836;&#20687;&#19979;&#26041;&#26174;&#31034;&#22238;&#27969;&#24494;&#21338;&#29992;&#25143;&#31881;&#19997;&#25968;&#12289;&#20851;&#27880;&#25968;&#12289;&#24494;&#21338;&#25968;&#20449;&#24687;');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show_other'),
array('showoption', array(
	array('fastlogin', lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show_other_1')),
	array('viewthreadweibodetail', lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show_other_2')),
	array('viewthreadweibomedal', lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_show_other_3')),
)),
$pluginsettings['showoption'],
'mcheckbox'
);


showtablefooter();

showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_bind'), 'class="tb tb2 "');

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_quick_register'), 'quick_register', $pluginsettings['quick_register'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_quick_register_desc'), '');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_use_signature'), 'use_signature', $pluginsettings['use_signature'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_use_signature_desc'), '');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_oauth_expired'), 'expireoption[ftime]', $pluginsettings['expireoption']['ftime'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_oauth_expired_desc'), 'style="width:100px;"');

$medallist = array();
$medallist = C::t('forum_medal')->fetch_all_name_by_available();


$weibo_medal = '<select name="medalid"><option value="">'.cplang('plugins_empty').'</option>';
foreach ($medallist as $medal) {
	$weibo_medal .= '<option value="'.$medal['medalid'].'"'.($pluginsettings['medalid'] == $medal['medalid'] ? ' selected' : '').'>'.$medal['name'].'</option>';
}
$weibo_medal .= '</select>';
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_medal'), '', '', $weibo_medal, '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_medal_desc'));
$crediturl = ADMINSCRIPT.'?action=credits&operation=list&anchor=policytable';
showtablerow('', '',array('<a href="'.$crediturl.'">'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_credit').'</a>', ''));
showtablefooter();
showsubmit('configsubmit', 'config');

showformfooter();
?>
