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
	
	$_POST['syncpublish']['option']	=	array_values($_POST['syncpublish']['option']);
	$pluginsettings['syncpublish']	=	$_POST['syncpublish'];
	$pluginsettings['pubtpl'] = $_POST['pubtpl'];
	
	sina_plugin_tools::set_plugin_admin_settings($pluginsettings);
	cpmsg('plugins_setting_succeed', $_G['siteurl'].ADMINSCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod'], 'succeed');
}

$syncoptions = array(
	array('threads', lang('forum/template', 'threads')),	
	array('portal', lang('template', 'portal')),	
	array('blog', lang('template', 'blog')),
	array('share', lang('template', 'share')),
	array('doing', lang('template', 'doing')),
	array('follow', lang('home/template', 'follow')),
);
showformheader('plugins&operation=config&pluginid='.$pluginid.'&identifier='.sina_plugin_tools::get_plugin_name().'&pmod='.$_GET['pmod']);
showtableheader(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_sync'), 'class="tb tb2 "');
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_sync_desc'), 
	array('syncpublish[option]', $syncoptions), 
	$pluginsettings['syncpublish']['option'],
	'mcheckbox'
);

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_forwarding'), 'syncpublish[forwarding]', $pluginsettings['syncpublish']['forwarding'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_forwarding_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_advocate'), 'syncpublish[advocate]', $pluginsettings['syncpublish']['advocate'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_advocate_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_replynopublish'), 'syncpublish[replynopublish]', $pluginsettings['syncpublish']['replynopublish'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_replynopublish_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_sync_checked'), 'syncpublish[sync_checked]', $pluginsettings['syncpublish']['sync_checked'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_sync_checked_desc'));

showsetting(lang('forum/template', 'threads').lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_weibo_title_format'), 'pubtpl[thread]', $pluginsettings['pubtpl']['thread'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pubttp_thread_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_weibo_reply_format'), 'pubtpl[reply]', $pluginsettings['pubtpl']['reply'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pubttp_reply_desc'));
showsetting(lang('template', 'blog').lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_weibo_title_format'), 'pubtpl[blog]', $pluginsettings['pubtpl']['blog'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pubttp_blog_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_weibo_portal_format'), 'pubtpl[article]', $pluginsettings['pubtpl']['article'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pubttp_article_desc'));
showsetting(lang('template', 'doing').lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_weibo_title_format'), 'pubtpl[doing]', $pluginsettings['pubtpl']['doing'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pubttp_doing_desc'));
showsetting(lang('home/template', 'follow').lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_weibo_title_format'), 'pubtpl[follow]', $pluginsettings['pubtpl']['follow'], 'textarea', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_pubttp_follow_desc'));

showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shorturl'), 'syncpublish[shorturl]', $pluginsettings['syncpublish']['shorturl'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_shorturl_desc'));
showsetting(lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_upload_url_text'), 'syncpublish[upload_url_text]', $pluginsettings['syncpublish']['upload_url_text'], 'radio', '', 0, lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'admin_upload_url_text_desc'));

showtablefooter();
showsubmit('configsubmit', 'config');
showformfooter();
