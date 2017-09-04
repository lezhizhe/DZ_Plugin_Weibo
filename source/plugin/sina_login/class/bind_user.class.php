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

class sina_bind_user {
	
	private $table_obj = null;
	
	function sina_bind_user() {

		$this->table_obj = C::t('#'.sina_plugin_tools::get_plugin_name().'#sina_bind_user');
	}
	
	function insert($data, $return_insert_id = false, $replace = false, $silent = false) {

		$this->table_obj->insert($data, $return_insert_id, $replace, $silent);
	}

	function delete_by_uid($uid) {
		
		$this->table_obj->delete_by_uid($uid);
	}

	function delete_by_sina_uid($sina_uid) {
		
		$this->table_obj->delete_by_sina_uid($sina_uid);
	}
	
	function update($data, $sina_uid) {
		
		$this->table_obj->update($data, $sina_uid);
	}
	
	function get_user_by_sina_uid($sina_uid) {
		
		return $this->table_obj->get_user_by_sina_uid($sina_uid);
	}
	
	function get_sina_users_by_uid($uid) {
		
		return $this->table_obj->get_sina_users_by_uid($uid);
	}
	
	function user_register($username, $sina_uid, $password, $force = false) {
		global $_G;
		loaducenter();
        $checkusername = uc_user_checkname($username);
        while($checkusername < 0) {
            $username = mb_substr($username, 0, 14, CHARSET).random(1);
            $checkusername = uc_user_checkname($username);
        }
        $email = $sina_uid.random(2).'@sina.com.cn';
        $checkemail = uc_user_checkemail($email);
        while($checkemail < 0) {
            $email = $sina_uid.random(2).'@sina.com.cn';
            $checkemail = uc_user_checkemail($email);
        }
		$uid = uc_user_register(addslashes($username), addslashes($password), addslashes($email), '', '', $_G['clientip']);
		if($_G ['setting']['regverify']) {
			$groupid = 8;
		} else {
			$groupid = $_G ['setting']['newusergroupid'];
		}
		if($uid > 0) {
			$init_arr = array('credits' => explode(',', $_G ['setting']['initcredits']), 'profile'=>array(), 'emailstatus' => 0);
			C::t('common_member')->insert($uid, $username, $password, $email, $_G['clientip'], $groupid, $init_arr);
		}
		return $uid;
	}
	
	function set_signature($uid, $sina_uid, $style = 1) {
		$admin_settings = &sina_plugin_tools::get_plugin_admin_settings();
		if($admin_settings['use_signature']) {
			$userinfo = C::t('common_member_field_forum')->fetch($uid);
			if(empty($userinfo['sightml'])) {
				$sinausers = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($uid);
				foreach($sinausers as $rs) {
					if($rs['status'] == 1) {
						$rs['settings'] = unserialize($rs['settings']);
						if($sina_uid = $rs['settings']['defaultsinauid']) {
							$signature = '<a href="http://weibo.com/u/'.$sina_uid.'?s=6uyXnP" target="_blank"><img id="aimg_'.random(4).'" onclick="zoom(this, this.src, 0, 0, 0)" class="zoom" src="http://service.t.sina.com.cn/widget/qmd/'.$sina_uid.'/12ce62e9/'.$style.'.png" onmouseover="img_onmouseoverfunc(this)" onload="thumbImg(this)" border="0" alt="" /></a>';
							$data = array('sightml' => $signature);
							return C::t('common_member_field_forum')->update($uid, $data);
						}
						break;
					}
				}
			}
		}
		return false;
	}

	function medal_operate($uid, $operate = 'award') {
		$admin_settings = &sina_plugin_tools::get_plugin_admin_settings();
		if($admin_settings['medalid'] < 1) {
			return ;
		}
	
		$awarded = false;
		$medalarr = array();
		$medalinfo = array();
	
		$medalinfo = DB::fetch_first("SELECT * FROM ".DB::table('forum_medal')." WHERE medalid='{$admin_settings['medalid']}'");
		if(!$medalinfo) {
			sina_plugin_tools::log('medalid not exists', 'medalid not exists at '.($operate == 'award' ? ' award' : ' getback'));
			return ;
		}
	
		$medals = DB::fetch_first("SELECT medals FROM ".DB::table('common_member_field_forum')." WHERE uid='$uid'");
		if($medals['medals']) {
			$medalarr = explode("\t", $medals['medals']);
			if(in_array($admin_settings['medalid'], $medalarr)) {
				$awarded = true;
			}
		}
		
		if($operate == 'award' && !$awarded) {
				
			array_push($medalarr, $admin_settings['medalid']);
			$medalarr = array_unique($medalarr);
			DB::query("UPDATE ".DB::table('common_member_field_forum')." SET medals='".implode("\t", $medalarr)."' WHERE uid='$uid'");
			if(sina_plugin_tools::get_dz_version() == 'X2.5') {
				C::t('common_member_medal')->insert(array('uid' => $uid, 'medalid' => $admin_settings['medalid']), 0, 1);
			}
				
			$data = array(
					'uid' => $uid,
					'medalid' => $admin_settings['medalid'],
					'type' => 0,
					'dateline' => TIMESTAMP,
					'expiration' => $medalinfo['expiration'],
					'status' => empty($medalinfo['expiration']) ? 0 : 1,
			);
			DB::insert('forum_medallog', $data);
		} else if($awarded == 'getback'){
	
			$medalarrtmp = array();
			foreach ($medalarr as $medalid) {
				if($medalid != $admin_settings['medalid']) {
					$medalarrtmp[] = $medalid;
				}
			}
			
			
			DB::query("UPDATE ".DB::table('common_member_field_forum')." SET medals='".implode("\t", $medalarrtmp)."' WHERE uid='$uid'");
			DB::query("UPDATE ".DB::table('forum_medallog')." SET type='4' WHERE uid='{$uid}' AND medalid='{$admin_settings['medalid']}'");
			C::t('common_member_medal')->delete_by_uid_medalid($uid, $admin_settings['medalid']);
		}
	}
}

?>
