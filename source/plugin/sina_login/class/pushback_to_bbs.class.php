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

class pushback_to_bbs {
	var $pushback_filter;
	var $config;
	var $sinauser;
	var $synctablename;
	var $synctablename_repost;
	function pushback_to_bbs() {
		$this->config = &sina_plugin_tools::get_plugin_admin_settings();
		$this->pushback_filter = sina_plugin_tools::get_pushback_filter();
		$this->synctablename = 'plugin_sina_sync_bind_pushback';
		$this->synctablename_repost = 'plugin_sina_sync_bind_pushback_repost';
	}
	
	function pushback($type, $mid, $tid) {
		if($this->_check($tid, $type, $mid)) {
			if($this->_lock($tid, $type, $mid) > 0) {
				global $_G;
				$maxcid = $this->_getmaxcid($mid);
				$client = sina_plugin_tools::get_client($this->config['appkey'], $this->config['appsecret'], $this->sinauser['access_token']);

				$comments = $client->get_comments_by_sid($mid, 1, 20, $maxcid);
				if($comments['total_number'] > 0 && is_array($comments['comments'])) {
					
					$func = '_do'.$type;
					$comments_list = array();
					foreach($comments['comments'] as $com) {
						$comments_list[$com['id']] = $com;
					}
					$comids = array_keys($comments_list);
					ksort($comments_list);
					foreach($comments_list as $key => $com) {
						if('gbk' == strtolower(CHARSET)) {
							$com = sina_plugin_tools::convert($com, 'UTF-8', 'GBK');
						}
						
						$comment = sina_plugin_tools::get_bind_thread()->get_comment_by_mid($com['idstr']);
						if($comment) {
							continue;
						}
						$cid = DB::result_first("SELECT cid FROM ".DB::table($this->synctablename)." WHERE mid='$mid' AND cid='{$com['idstr']}'");
						if(!$cid) {
							$sinauser = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($com['user']['idstr']);
							$com['text'] = $this->_filter($com['text']);
							if($sinauser && $sinauser['status'] == 1) {
								$user = getuserbyuid($sinauser['uid'], 1);
								$userinfo = array('uid' => $sinauser['uid'], 'username' => $user['username']);
							} else {
								$this->_insert_sina_user($com['user']);
								$userinfo = array('uid' => $this->config['commentuid'], 'username' => $this->config['commentusername']);
								if(!$this->config['sysncpushback']['pushback_nocomment']) {
									$com['text'].= "\n\t\t\t".lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'comment_from_sina_user').':<a href="http://www.weibo.com/u/'.$com['user']['idstr'].'" target="_blank">'.$com['user']['screen_name'].'<a>';
								}
							}
							$commentid = $this->$func($tid, $com['text'], $userinfo);
							$data = array(
								'id'	=> $commentid,
								'type'	=> $type,
								'mid'	=> $mid,
								'cid'	=>	$com['idstr'],
								'sina_uid' => $com['user']['idstr'],
								'synctime'=> TIMESTAMP
							);
							DB::insert($this->synctablename, $data);
						}
					}
				}
				if($this->config['sysncpushback']['pushbackrepost']) {
					$maxrid = $this->_getmaxrid($mid);
					$reposts = $client->repost_timeline($mid, 1, 20, $maxrid);
					if($reposts['total_number'] > 0 && is_array($reposts['reposts'])) {
						$func = '_do'.$type;
						$repost_list = array();
						foreach($reposts['reposts'] as $rs) {
							$repost_list[$rs['idstr']] = $rs;
						}
						ksort($repost_list);
						foreach($repost_list as $key => $repost) {
							if('gbk' == strtolower(CHARSET)) {
								$repost = sina_plugin_tools::convert($repost, 'UTF-8', 'GBK');
							}
					
							$rid = DB::result_first("SELECT rid FROM ".DB::table($this->synctablename_repost)." WHERE mid='$mid' AND rid='{$repost['idstr']}'");
							if(!$rid) {
								$sinauser = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($repost['user']['idstr']);
								$repost['text'] = $this->_filter($repost['text']);
								if($sinauser && $sinauser['status'] == 1) {
									$user = getuserbyuid($sinauser['uid'], 1);
									$userinfo = array('uid' => $sinauser['uid'], 'username' => $user['username']);
								} else {
									$this->_insert_sina_user($repost['user']);
									$userinfo = array('uid' => $this->config['commentuid'], 'username' => $this->config['commentusername']);
									if(!$this->config['sysncpushback']['pushback_nocomment']) {
										$repost['text'].= "\n\t\t\t".lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'comment_from_sina_user').':<a href="http://www.weibo.com/u/'.$repost['user']['idstr'].'" target="_blank">'.$repost['user']['screen_name'].'<a>';
								
									}
								}
								$commentid = $this->$func($tid, $repost['text'], $userinfo);
								$data = array(
										'id'	=> $commentid,
										'type'	=> $type,
										'mid'	=> $mid,
										'rid'	=>	$repost['idstr'],
										'sina_uid' => $repost['user']['idstr'],
										'synctime'=> TIMESTAMP
								);
								DB::insert($this->synctablename_repost, $data);
							}
						}
					}
				}
				$this->_unlock($tid, $type, $mid);
			}
		} 
	}
	
	function _dothread($tid, $message, $userinfo) {
		require_once libfile('function/forum');
		$thread = get_thread_by_tid($tid);
		
		if(!$thread || $thread['special'] > 1) {
			return ;
		}
		return $this->_insertreply($tid, $thread['fid'], $message, $userinfo, $thread['subject']);
	}
	
	function _doreply($pid, $message, $userinfo) {
		require_once libfile('function/forum');
		$post = get_post_by_pid($pid);
		if(!$post) {
			return ;
		}
		return $this->_insertreply($post['tid'], $post['fid'], $message, $userinfo);
	}
	private function _insertreply($tid, $fid, $message, $userinfo, $subject = '') {
		global $_G;

		$message = preg_replace('/<a href="(.*?)" target="_blank">(.*?)<a>/', '[url=\\1]\\2[/url]', $message);
		$message = preg_replace('/&lt;sina:link[ ]+src=&quot;([a-zA-Z0-9]+)&quot;[ a-zA-Z0-9="&;]*\/&gt;/', "[url=http://t.cn/\${1}]http://t.cn/\${1}[/url]", $message);
		$message = trim($message);
		
		$pid = insertpost(array(
				'fid' => $fid,
				'tid' => $tid,
				'first' => '0',
				'author' => $userinfo['username'],
				'authorid' => $userinfo['uid'],
				'subject' => '',
				'dateline' => $_G['timestamp'],
				'message' => $message,
				'useip' => $_G['clientip'],
				'invisible' => 0,
				'anonymous' => 0,
				'usesig' => 0,
				'htmlon' =>	0,
				'bbcodeoff' => 0,
				'smileyoff' => 0,
				'parseurloff' => 0,
				'attachment' => '0',
				'status' =>  0,
		));
		$viewers = rand(2, 16);
		DB::query("UPDATE ".DB::table('forum_thread')." SET lastposter='{$userinfo['username']}', lastpost='{$_G['timestamp']}', replies=replies+1,views=views+{$viewers} WHERE tid='{$tid}'");
		DB::query("UPDATE ".DB::table('common_member_count')." SET posts=posts+1 WHERE uid='{$userinfo['uid']}'");
		
		if(empty($subject)) {
			$thread = get_thread_by_tid($tid);
			$subject = $thread['subject'];
		}
		$lastpost = "$tid\t{$subject}\t{$_G['timestamp']}\t{$userinfo['username']}";
		DB::query("UPDATE ".DB::table('forum_forum')." SET lastpost='{$lastpost}', posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'");
		return $pid;
	}
	
	
	function _doportal($id, $message, $userinfo) {
		global $_G;
		$idtype = 'aid';
		$data = C::t('portal_article_title')->fetch($id);
		if(empty($data)) {
			return 'comment_comment_noexist';
		}
		if($data['allowcomment'] != 1) {
			return 'comment_comment_notallowed';
		}
		
		$setarr = array(
				'uid' => $userinfo['uid'],
				'username' => $userinfo['username'],
				'id' => $id,
				'idtype' => $idtype,
				'postip' => $_G['clientip'],
				'dateline' => $_G['timestamp'],
				'status' => 0,
				'message' => $message
		);
		
		$commentid = C::t('portal_comment')->insert($setarr, true);
		$viewnum = rand(2, 16);
		C::t('portal_article_count')->increase($id, array('commentnum' => 1, 'viewnum' => $viewnum));
		C::t('common_member_status')->update($userinfo['uid'], array('lastpost' => $_G['timestamp']), 'UNBUFFERED');
		return $commentid;
	}
	
	function _doblog($id, $message, $userinfo) {
		global $_G;
		include_once libfile('function/spacecp');
		$blog = array_merge(C::t('home_blog')->fetch($id), C::t('home_blogfield')->fetch_targetids_by_blogid($id));
		if(!$blog || $blog['noreply'] || $blog['friend'] > 1) {
			return ;
		}
		hot_update('blogid', $blog['blogid'], $blog['hotuser']);
		
		$setarr = array(
				'uid' => $blog['uid'],
				'id' => $id,
				'idtype' => 'blogid',
				'authorid' => $userinfo['uid'],
				'author' => $userinfo['username'],
				'dateline' => $_G['timestamp'],
				'message' => $message,
				'ip' => $_G['clientip'],
				'status' => 0,
		);
		
		$commentid = C::t('home_comment')->insert($setarr, true);
		$updatestat = getglobal('setting/updatestat');
		if(!empty($updatestat)) {
			C::t('common_stat')->updatestat($userinfo['uid'], 'blogcomment', 0, 1);
		}
		$viewnum = rand(2, 16);
		C::t('home_blog')->increase($id, $userinfo['uid'], array('viewnum' => $viewnum, 'replynum' => 1));
		C::t('common_member_status')->update($userinfo['uid'], array('lastpost' => $_G['timestamp']), 'UNBUFFERED');
		return $commentid;
	}
	
	protected function _check($tid, $type, $mid) {
		$result = true;
		$allow_type = array(
			'thread' => 'threads',
			'reply'  => 'threads',
			'portal' => 'portal',
			'blog'	 => 'blog',
		);
		
		if(!in_array($allow_type[$type], $this->config['sysncpushback']['option'])) {
			return false;
		}
		
		$syncinfo = sina_plugin_tools::get_bind_thread()->get_row_by_mid($mid);
		if(!$syncinfo || $mid != $syncinfo['mid'] || $syncinfo['iscomment'] || (TIMESTAMP - $syncinfo['synctime']) > 6048000) {
			$result = false;
		}
		
		if($this->config['sysncpushback']['ltime'] < 1) {
			$this->config['sysncpushback']['ltime'] = 5;
		}
		if((TIMESTAMP - $syncinfo['lastpushbacktime']) < $this->config['sysncpushback']['ltime']*60) {
			$result = false;
		}
		
		if($syncinfo['status'] == 1) {
			if((TIMESTAMP - $syncinfo['lastpushbacktime']) > $this->config['sysncpushback']['ltime']*60*2) {
				$result = DB::query("UPDATE ".DB::table('plugin_sina_sync_bind_thread')." SET status='2' WHERE tid='{$tid}' AND type='{$type}' AND mid='{$mid}' AND status='1'") > 0;
			}
			$result = false;
		}
		
		if($result) {
			$this->sinauser = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($syncinfo['sina_uid']);
		}

		return $result;
	}
	
	protected function _lock($tid, $type, $mid) {

		return DB::query("UPDATE ".DB::table('plugin_sina_sync_bind_thread')." SET status='1' WHERE tid='{$tid}' AND type='{$type}' AND mid='{$mid}' AND status='2'");
	}
	
	protected function _unlock($tid, $type, $mid) {

		return DB::query("UPDATE ".DB::table('plugin_sina_sync_bind_thread')." SET status='2', lastpushbacktime='".TIMESTAMP."' WHERE tid='{$tid}' AND type='{$type}' AND mid='{$mid}' AND status='1'");
	}
	
	protected function _getmaxcid($mid) {

		$maxcid = DB::result_first("SELECT MAX(cid) FROM ".DB::table($this->synctablename)." WHERE mid='$mid'");
		if($maxcid > 0) {
			return $maxcid;
		}
		return 0;
	}
	
	protected function _getmaxrid($mid) {

		$maxrid = DB::result_first("SELECT MAX(rid) FROM ".DB::table($this->synctablename_repost)." WHERE mid='$mid'");
		if($maxrid > 0) {
			return $maxrid;
		}
		return 0;
	}
	
	public function _insert_sina_user($user) {

		$data = array();
		$data['sina_uid'] = trim($user['idstr']);
		$data['status'] = 0;
		$data['profile'] = serialize($user);
		sina_plugin_tools::get_bind_sina()->insert($data, false, true);
	}

	private function _filter($text) {

		if(is_array($this->pushback_filter)) {
            $pattern = array();
            $replacements = array();
			foreach($this->pushback_filter as $key => $value) {
				if($value) {
                    $random = rand(0, count($value) - 1);
                    $pattern[] = '/'.$key.'/';
                    $replacements[] = $value[$random];
				}
			}
            $result =  preg_replace($pattern, $replacements, $text);
            return $result;
		}
		return $text;
	}
}
?>
