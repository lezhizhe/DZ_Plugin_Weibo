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

//showtableheader('');
//showsetting('AppKey', 'appkey', $pluginsettings['appkey'], 'text', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_key_desc'));
//showsetting('AppSecret', 'appsecret', $pluginsettings['appsecret'], 'text', '', 0, lang('plugin/'.sina_pluginp7_tools::get_plugin_name(), 'admin_secret_desc'));
$admin_help = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help');
$admin_help_setting = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_setting');
$admin_help_look = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_look');
$admin_help_question = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_question');
$admin_help_type14 = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_type14');
$admin_help_type15 = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_type15');
$admin_help_type16 = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_type16');
$admin_help_type17 = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_type17');
$admin_help_author = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_author');
$admin_help_author_desc = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_author_desc');
$admin_help_upgrade = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_upgrade');
$admin_help_upgrade_desc = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_help_upgrade_desc');
echo <<<EOF
<table class="tb tb2 fixpadding">
	<tbody>
	<tr><th colspan="15" class="partition">$admin_help</th></tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_setting</td>
		<td class="lineheight smallfont"><a href="http://bbs.lezhizhe.net/forum.php?mod=viewthread&tid=13" target="_blank">$admin_help_look</a></td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_question</td>
		<td class="lineheight smallfont"><a href="http://bbs.lezhizhe.net/forum.php?mod=viewthread&tid=145" target="_blank">$admin_help_look</a></td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_type17</td>
		<td class="lineheight smallfont"><a href="http://bbs.lezhizhe.net/forum.php?mod=forumdisplay&fid=50&filter=typeid&typeid=17" target="_blank">$admin_help_look</a></td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_type15</td>
		<td class="lineheight smallfont"><a href="http://bbs.lezhizhe.net/forum.php?mod=forumdisplay&fid=50&filter=typeid&typeid=15" target="_blank">$admin_help_look</a></td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_type16</td>
		<td class="lineheight smallfont"><a href="http://bbs.lezhizhe.net/forum.php?mod=forumdisplay&fid=50&filter=typeid&typeid=16" target="_blank">$admin_help_look</a></td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_type14</td>
		<td class="lineheight smallfont"><a href="http://bbs.lezhizhe.net/forum.php?mod=forumdisplay&fid=50&filter=typeid&typeid=14" target="_blank">$admin_help_look</a></td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_author</td>
		<td class="lineheight smallfont"><a href="http://www.weibo.com/339403229" target="_blank">$admin_help_look</a><br/>$admin_help_author_desc</td>
	</tr>
	<tr>
		<td class="vtop td24 lineheight">$admin_help_upgrade</td>
		<td class="lineheight smallfont">$admin_help_upgrade_desc</td>
	</tr>
	</tbody>
</table>
EOF
//showtablefooter();

?>
