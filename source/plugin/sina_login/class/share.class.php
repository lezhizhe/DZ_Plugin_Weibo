<?php
/**
 *	[http://www.lezhizhe.net] (C)2012-2099 lezhizhe_net.
 *  This is NOT a freeware, use is subject to license terms.
 *
 * @author				lezhizhe_net<caoziqiang163@126.com>
 * @copyright 			lezhizhe.net
 */
if(!defined('IN_DISCUZ')) {
	die('Access Denied');
}

class sina_bind_share {
	
	protected $config = array();
	
	function init_config($config) {
		$this->config = $config;
		return $this;
	}
	
	function execute($do) {
		global $_G;
		if(!$this->config['sysncpushback']['pushbackshare'] || !in_array($_G['uid'], $this->config['sysncpushback']['pushbackshare_uids'])) {
			die('Access Denied');
		}
		switch ($do) {
			case 'new':
				$this->new_share();
				break;
			case 'share':
				$this->do_share();
				break;
			default:
				die('Access Denied');
		}
	}
	
	function new_share() {
		global $_G;
		$return = false;
		$type = trim($_GET['type']);
		$tid = intval($_GET['tid']);
		if(!in_array($type, array('thread', 'portal', 'blog'))) {
			$type = 'thread';
		}
		switch ($type) {
			case 'thread':
				include_once libfile('function/forum');
				$thread = get_thread_by_tid($tid);
				$post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($tid);
				$thread = array_merge($thread, $post);
				$parser = sina_plugin_tools::get_parseimg();
				$parser->init_gid($_G['group']['groupid']);
				$attachimages = array();
				$thread['message'] = $parser->connectParseBbcode($thread['message'], $thread['fid'], $thread['pid'], $thread['htmlon'], $attachimages);
				$rewrite = false;
				if(!$this->config['syncpublish']['advocate'] && $_G['setting']['rewritestatus'] && in_array('forum_viewthread', $_G['setting']['rewritestatus'])) {
					$rewrite = true;
				} 
				$url = sina_plugin_tools::get_url_by_type('forum_viewthread', $tid, $rewrite, $this->config['syncpublish']['advocate']);
				$url = sina_plugin_tools::get_short_url($url, $this->config['appkey'], $this->config['appsecret']);
				$format = $this->config['pubtpl'][$type];
				if(!$format) {
					$format = lang('plugin/'.sina_plugin_tools::get_plugin_name(), $type.'format');
				}
				$thread['message'] = trim(str_replace(array('/\s+/', "\n", "\r", "\t", "\n\r"), ' ', strip_tags($thread['message'])));
				$thread['message'] = str_replace('/\s+/', '', $thread['message']);
				$message = sina_plugin_tools::format_weibo($format, $thread['subject'], $thread['message'], $url);
				if($attachimages) {
					$thread['images'] = array_slice($attachimages, 0, 8);
					$pre = '';
					foreach($thread['images'] as $img) {
						$aids .= $pre.$img['aid'];
						$pre = ',';
					}
					$selectedaid = $thread['images'][0]['aid'];
				}
				break;
			case 'portal':
				$aid = $tid;
				$rewrite = false;
				if(!$this->config['syncpublish']['advocate'] && $_G['setting']['rewritestatus'] && in_array('portal_article', $_G['setting']['rewritestatus'])) {
					$rewrite = true;
				}
				$url = sina_plugin_tools::get_url_by_type('portal_article', $aid, $rewrite, $this->config['syncpublish']['advocate']);
				$url = sina_plugin_tools::get_short_url($url, $this->config['appkey'], $this->config['appsecret']);
				$format = $this->config['pubtpl']['article'];
				if(!$format) {
					$format = lang('plugin/'.sina_plugin_tools::get_plugin_name(), $type.'format');
				}
				$article = C::t('portal_article_title')->fetch($aid);
				if($article['summary']) {
					$content = $article['summary'];
				} else {
					$article_content = C::t('portal_article_content')->fetch_by_aid_page($aid, 1);
					require_once libfile('function/blog');
					$article_content['content'] = blog_bbcode($article_content['content']);
					$content = trim(str_replace(array('/\s+/', "\n", "\r", "\t", "\n\r"), ' ', strip_tags($article_content['content'])));
				}
				$message = sina_plugin_tools::format_weibo($format, $article['title'], $content, $url);
				$thread = array('images' => array());
				$attachments = C::t('portal_attachment')->fetch_all_by_aid($aid);
				if($attachments) {
					$i = 0;
					$pre = '';
					foreach($attachments as $val) {
						if($val['isimage']) {
							$aid = $val['attachid'];
							$aids .= $pre.$aid;
							$path = $_G['setting']['attachurl'].'portal/'.$val['attachment'];
							if(strpos($path, 'http') === false) {
								$path = $_G['siteurl'].$path;
							}
							$thread['images'][] = array('aid' => $aid, 'thumb' => $path, 'path' => $path);
						}
						if($i++ >= 8) {
							break;
						}
						$pre = ',';
					}
					if($i > 0) {
						$selectedaid = $thread['images'][0]['aid'];
					}
				}
				break;
			case 'blog':
				
				break;
		}
		$formurl = "plugin.php?id=sina_login:index&operation=share&do=share&type={$type}";
		include template(sina_plugin_tools::get_plugin_name().':share');
		exit;
	}
	
	function do_share() {
		global $_G;
		if(submitcheck('weibo_repost')) {
			$code = 0;
			$tid = intval($_POST['tid']);
			$type = trim($_GET['type']);
			if(!in_array($type, array('thread', 'portal', 'blog'))) {
				$type = 'thread';
			}
			$status = trim($_POST['sina_reason']);
			$pic = trim($_POST['sina_attach_image']);
			$sinauserarr = array();
			$sinadata = sina_plugin_tools::get_bind_user()->get_sina_users_by_uid($_G['uid']);
			if($sinadata) {
				foreach($sinadata as $rs) {
					if($rs['status'] == 1) {
						if($rs['oauth_time'] + $rs['expires_in'] > TIMESTAMP) {
							$rs['settings'] = unserialize($rs['settings']);
							$rs['profile'] = unserialize($rs['profile']);
							$sinauserarr[$rs['sina_uid']] = $rs;
						}
					}
				}
				if($sinauserarr) {
					$result = false;
					foreach($sinauserarr as $sina) {
						$client = sina_plugin_tools::get_client($this->config['appkey'], $this->config['appsecret'], $sina['access_token']);
						if($pic) {
							if($this->config['syncpublish']['upload_url_text']) {
								$response = $client->upload_url_text($status, $pic);
							} else {
								$response = $client->upload($status, $pic);
							}
						} else {
							$response = $client->update($status);
						}
						if(!$response || $response['error']) {
							sina_plugin_tools::log('share to weibo error type: '.$type.', tid:'.$tid.' sina_uid:'.$sina['sina_uid'].' uid:'.$_G['uid'], array('status' => $status, 'pic' => $pic, 'response' => $response));
						} else {
							$data = array();
							$data['sina_uid'] = $sina['sina_uid'];
							$data['mid'] = trim($response['idstr']);
							$data['tid'] = $tid;
							$data['type'] = $type;
							$data['iscomment'] = 0;
							$data['synctime'] = strtotime($response['created_at']);
							$data['lastpushbacktime'] = $data['synctime'];
							sina_plugin_tools::get_bind_thread()->insert($data);
							$result = true;
						}
					}
				}
			}
			
			include template('common/header_ajax');
			if($result) {	
				echo 'ok';
			} else {
				echo 'failed';
			}
			include template('common/footer_ajax');
			exit;
		}
	}
}
