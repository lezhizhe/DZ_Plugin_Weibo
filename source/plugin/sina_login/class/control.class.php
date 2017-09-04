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

 
class sina_control {
	
	protected $config = array();
	
	function _call($func, $args = array()) {
		if(method_exists($this, $func) && $func{0} != '_') {
			$this->$func($args);
		} else {
			die('Method does not exists');
		}
	}
	
	private function _init() {
		global $_G;
		require_once dirname(__FILE__).'/tools.class.php';
		$this->config	=	&sina_plugin_tools::get_plugin_admin_settings();
		$this->config['callbackurl'] = $_G['siteurl'].sina_plugin_tools::get_rewrite_url('callback');
	}
	
	function sina_control() {
		$this->_init();
	}
	
	function doinit($forcelogin = 'false') {
		$oauth2 = sina_plugin_tools::get_oauth2($this->config['appkey'], $this->config['appsecret'], null);
		header('Location:'.$oauth2->getAuthorizeURL($this->config['callbackurl'], 'code', sina_plugin_tools::get_oauth_state(), null, $forcelogin));
	}
	
	function dofinit() {
		$this->doinit('true');
	}

	function docallback() {
		global $_G;
		
		if (isset($_REQUEST['code'])) {
			
			$state = explode('___', $_REQUEST['state']);
			$referer = urldecode($state[1]);

			if(!$_G['cookie'][$state[0]]) {
				showmessage('', $_G['siteurl'], array(), array('header' => true));
			}
			dsetcookie($state[0]);
			
			$oauth2 = sina_plugin_tools::get_oauth2($this->config['appkey'], $this->config['appsecret'], null);
			try {
				$token = $oauth2->getAccessToken('code', array('code' => $_REQUEST['code'], 'redirect_uri' => $this->config['callbackurl']));
			} catch (OAuthException $e) {
				sina_plugin_tools::log('get_access_token_failed', $e->getMessage());
				showmessage(sina_plugin_tools::get_plugin_name().':get_sina_access_token_failed', $_G['siteurl']);
			}
			
			if($token) {
				if($token['uid'] < 1) {
					showmessage(sina_plugin_tools::get_plugin_name().':get_sina_uid_failed', $_G['siteurl']);
				}
				
				$table_bind_user = sina_plugin_tools::get_bind_user();
				$binduserinfo = array();
				$binduserinfo = $table_bind_user->get_user_by_sina_uid($token['uid']);
				$data = array();
				
				$data['access_token'] = $token['access_token'];
				$data['oauth_time'] = TIMESTAMP;
				$data['expires_in'] = $token['expires_in'];
				$data['profile'] = serialize($this->_get_sina_user_info($token));
				
				if($this->config['quick_register'] && (!$_G['uid'] && (!$binduserinfo || $binduserinfo['uid'] < 1))) {
					$data['sina_uid'] = $token['uid'];
					$profile = unserialize($data['profile']);
					$username = mb_substr($profile['screen_name'], 0, 7, CHARSET);
					$password = random(8);
					$uid = $table_bind_user->user_register($username, $data['sina_uid'], $password);
					if($uid < 0) {
						sina_plugin_tools::log('direct login failed!', $token);
						showmessage(sina_plugin_tools::get_plugin_name().':login_failed', $referer ? $referer : $_G['siteurl']);
					}
					$login = $this->_bind_login($uid);
					require_once libfile('cache/userstats', 'function');
					build_cache_userstats();
					
					if(false === $login) {
						showmessage(sina_plugin_tools::get_plugin_name().':login_failed', $referer ? $referer : $_G['siteurl']);
					} else {
						$data['uid'] = $uid;
						$data['status'] = 1;
						$data['settings'] =  serialize($this->_get_user_default_settings($uid, $token['uid']));
						$table_bind_user->insert($data, false, true);
						$table_bind_user->medal_operate($uid, 'award');
						$table_bind_user->set_signature($uid, $data['sina_uid'], rand(1, 10));
						dsetcookie(sina_plugin_tools::get_plugin_name().'_shareregister', authcode($data['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()));
						$this->_oauth2_credit($uid, 'bind');
						$this->_oauth2_credit($uid, 'login');
						require_once libfile('function/member');
						$welcomemsg = & $_G['setting']['welcomemsg'];
						$welcomemsgtitle = & $_G['setting']['welcomemsgtitle'];
						$welcomemsgtxt = & $_G['setting']['welcomemsgtxt'];
						if($welcomemsg && !empty($welcomemsgtxt)) {
							$welcomemsgtitle = replacesitevar($welcomemsgtitle);
							$welcomemsgtxt = replacesitevar($welcomemsgtxt);
							if($welcomemsg == 1) {
								$welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
								notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
							} elseif($welcomemsg == 3) {
								$welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
								notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
							}
						}
						$passwordmsg = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'register_pwd_tip');
						$passwordmsg = str_replace('{passward}', $password, $passwordmsg);
						$passwordmsg = replacesitevar($passwordmsg);
						notification_add($uid, 'system', $passwordmsg, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
						showmessage('login_succeed', $referer, $login['param'], array('extrajs' => $login['ucsynlogin']));
					}
				}
				
				if($binduserinfo) {
					
					if($binduserinfo['uid'] && $binduserinfo['status'] == 1) {
						if(!$_G['uid']) {
							$userexists = getuserbyuid($binduserinfo['uid'], 1);
							if(!$userexists) {
								dsetcookie('sina_'.sina_plugin_tools::get_plugin_name(), authcode($data['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()), TIMESTAMP + 120);
								$message = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'after_bind_message');
								$message = str_replace('{register}', 'member.php?mod='.$_G['setting']['regname'].'&referer='.$referer, $message);
								$message = str_replace('{login}', 'member.php?mod=logging&action=login&referer='.$referer, $message);
								showmessage($message, 'member.php?mod='.$_G['setting']['regname'].'&referer='.$referer, array(), array('refreshtime' => 3));
							} else {
								$login = $this->_bind_login($binduserinfo['uid']);
								if(false === $login) {
									showmessage(sina_plugin_tools::get_plugin_name().':login_failed', $referer ? $referer : $_G['siteurl']);
								} else {
									$this->_oauth2_credit($_G['uid'], 'login');
									$table_bind_user->update($data, $binduserinfo['sina_uid']);							
									showmessage('login_succeed', $referer, $login['param'], array('extrajs' => $login['ucsynlogin']));
								}
							}
						} else if($_G['uid'] == $binduserinfo['uid']) {
							
							$table_bind_user->update($data, $binduserinfo['sina_uid']);
							$this->_oauth2_credit($_G['uid'], 'extend');
							showmessage('',  $referer ? $referer : $_G['siteurl'], array(), array('header' => true));
						} else {
							$userexists = getuserbyuid($binduserinfo['uid'], 1);
							if($userexists['uid'] > 0) {
								showmessage(sina_plugin_tools::get_plugin_name().':sina_user_has_bind_by_other', $referer ? $referer : $_G['siterul']);
							} else {
								$data['uid'] = $_G['uid'];
								$data['status']	= 1;
								$data['settings'] = serialize($this->_get_user_default_settings($_G['uid'], $binduserinfo['sina_uid']));
								$table_bind_user->update($data, $binduserinfo['sina_uid']);
								$this->_oauth2_credit($_G['uid'], 'bind');
								$table_bind_user->medal_operate($_G['uid'], 'award');
								$table_bind_user->set_signature($_G['uid'], $binduserinfo['sina_uid'], rand(1, 10));
								dsetcookie(sina_plugin_tools::get_plugin_name().'_sharebind', authcode($binduserinfo['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()));
								showmessage('',  $referer ? $referer : $_G['siteurl'], array(), array('header' => true));
							}
						}
					} else if($binduserinfo['uid'] && $binduserinfo['status'] == 0 && $this->config['quick_register']) {
						if($_G['uid'] > 0) {
							$data['uid'] = $_G['uid'];
						} else {
							$data['uid'] = $binduserinfo['uid'];
						}
						$login = $this->_bind_login($data['uid']);
						if(false === $login) {
							showmessage(sina_plugin_tools::get_plugin_name().':login_failed', $referer ? $referer : $_G['siteurl']);
						} else {
							$data['status']	= 1;
							$this->_oauth2_credit($binduserinfo['uid'], 'login');
							$table_bind_user->update($data, $binduserinfo['sina_uid']);	
							$table_bind_user->medal_operate($binduserinfo['uid'], 'award');
							$table_bind_user->set_signature($binduserinfo['uid'], $binduserinfo['sina_uid'], rand(1, 10));
							showmessage('login_succeed', $referer, $login['param'], array('extrajs' => $login['ucsynlogin']));
						}
					} else {
						if($_G['uid']) { 
							
							$data['uid'] = $_G['uid'];
							$data['status']	= 1;
							$data['settings'] = serialize($this->_get_user_default_settings($_G['uid'], $binduserinfo['sina_uid']));
							$table_bind_user->update($data, $binduserinfo['sina_uid']);
							$this->_oauth2_credit($_G['uid'], 'bind');
							$table_bind_user->medal_operate($_G['uid'], 'award');
							$table_bind_user->set_signature($_G['uid'], $binduserinfo['sina_uid'], rand(1, 10));
							dsetcookie(sina_plugin_tools::get_plugin_name().'_sharebind', authcode($binduserinfo['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()));
							showmessage('',  $referer ? $referer : $_G['siteurl'], array(), array('header' => true));
						} else {
							
							$table_bind_user->update($data, $binduserinfo['sina_uid']);
							dsetcookie('sina_'.sina_plugin_tools::get_plugin_name(), authcode($binduserinfo['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()), TIMESTAMP + 120);
							$message = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'after_bind_message');
							$message = str_replace('{register}', 'member.php?mod='.$_G['setting']['regname'].'&referer='.$referer, $message);
							$message = str_replace('{login}', 'member.php?mod=logging&action=login&referer='.$referer, $message);
							showmessage($message, 'member.php?mod='.$_G['setting']['regname'].'&referer='.$referer, array(), array('refreshtime' => 3));
						}
					}
				} else { 
					$data['sina_uid'] = $token['uid'];
					if(!$_G['uid']) {
						
						$table_bind_user->insert($data);
						dsetcookie('sina_'.sina_plugin_tools::get_plugin_name(), authcode($data['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()), TIMESTAMP + 120);
						$message = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'after_bind_message');
						$message = str_replace('{register}', 'member.php?mod='.$_G['setting']['regname'].'&referer='.$referer, $message);
						$message = str_replace('{login}', 'member.php?mod=logging&action=login&referer='.$referer, $message);
						showmessage($message, 'member.php?mod='.$_G['setting']['regname'].'&referer='.$referer, array(), array('refreshtime' => 3));
					} else {
						$data['uid'] = $_G['uid'];
						$data['status']	= 1;
						$data['settings'] =  serialize($this->_get_user_default_settings($_G['uid'], $token['uid']));
						$table_bind_user->insert($data);
						$table_bind_user->medal_operate($_G['uid'], 'award');
						$table_bind_user->set_signature($_G['uid'], $data['sina_uid'], rand(1, 10));
						dsetcookie(sina_plugin_tools::get_plugin_name().'_sharebind', authcode($data['sina_uid'], 'ENCODE', sina_plugin_tools::get_plugin_name()));
						$this->_oauth2_credit($_G['uid'], 'bind');
						showmessage('',  $referer ? $referer : $_G['siteurl'], array(), array('header' => true));
					}
				}
				
			} else {
				sina_plugin_tools::log('token_empty', $_REQUEST);
				showmessage(sina_plugin_tools::get_plugin_name().':authorized_failed', $referer);
			}
			
		} else if($_REQUEST['error_code']) {
			sina_plugin_tools::log('authorized_failed', $_REQUEST);
			showmessage(sina_plugin_tools::get_plugin_name().':authorized_failed', $_G['siteurl']);
		} else {
			showmessage('', $_G['siteurl'], array(), array('header' => true));
		}
	}
	
	function dosetting() {
		global $_G;
		if(submitcheck('weibosubmit')) {
			$data = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($_G['uid']);
			$settings = array();
			$settings['sync'] = daddslashes($_POST['syncoption']);
			if(isset($_POST['defaultsinauid'])) {
				$settings['defaultsinauid'] = daddslashes(trim($_POST['defaultsinauid']));
			} else {
				foreach($data as $rs) {
					if($rs['status'] == 1) {
						$settings['defaultsinauid'] = $rs['sina_uid'];
						break;
					}
				}
			}
			$settings = serialize($settings);
			foreach($data as $rs) {	
				sina_plugin_tools::get_bind_user()->update(array('settings' => $settings), $rs['sina_uid']);
			}
			showmessage(sina_plugin_tools::get_plugin_name().':config_succeed', dreferer());
		}
		$data = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($_G['uid']);
		$_G['sina_admin_settings'] = &sina_plugin_tools::get_plugin_admin_settings();
		$_G['sina_admin_settings']['weibofollow']['recomenduids'] = array_slice($_G['sina_admin_settings']['weibofollow']['recomenduids'], 0, 6);
		if($data) {
			
			$sinauserarr = array();
			foreach($data as $rs) {
				if($rs['status'] == 1) {
					$rs['expiretime'] = $rs['oauth_time'] + $rs['expires_in'] - TIMESTAMP;
					$rs['isexpired'] = $rs['expiretime'] <= $_G['sina_admin_settings']['expireoption']['ftime'] * 3600;
					$rs['negative'] = $rs['expiretime'] >= 0 ? false : true;
					
					$rs['expiretime'] = abs($rs['expiretime']);
					$format = '';
					$lang = lang('core', 'date');
					if($rs['expiretime'] > 24*3600) {
						$format .= dintval($rs['expiretime']/(24*3600)).$lang['day'];
						$rs['expiretime'] %= (24*3600);
					}
					if($rs['expiretime'] > 3600) {
						$format .= dintval($rs['expiretime']/3600).$lang['hour'];
						$rs['expiretime'] %= 3600;
					}
					if($rs['expiretime'] > 60) {
						$format .= dintval($rs['expiretime']/60).$lang['min'];
						$rs['expiretime'] %= 60;
					}
					if($rs['expiretime'] > 0) {
						$format .= $rs['expiretime'].$lang['sec'];
					}
					$rs['expire'] = $format;

					$_G['sina_bind_user_settings'] = unserialize($rs['settings']);
					$rs['profile'] = unserialize($rs['profile']);
					$sinauserarr[$rs['sina_uid']] = $rs;
				}
			}
			$_G['sina_bind_user'] = $sinauserarr;
		}
	}
	
	function docancel($args) {
		global $_G;
		if($_G['uid']) {
			
			if(isset($args['sina_uid'])) {
				sina_plugin_tools::get_bind_user()->update(array('status' => 0), $args['sina_uid']);
			}
			$sina_users = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($_G['uid']);
			$getback = true;
			foreach($sina_users as $rs) {
				if($rs['status'] == 1) {
					$getback = false;
					break;
				}
			}
			if($getback) {
				sina_plugin_tools::get_bind_user()->medal_operate($_G['uid'], 'getback');
			} else {
				$usersettings = $this->_get_user_default_settings($_G['uid'], 0);
				if($args['sina_uid'] == $usersettings['defaultsinauid'] || $usersettings['defaultsinauid'] == 0) {
					$defaultsinauid = 0;
					foreach($sina_users as $rs) {
						if($rs['status'] == 1) {
							$usersettings['defaultsinauid'] = $rs['sina_uid'];
							break;
						}
					}
					$usersettings = serialize($usersettings);
					foreach($sina_users as $rs) {
						sina_plugin_tools::get_bind_user()->update(array('settings' => $usersettings), $rs['sina_uid']);
					}
				}
			}
		}
		showmessage(sina_plugin_tools::get_plugin_name().':cancel_succeed', dreferer());
	}
	
	function dosync() {
		$type = trim($_POST['type']);
		$mid = trim($_POST['mid']);
		$tid = dintval(trim($_POST['tid']));
		$type_arr = array('thread', 'reply', 'portal', 'blog');
		if(!in_array($type, $type_arr)) {
			die('Access Diney');
		}
		$pushback_to_bbs = sina_plugin_tools::get_pushback_to_bbs();
		register_shutdown_function(array($pushback_to_bbs, 'pushback'), $type, $mid, $tid);
	}
	
	function dosharebinding() {
		global $_G;
		if($_G['uid']) {
			$type = trim($_GET['type']);
			if(!in_array($type, array('register', 'bind'))) {
				die('Access Diney');
			}
			if(!$this->config['shareoption']['open'] || $this->config['shareoption']['shareforce']) {
				die('Access Diney');
			}
			
			$sina_uid = trim($_GET['sina_uid']);
			$binddata = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($sina_uid);
			if($binddata['uid'] != $_G['uid'] || $binddata['status'] != 1 || TIMESTAMP > $binddata['oauth_time'] + $binddata['expires_in']) {
				return '';
			}
			if(submitcheck('weibo_repost')) {
				$pic_path = $this->config['shareoption']['sharepic'];
				$content = trim($_POST['content']);
				$status = empty($content) ? $this->config['shareoption']['sharecontent'] : $content;
				$client = sina_plugin_tools::get_client($this->config['appkey'], $this->config['appsecret'], $binddata['access_token']);
				if($pic_path) { 
					if($this->config['syncpublish']['upload_url_text']) {
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
				include template('common/header_ajax');
				echo 'ok';
				include template('common/footer_ajax');
				exit;
			} else {
				$url = sina_plugin_tools::get_rewrite_url('sharebinding');
				include template(sina_plugin_tools::get_plugin_name().':sharebinding');
			}
		}
	}
	
	function doshare() {
		global $_G;
		if($_G['uid'] < 1) {
			die('Access Diney');
		}
		$do = trim($_GET['do']);
		sina_plugin_tools::get_share()->init_config($this->config)->execute($do);
	}
	
	function doapi() {
		
		$apitype = trim($_GET['apitype']);
		sina_plugin_tools::get_api()->init_config($this->config)->execute($apitype);
	}
	
	function dofocus() {
		echo <<<EOF
		<html xmlns:wb=“http://open.weibo.com/wb”>
			<head>
			<script src="http://tjs.sjs.sinajs.cn/open/api/js/wb.js" type="text/javascript" charset="utf-8"></script>
			</head>
			<body style="margin:0 auto">
			<wb:follow-button uid="{$this->config['weibofollow']['officialuid']}" type="gray_3" width="100%" height="24" ></wb:follow-button>
			</body>
		</html>
EOF;
	
	}

	private function _bind_login($uid) {
		global $_G;
		
		$member = getuserbyuid($uid);
		if(!$member) {
			return false;
		}
		
		loadcache('usergroups');
		$usergroups = $_G['cache']['usergroups'][$member['groupid']]['grouptitle'];
		$param = array('username' => $_G['member']['username'], 'usergroup' => $usergroups);
		
		require_once libfile('function/member');
		setloginstatus($member, 1296000);
		
		DB::query("UPDATE ".DB::table('common_member_status')." SET lastip='".$_G['clientip']."', lastvisit='".TIMESTAMP."', lastactivity='".TIMESTAMP."' WHERE uid='$uid'");
		$ucsynlogin = '';
		if($_G['setting']['allowsynlogin']) {
			loaducenter();
			$ucsynlogin = uc_user_synlogin($_G['uid']);
		}
		return array('param' => $param, 'ucsynlogin' => $ucsynlogin);
	}
	
	private function _oauth2_credit($uid, $type) {
		switch($type) {
			case 'bind':
				updatecreditbyaction('sina_sync_bind', $uid);
				break;
			case 'extend':
				updatecreditbyaction('sina_sync_extend', $uid);
				break;
			case 'login':
				updatecreditbyaction('sina_sync_login', $uid);
				break;
		}
		
	}

	private function _get_sina_user_info($token) {
		$sinauserinfo = array();
		$client = sina_plugin_tools::get_client($this->config['appkey'], $this->config['appsecret'], $token['access_token']);
		$tmp = $client->show_user_by_id($token['uid']);
		if(!$tmp || $tmp['error']) {
			sina_plugin_tools::log('get_sina_user_info error sina_uid:'.$token['uid'], $tmp);
		}
		$sinauserinfo['screen_name'] = $tmp['screen_name'];
		$sinauserinfo['profile_image_url'] = $tmp['profile_image_url'];
		$sinauserinfo['avatar_large'] = $tmp['avatar_large'];
		$sinauserinfo['profile_url'] = $tmp['profile_url'];
		$sinauserinfo['followers_count'] = $tmp['followers_count'];
		$sinauserinfo['friends_count'] = $tmp['friends_count'];
		$sinauserinfo['statuses_count']= $tmp['statuses_count'];
		if('gbk' == strtolower(CHARSET)) {
			$sinauserinfo = sina_plugin_tools::convert($sinauserinfo, 'UTF-8', 'GBK');
		}
		return $sinauserinfo;
	}
	
	private function _get_user_default_settings($uid, $defaultsinauid) {
		$usersettings = array();
		$usersettings = sina_plugin_tools::get_plugin_user_settings($uid);
		if($usersettings) {
			return $usersettings;
		}
		return array('sync' => $this->config['syncpublish']['option'], 'defaultsinauid' => $defaultsinauid);
	}
}
