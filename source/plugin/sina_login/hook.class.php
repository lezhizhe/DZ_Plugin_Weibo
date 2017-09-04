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
class plugin_sina_base {
	const	SYNCAGING = 6048000;
	public $isbind = false; 
	public $weibouserarr = array();
	public $adminsettings = array();
	public $usersettings = array();
	public $pluginid = '';
	
	function plugin_sina_base() {
		$this->_init();
	}
	
	function get_syncaging() {
		return self::SYNCAGING;
	}
	
	private function _init() {
		global $_G;
		require_once DISCUZ_ROOT.'source/plugin/sina_login/class/tools.class.php';
		if($_G['uid'] && $this->isbind == false) {
			$users = self::get_bind_user($_G['uid']);
			foreach($users as $rs) {
				$this->isbind = true;
				$this->weibouserarr = $users;
				$this->usersettings = $rs['settings'];
				break;
			}
		}
		$this->pluginid = sina_plugin_tools::get_plugin_name();
		$this->adminsettings = &sina_plugin_tools::get_plugin_admin_settings();
		$this->adminsettings['initurl'] = sina_plugin_tools::get_rewrite_url('init');
		$this->adminsettings['imgurl'] = 'source/plugin/'.$this->pluginid.'/img';
	}

	public static function get_bind_user($uid) {
		static $users = null;
		if($users === null) {
			$users = array();
			$data = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($uid);
			if($data) {
				foreach($data as $rs) {
					if($rs['status'] == 1) {
						$rs['settings'] = unserialize($rs['settings']);
						$rs['profile'] = unserialize($rs['profile']);
						$users[$rs['sina_uid']] = $rs;
					}
				}
			} 
		}
		return $users;
	}
	
	function hack_publish($func, $args = array()) {
		sina_plugin_tools::get_publish_to_weibo()->callfunc($func, $this, $args);
	}
	
	public function get_request_method(){
			return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
	}
	
	public function tpl($tpl) {
		return template(sina_plugin_tools::get_plugin_name().':'.$tpl);
	}
	
}

class plugin_sina_login extends plugin_sina_base {
	
	function global_login_extra() {
		if(in_array('headerlogin', $this->adminsettings['showoption'])) {
			global $_G;
			$return = '';
			include $this->tpl('login_extra');
			return $return;
		}
	}
	
	function global_usernav_extra1() {
		global $_G;
		if(!$_G['uid'] || $_G['inshowmessage'] || (CURSCRIPT == 'home' && CURMODULE == 'spacecp' && $_GET['id'] == $this->pluginid.':setting')) {
			return '';
		}
		$expired = false;
		if($this->adminsettings['expireoption']['ftime'] > 0) {
			foreach($this->weibouserarr as $rs) {
				if(($rs['oauth_time'] + $rs['expires_in'] - TIMESTAMP) < $this->adminsettings['expireoption']['ftime'] * 3600) {
					$expired = true;
					break;
				}
			}
		}
		$return = '';
		include $this->tpl('usernav_extra1');
		return $return;
	}

	function global_nav_extra(){ 
		if($this->adminsettings['weibofollow']['followopen'] && $this->adminsettings['weibofollow']['focus_position'] == 'global_header') {
			$return = '';
			include $this->tpl('global_nav_extra_focus');
			return $return;
		}
	}
	
	function global_cpnav_extra2(){
		if($this->adminsettings['weibofollow']['followopen'] && $this->adminsettings['weibofollow']['focus_position'] == 'global_cpnav_extra2') {
			$return = '';
			include $this->tpl('global_cpnav_extra2_focus');
			return $return;
		}
	}
	
	function global_login_text() {
		if(in_array('fastlogin', $this->adminsettings['showoption'])) {
			$return = '';
			include $this->tpl('login_text');
			return $return;
		}
	}
	
	function common() {
		global $_G;
		if($_G['cookie']['sina_'.$this->pluginid]) {
			if(CURSCRIPT == 'member' && (CURMODULE == 'register' || (CURMODULE == 'logging' && $_GET['action'] == 'login'))) {
				$_G['setting']['reglinkname'] = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'perfect_user');
			}
		};
	}
	
	function global_footer() {
		global $_G;
		if($this->adminsettings['shareoption']['open']) {
			$sina_uid = $_G['cookie'][$this->pluginid.'_sharebind'] ? $_G['cookie'][$this->pluginid.'_sharebind'] : $_G['cookie'][$this->pluginid.'_shareregister_1'];
			if($sina_uid) {
				$sina_uid = authcode($sina_uid, 'DECODE', sina_plugin_tools::get_plugin_name());
				$binddata = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($sina_uid);
				if($binddata && !($binddata['uid'] != $_G['uid'] || $binddata['status'] != 1 || TIMESTAMP > $binddata['oauth_time'] + $binddata['expires_in'])) {
					$status = $this->adminsettings['shareoption']['sharecontent'];
					if($this->adminsettings['shareoption']['shareforce'] && !empty($status)) {
						if('gbk' == strtolower(CHARSET)) {
							$status = sina_plugin_tools::convert($status, 'GBK', 'UTF-8');
						}
						$pic_path = $this->adminsettings['shareoption']['sharepic'];
						$client = sina_plugin_tools::get_client($this->adminsettings['appkey'], $this->adminsettings['appsecret'], $binddata['access_token']);
						if($pic_path) { 
							if($this->adminsettings['syncpublish']['upload_url_text']) {
								$response = $client->upload_url_text($status, $pic_path);
							} else {
								$response = $client->upload($status, $pic_path);
							}
						} else {
							$response = $client->update($status);
						}
						if(!$response || $response['error']) {
							sina_plugin_tools::log('sharebinding to weibo error sina_uid:'.$sina_uid.' uid:'.$_G['uid'], $response);
						}
					} else {
						include $this->tpl('global_footer');
					}
				}
			}
			if(!$_G['inshowmessage']) {
				dsetcookie($this->pluginid.'_sharebind');
				dsetcookie($this->pluginid.'_shareregister_1');
				if($_G['cookie'][$this->pluginid.'_shareregister']) {
					dsetcookie($this->pluginid.'_shareregister_1', $_G['cookie'][$this->pluginid.'_shareregister']);
					dsetcookie($this->pluginid.'_shareregister');
				}
			}
		}
		return $return;
	}
}

class plugin_sina_login_member extends plugin_sina_base {
	

	function register_top() {
		global $_G;
		$return = '';
		if($_G['cookie']['sina_'.$this->pluginid]) {
			include $this->tpl('member_tips');
		}
		return $return ;
	}
	
	function logging_top() {
		return $this->register_top();
	}
	
	function register_logging_method() {
		global $_G;
		$return = '';
		if(!$_G['cookie']['sina_'.$this->pluginid]) {
			include $this->tpl('member_login');
		}
		return $return ;
	}
	
	function logging_method() {
		return $this->register_logging_method();
	}
	
	function logging_login_message($p) {
		global $_G;
		if($_G['cookie']['sina_'.$this->pluginid]) {
			if(in_array($p['param'][0], array('login_succeed', 'location_login_succeed', 'login_succeed_inactive_member'))) {
				if($_G['cookie']['sina_'.$this->pluginid]) {
					$sina_uid = authcode($_G['cookie']['sina_'.$this->pluginid], 'DECODE', $this->pluginid);
					if($this->_bind($sina_uid)) {
						dsetcookie(sina_plugin_tools::get_plugin_name().'_shareregister', authcode($sina_uid, 'ENCODE', sina_plugin_tools::get_plugin_name()));
					}
				}
			}
		}
	}
	
	function register_message($p) {
		global $_G;
		if($_G['cookie']['sina_'.$this->pluginid]) {
			if($p['param'][3]['showid'] == 'succeedmessage' && in_array($p['param'][0], array('register_succeed', 'register_manual_verify', 'register_email_verify'))) {
				if($_G['cookie']['sina_'.$this->pluginid]) {
					$sina_uid = authcode($_G['cookie']['sina_'.$this->pluginid], 'DECODE', $this->pluginid);
					if($this->_bind($sina_uid)) {
						dsetcookie(sina_plugin_tools::get_plugin_name().'_shareregister', authcode($sina_uid, 'ENCODE', sina_plugin_tools::get_plugin_name()));
					}
				}
			}
		}
	}
	
	function register_bottom() {
		global $_G;
		$return = '';
		if($_G['cookie']['sina_'.$this->pluginid]) {
			include $this->tpl('register_bottom');
		}
		return $return ;
	}
	private function _bind($sina_uid) {
		global $_G;
		if(!$_G['uid']) {
			return false;
		}
	
		dsetcookie('sina_'.$this->pluginid);
		$binduser = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($sina_uid);
		if($binduser) {
			if($binduser['uid'] > 0 && $binduser['status'] == 1) {
				return false;
			} else {
				$data = array();
				$data['uid'] = $_G['uid'];
				$data['status'] = 1;
				$data['settings'] = array();
				if($this->weibouserarr) {
					foreach($this->weibouserarr as $sinauser) {
						$data['settings'] = $sinauser['settings'];
						break;
					}
				} else {
					$data['settings']['defaultsinauid'] = $sina_uid;
					$data['settings']['sync'] = $this->adminsettings['syncpublish']['option'];
				}
				$data['settings'] = serialize($data['settings']);
				sina_plugin_tools::get_bind_user()->update($data, $sina_uid);
				updatecreditbyaction('sina_sync_bind', $_G['uid']);
				sina_plugin_tools::get_bind_user()->medal_operate($_G['uid'], 'award');
				sina_plugin_tools::get_bind_user()->set_signature($_G['uid'], $sina_uid, rand(1, 10));
				return true;
			}
		}
		return false;
	}
}

class plugin_sina_login_forum extends plugin_sina_base {
	
	
	function viewthread_sidebottom_output() {
		global $_G, $postlist;
		$returan = $pidlist = array();
		foreach($postlist as $pid => $post) {
			if($this->adminsettings['commentuid'] == $post['authorid']) {
				$pidlist[$pid] = $pid;
			}
		}
		if($pidlist) {
			$sinauserlist = $pushbackdata = $repostdata = array();
			$pushbackdata = sina_plugin_tools::get_bind_pushback()->get_rows_by_ids($pidlist, 'thread');
			$repostdata = sina_plugin_tools::get_bind_pushback_repost()->get_rows_by_ids($pidlist, 'thread');
			$sinauids = array();
			foreach($pushbackdata as $rs) {
				$sinauids[$rs['id']] = $rs['sina_uid'];// pid=>sina_uid
			}
			foreach($repostdata as $rs) {
				$sinauids[$rs['id']] = $rs['sina_uid'];// pid=>sina_uid
			}
			unset($pushbackdata);
			unset($repostdata);
			if($sinauids) {
				$sinauserlist = sina_plugin_tools::get_bind_sina()->get_sina_users_by_sina_uids($sinauids);
			}
			if($sinauserlist) {
				foreach($sinauserlist as $sina_uid => $rs) {
					$rs['profile'] = unserialize($rs['profile']);
					$sinauserlist[$sina_uid] = $rs;
				}
				foreach($postlist as $pid => $post) {
					if($sinauids[$pid] && $sinauserlist[$sinauids[$pid]]) {
						$sina_uid = $sinauids[$pid];
						$rs = $sinauserlist[$sina_uid];
						$postlist[$pid]['avatar'] = "<img width=\"120\" height=\"120\" src=\"".$sinauserlist[$sina_uid]['profile']['avatar_large']."\" />";
						$postlist[$pid]['author'] = $sinauserlist[$sinauids[$pid]]['profile']['screen_name'];
						
						if(in_array('viewthreadweiboinfo', $this->adminsettings['showoption'])) {
							$str = '<div class="tns xg2" id="sinainfo'.$pid.'">
	                                				<table cellspacing="0" cellpadding="0">';
							$href = '<a href="http://www.weibo.com/u/'.$sina_uid.'" target="_blank" class="xi2">';
							$str .= '<th><p>'.$href.$rs['profile']['followers_count'].'</a></p>'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'followers').'</th>';
							$str .= '<th><p>'.$href.$rs['profile']['friends_count'].'</a></p>'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'focus').'</th>';
							$str .= '<td><p>'.$href.$rs['profile']['statuses_count'].'</a></p>'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'weibo').'</td>';
							$str .= '</table></div>';
							$rs['sinainfo'] = $str;
							$js = <<<EOF
<script language="javascript">
var sina_userinfo_$pid = $('userinfo$pid');
for(var i = 0; i < sina_userinfo_$pid.parentNode.childNodes.length; i++) {
	if(sina_userinfo_$pid.parentNode.childNodes[i].nodeName == 'DIV' && sina_userinfo_$pid.parentNode.childNodes[i].getAttribute('class') == 'pi') {
		sina_userinfo_$pid.parentNode.childNodes[i].innerHTML = '<div class="authi"><a href="http://www.weibo.com/u/$sina_uid" target="_blank" class="xw1"><img src="source/plugin/sina_login/img/icon_logo.png">{$rs['profile']['screen_name']}</a></div>';
	}
	if(sina_userinfo_$pid.parentNode.childNodes[i].nodeName == 'DIV' && sina_userinfo_$pid.parentNode.childNodes[i].getAttribute('class') == 'p_pop blk bui') {
		sina_userinfo_$pid.parentNode.childNodes[i].setAttribute('class', '');
	}
}
var profile_side_$pid = $('sinainfo{$pid}');
var sina_child_$pid = profile_side_$pid.parentNode.childNodes;
for(var i=0; i < sina_child_$pid.length; i++) {
	if(sina_child_{$pid}[i].nodeName == 'DIV' && sina_child_{$pid}[i].getAttribute('class') == 'tns xg2' && sina_child_{$pid}[i].getAttribute('id') == undefined) {
		profile_side_$pid.parentNode.removeChild(sina_child_{$pid}[i]);
	}
	if(sina_child_{$pid}[i].nodeName == 'DIV' && sina_child_{$pid}[i].getAttribute('class') == 'avatar') {
		sina_child_{$pid}[i].innerHTML='{$postlist[$pid]['avatar']}';
		sina_child_{$pid}[i].setAttribute('onmouseover', '');
	}
}
</script>
EOF;
							$return[] = $rs['sinainfo'].$js;
						} else {
							$return[] = '';
						}
					} else {
						$return[] = '';
					}
				}
			}
		}
		return $return;
	}

	function index_nav_extra() {
		global $_G;
		if($this->adminsettings['weibofollow']['followopen'] && $this->adminsettings['weibofollow']['focus_position'] == 'index_nav_extra') {
			$return = '';
			if($this->adminsettings['weibofollow']['focus_qqwidth'] == 999){
				$focus_qqwidth = 'width:0px';
			} else if($this->adminsettings['weibofollow']['focus_qqwidth'] > 0){
				$focus_qqwidth = 'width:'.$this->adminsettings['weibofollow']['focus_qqwidth'].'px';
			} else {
				$focus_qqwidth = '';
			}
			include $this->tpl('index_nav_extra');
			return $return;
		}
	}

	function index_status_extra() {
		global $_G;
		if($this->adminsettings['weibofollow']['followopen'] && $this->adminsettings['weibofollow']['focus_position'] == 'status_extra') {
			$return = '';
			if($this->adminsettings['weibofollow']['focus_qqwidth'] == 999){
				$focus_qqwidth = 'width:0px';
			} else if($this->adminsettings['weibofollow']['focus_qqwidth'] > 0){
				$focus_qqwidth = 'width:'.$this->adminsettings['weibofollow']['focus_qqwidth'].'px';
			} else {
				$focus_qqwidth = '';
			}
			include $this->tpl('index_status_extra');
			return $return;
		}
	}
	
	function viewthread_useraction_output() {
		$return = '';
		if($this->adminsettings['syncpublish']['forwarding']) {
			global $_G, $postlist, $thread;
			if(!$this->isbind || !$this->adminsettings['sysncpushback']['pushbackshare'] || !in_array($_G['uid'], $this->adminsettings['sysncpushback']['pushbackshare_uids'])) {
				$rewrite = false;
				if(!$this->adminsettings['syncpublish']['advocate'] && $_G['setting']['rewritestatus'] && in_array('forum_viewthread', $_G['setting']['rewritestatus'])) {
					$rewrite = true;
				} 
				$weibo_share_url = sina_plugin_tools::get_url_by_type('forum_viewthread', $_G['tid'], $rewrite, $this->adminsettings['syncpublish']['advocate']);
				$type = 'thread';
				$return = $post = array();
				$post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
				$thread = array_merge($thread, $post);
				$parser = sina_plugin_tools::get_parseimg();
				$parser->init_gid($_G['group']['groupid']);
				$attachimages = array();
				$thread['message'] = $parser->connectParseBbcode($thread['message'], $thread['fid'], $thread['pid'], $thread['htmlon'], $attachimages);
				$type = 'thread';
				$format = $this->adminsettings['pubtpl'][$type];
				if(!$format) {
					$format = lang('plugin/'.sina_plugin_tools::get_plugin_name(), $type.'format');
				}
				$thread['message'] = trim(str_replace(array('/\s+/', "\n", "\r", "\t", "\n\r"), ' ', strip_tags($thread['message'])));
				$thread['message'] = str_replace('/\s+/', '', $thread['message']);
				$message = sina_plugin_tools::format_weibo($format, $thread['subject'], $thread['message'], '');
				$pre1 = $pic_path = '';
				foreach($attachimages as $img) {
					$pic_path .= $pre1.$img['path'];
					$pre1 = '||';
				}
			}
			include $this->tpl('viewthread_useraction');
		}
		return $return;
	}
	
	function viewthread_bottom_output() {
		global $thread;
		$syncurl = '';
		$syncdata = array();
		
		if(in_array('threads', $this->adminsettings['sysncpushback']['option']) && TIMESTAMP - $thread['dateline'] < self::SYNCAGING) {
			$syncrows = sina_plugin_tools::get_bind_thread()->get_rows_by_tid($thread['tid'], 'thread');
			if($syncrows) {
				foreach($syncrows as $rs) {
					if(TIMESTAMP - $rs['lastpushbacktime'] >= $this->adminsettings['sysncpushback']['ltime'] * 60) {
						$syncdata[] = 'type=thread&mid='.$rs['mid'].'&tid='.$rs['tid'];
					}
				}
				if($syncdata) {
					$syncurl = sina_plugin_tools::get_rewrite_url('sync');
				}
			}
		}
		$return = '';
		include $this->tpl('viewthread_bottom');
		return $return;	
	}

	function post_middle_output() {
		if($_GET['action'] != 'edit') {
			if(in_array('threads', $this->adminsettings['syncpublish']['option']) && in_array('threads', $this->usersettings['sync'])) {
				$return = '';
				include $this->tpl('sync_tip');
				return $return;
			}
		}
	}
	
	function forumdisplay_fastpost_btn_extra_output() {
		if(in_array('threads', $this->adminsettings['syncpublish']['option']) && in_array('threads', $this->usersettings['sync'])) {
			$return = '';
			include $this->tpl('sync_tip');
			return $return;
		}
	}
	
	function post_infloat_btn_extra_output() {
		if(in_array('threads', $this->adminsettings['syncpublish']['option']) && in_array('threads', $this->usersettings['sync'])) {
			$return = '';
			include $this->tpl('sync_tip');
			return $return;
		}
	}
	
	function viewthread_fastpost_btn_extra() {
	
		if(in_array('threads', $this->adminsettings['syncpublish']['option']) && in_array('threads', $this->usersettings['sync'])) {
			$return = '';
			include $this->tpl('sync_tip');
			return $return;
		}
	}
	
	function _get_sinauser_list() {
		static $sinausers = null;
		if(null !== $sinausers) {
			return $sinausers;
		}
		global $_G, $postusers;
		$uids = array_keys($postusers);
		$sinausers = array();
		foreach($uids as $uid) {
			$users = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($uid);
			if($users) {
				foreach ($users as $rs) {
					if($rs['status'] == 1) {
						$sinausers[$uid] = array('sina_uid' => $rs['sina_uid'], 'profile' => $rs['profile']);
						$rs['settings'] = unserialize($rs['settings']);
						if($rs['settings']['defaultsinauid'] == $rs['sina_uid']) {
							break;
						}
					}
				}
			}
		}
		foreach($sinausers as $uid=>$rs) {
			$rs['profile'] = unserialize($rs['profile']);
			$sinausers[$uid] = $rs;
		}
		return $sinausers;
	}
	
	function viewthread_imicons_output() {
		if(in_array('viewthreadweibodetail', $this->adminsettings['showoption'])) {
			global $_G, $postlist;
			$sinausers = $this->_get_sinauser_list();
			$datalist = array();
			foreach($postlist as $post) {
				$uid = $post['authorid'];
				if($sinausers[$uid]) {
					$datalist[] = '<a href="http://weibo.com/u/'.$sinausers[$uid]['sina_uid'].'" target="_blank"><img src="'.$this->adminsettings['imgurl'].'/sina_logo_1.png" title="'.$sinausers[$uid]['profile']['screen_name'].' '.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'followers_count').':'.$sinausers[$uid]['profile']['followers_count'].' '.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'statuses_count').':'.$sinausers[$uid]['profile']['statuses_count'].'"></a>';
				} else {
					$datalist[] = '<img src="'.$this->adminsettings['imgurl'].'/icon_off.gif" title="'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'unbinduser').'" />';
				}
			}
			
			return $datalist;
		}
	}
	
	function viewthread_sidetop_output() {
		$result = array();
		if(in_array('viewthreadweibomedal', $this->adminsettings['showoption'])) {
			global $_G, $postlist;
			$sinausers = $this->_get_sinauser_list();
			foreach($postlist as $post) {
				$uid = $post['authorid'];
				if($sinausers[$uid]) {
					$result[] = '<p class="md_ctrl"><a href="http://weibo.com/u/'.$sinausers[$uid]['sina_uid'].'" target="_blank"><img src="source/plugin/sina_login/img/light.png" alt="'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'sina_bind_medal').'" title="['.$sinausers[$uid]['profile']['screen_name'].'] '.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'followers_count').':'.$sinausers[$uid]['profile']['followers_count'].' '.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'statuses_count').':'.$sinausers[$uid]['profile']['statuses_count'].'"></a></p>';
				} else {
					$result[] = '<a href="'.sina_plugin_tools::get_rewrite_url('init').'"><p class="md_ctrl"><img src="source/plugin/sina_login/img/gray.png" alt="'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'sina_bind_medal').'" title="'.lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'unbinduser').'"></a></p>';
				}
			}
		}
		return $result;
	}
	
	function post_sync_to_weibo_aftersubmit_message($param) {
		global $_G;
		$code = $this->_check_is_forum_post($param);
		switch($code) {
			case '1':	
				$this->hack_publish('newthread', $param);
				break;
			case '2':
				$this->hack_publish('reply', $param);
				break;
			default:
				break;
		}
	}
	
	private function _check_is_forum_post($param) {
		global $_G;
		if(!$this->isbind) {
			return -1;
		} else if('POST' != $this->get_request_method()) {
			return -2;
		} else if(!$_GET['synctosina']) {
			return -3;
		} else if(!in_array('threads', $this->adminsettings['syncpublish']['option'])) {
			return -4;
		} else if(!in_array('threads', $this->usersettings['sync'])) {
			return -5;
		} else if($_GET['action'] == 'newthread' && submitcheck('topicsubmit') && isset($param['param'][2]['tid']) && ($param['param'][2]['tid'] > 0)) {
			return 1;
		} else if($_GET['action'] == 'reply' && submitcheck('replysubmit') && isset($param['param'][2]['pid']) && ($param['param'][2]['pid'] > 0)) {
			return 2;
		} else {
			return 0;
		}
	}
}

class plugin_sina_login_group extends plugin_sina_login_forum {}

class plugin_sina_login_portal extends plugin_sina_base {
	
	function portalcp_bottom() {
		if($_GET['op'] != 'edit' && in_array('portal', $this->adminsettings['syncpublish']['option']) && in_array('portal', $this->usersettings['sync'])) {
			global $_G;
			$return = '';
			include $this->tpl('sync_tip');
			return $return;
		}
	}
	
	function portalcp_sync_to_weibo_aftersubmit_output(){
		global $_G;
		if($_GET['synctosina'] && in_array('portal', $this->adminsettings['syncpublish']['option'])) {
			if($this->isbind && 'POST' == $this->get_request_method() && 'article' == getgpc('ac') && in_array('portal', $this->usersettings['sync']) && $GLOBALS['aid'] > 0) {
				$this->hack_publish('newarticle', $_POST);
			}
		}
	}
	
	function view_article_op_extra_output() {
		global $_G, $article;
		$syncdata = array();
		$syncurl = '';
		if(in_array('portal', $this->adminsettings['sysncpushback']['option']) && TIMESTAMP - strtotime($article['dateline']) < self::SYNCAGING) {
			$syncrows = sina_plugin_tools::get_bind_thread()->get_rows_by_tid($article['aid'], 'portal');
			if($syncrows) {
				foreach($syncrows as $rs) {
					if(TIMESTAMP - $rs['lastpushbacktime'] >= $this->adminsettings['sysncpushback']['ltime'] * 60) {
						$syncdata[] = 'type=portal&mid='.$rs['mid'].'&tid='.$rs['tid'];
					}
				}
				if($syncdata) {
					$syncurl = sina_plugin_tools::get_rewrite_url('sync');
				}
			}
		}
		if($this->adminsettings['syncpublish']['forwarding']) {
			if(!$this->isbind || !$this->adminsettings['sysncpushback']['pushbackshare'] || !in_array($_G['uid'], $this->adminsettings['sysncpushback']['pushbackshare_uids'])) {
				$rewrite = false;
				if(!$this->adminsettings['syncpublish']['advocate'] && $_G['setting']['rewritestatus'] && in_array('portal_article', $_G['setting']['rewritestatus'])) {
					$rewrite = true;
				}
				$url = sina_plugin_tools::get_url_by_type('portal_article', $article['aid'], $rewrite, $this->adminsettings['syncpublish']['advocate']);
				$format = $this->adminsettings['pubtpl']['article'];
				if(!$format) {
					$format = lang('plugin/'.sina_plugin_tools::get_plugin_name(), $type.'format');
				}
				if($article['summary']) {
					$content = $article['summary'];
				} else {
					$article_content = C::t('portal_article_content')->fetch_by_aid_page($article['aid'], 1);
					require_once libfile('function/blog');
					$article_content['content'] = blog_bbcode($article_content['content']);
					$content = trim(str_replace(array('/\s+/', "\n", "\r", "\t", "\n\r"), ' ', strip_tags($article_content['content'])));
				}
				$cat = $_G['cache']['portalcategory'][$article['catid']];
				$message = sina_plugin_tools::format_weibo($format, $article['title'], $content, '', array('catname' => $cat['catname']));
				$attachments = C::t('portal_attachment')->fetch_all_by_aid($article['aid']);
				$pics = '';
				if($attachments) {
					$pre = '';
					foreach($attachments as $val) {
						if($val['isimage']) {
							$path = $_G['setting']['attachurl'].'portal/'.$val['attachment'];
							if(strpos($path, 'http') === false) {
								$path = $_G['siteurl'].$path;
							}
							$pics .= $pre.$path;
							$pre = '||';
						}
					}
				}
			}
		}
		$return = '';
		include $this->tpl('view_article_op_extra');
		return $return;
	}
}

class plugin_sina_login_home extends plugin_sina_base {
	
	function space_profile_baseinfo_bottom_output() {
		if($this->adminsettings['use_signature']) {
			return false;
		}
		global $uid, $_G;
		if($_G['uid'] != $uid) {
			$sinausers = array();
			foreach(sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($uid) as $rs) {	
				if($rs['status'] == 1) {
					$sinausers[] = $rs;
				}
			}
		} else {
			$sinausers = $this->weibouserarr;
		}
		$return = '';
		include $this->tpl('space_profile_baseinfo_bottom');
		return $return;	
	}
	
	function space_blog_op_extra_output() {
		global $blog;
		$syncdata = array();
		$syncurl = '';
		if(in_array('blog', $this->adminsettings['sysncpushback']['option']) && TIMESTAMP - $blog['dateline'] < self::SYNCAGING) {
			$syncrows = sina_plugin_tools::get_bind_thread()->get_rows_by_tid($blog['blogid'], 'blog');
			if($syncrows) {
				foreach($syncrows as $rs) {
					if(TIMESTAMP - $rs['lastpushbacktime'] >= $this->adminsettings['sysncpushback']['ltime'] * 60) {
						$syncdata[] = 'type=blog&mid='.$rs['mid'].'&tid='.$rs['tid'];
					}
				}
				if($syncdata) {
					$syncurl = sina_plugin_tools::get_rewrite_url('sync');
				}
			}
		}
		$return = '';
		include $this->tpl('space_blog_op_extra');
		return $return;
	}
	
	function spacecp_blog_middle_output(){
		if($_GET['op'] != 'edit' && in_array('blog', $this->adminsettings['syncpublish']['option']) && in_array('blog', $this->usersettings['sync'])) {
			$return = '';
			include $this->tpl('sync_tip');
			return $return;
		}
	}
	
	
	function spacecp_blog_sync_to_weibo_aftersubmit_message($param){
		if(in_array('blog', $this->adminsettings['syncpublish']['option'])) {
			if($_GET['synctosina'] && $this->isbind && in_array('blog', $this->usersettings['sync']) && getgpc('blogsubmit') && 'POST' == $this->get_request_method() && $param['param'][0] == 'do_success') {
				if($GLOBALS['newblog']['friend'] <= 1) {
					$this->hack_publish('newblog', $param);
				}	
			}
		}
	}
	
	function spacecp_doing_aftersubmit_message($param) {
		if(in_array('doing', $this->adminsettings['syncpublish']['option'])) {
			if($this->isbind && in_array('doing', $this->usersettings['sync']) && submitcheck('addsubmit') && substr($param['param'][0], -8) == '_success' && $param['param'][2]['doid'] > 0) {
				$this->hack_publish('newdoing', $param['param'][2]['doid']);
			}
		}
	}
	
	function spacecp_follow_aftersubmit_message($param) {
		if(in_array('follow', $this->adminsettings['syncpublish']['option'])) {
			if($this->isbind && in_array('follow', $this->usersettings['sync']) && submitcheck('topicsubmit') && $param['param'][0] == 'post_newthread_succeed' && $param['param'][2]['pid'] > 0) {
				$this->hack_publish('newfollow', $param);
			}
		}
	}
	
	function spacecp_share_aftersubmit_message($param) {
		if(in_array('share', $this->adminsettings['syncpublish']['option'])) {
			if($this->isbind && in_array('share', $this->usersettings['sync']) && getgpc('sharesubmit') && substr($param['param'][0], -8) == '_success' && $param['param'][2]['sid'] > 0) {
				$this->hack_publish('newshare', $param['param'][2]['sid']);
			}
		}
	}
}
class mobileplugin_sina_login extends plugin_sina_base {
}
class mobileplugin_sina_login_forum extends mobileplugin_sina_login {

	function viewthread_postbottom_mobile_output() {
		global $_G, $postlist, $thread;
		$rewrite = false;
		if(!$this->adminsettings['syncpublish']['advocate'] && $_G['setting']['rewritestatus'] && in_array('forum_viewthread', $_G['setting']['rewritestatus'])) {
			$rewrite = true;
		} 
		$weibo_share_url = sina_plugin_tools::get_url_by_type('forum_viewthread', $_G['tid'], $rewrite, $this->adminsettings['syncpublish']['advocate']);
		$type = 'thread';
		$return = $post = array();
		$post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
		$thread = array_merge($thread, $post);
		$parser = sina_plugin_tools::get_parseimg();
		$parser->init_gid($_G['group']['groupid']);
		$attachimages = array();
		$thread['message'] = $parser->connectParseBbcode($thread['message'], $thread['fid'], $thread['pid'], $thread['htmlon'], $attachimages);
		$type = 'thread';
		$format = $this->adminsettings['pubtpl'][$type];
		if(!$format) {
			$format = lang('plugin/'.sina_plugin_tools::get_plugin_name(), $type.'format');
		}
		$thread['message'] = trim(str_replace(array('/\s+/', "\n", "\r", "\t", "\n\r"), ' ', strip_tags($thread['message'])));
		$thread['message'] = str_replace('/\s+/', '', $thread['message']);
		$message = sina_plugin_tools::format_weibo($format, $thread['subject'], $thread['message'], '');
		$pics = $pre1 = '';
		foreach($attachimages as $img) {
			$pics .= $pre1.$img['path'];
			$pre1 = '||';
		}
		$mobileshare = '';
		include $this->tpl('mobileshare');
		foreach($postlist as $key => $val){
			$val['message'] .= $mobileshare;
			$postlist[$key] = $val;
			break;
		}
		return $return;
	}
}
?>
