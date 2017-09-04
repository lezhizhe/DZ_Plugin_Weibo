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

class publish_to_weibo {
	private $obj = null;
	private $unexpireduser = array();
	function callfunc($func, plugin_sina_base $sina_login_base, $args) {
		$this->obj = $sina_login_base;
		if($this->_get_unexpired_user()) {
			return $this->$func($args);
		}
	}
	
	protected function newthread($param) {
		global $_G, $special;
		if($special < 0 || $special > 5) {
			$special = 0;
		}
		$title = trim($_POST['subject']);
		$url = $this->_get_url('forum_viewthread', $param['param'][2]['tid']);
		$message = trim($this->_filter($_POST['message']));
		$message = $this->_merge_message($title, $message, $url, 'thread');
		$pic_path = $this->_get_picpath_from_post($_POST['message']);
		
		if(empty($pic_path)) {
			$this->_send_weibo($message, $param['param'][2]['tid'], 'thread');
		} else {
			$this->_send_weibo_img($message, $pic_path, $param['param'][2]['tid'], 'thread');
		}
	}
	
	protected function reply($param) {
		global $_G;
		$tid = $param['param'][2]['tid'];
		$pid = $param['param'][2]['pid'];

		$title = $GLOBALS['thread']['subject'];
		$url = $_G['siteurl'].$param['param'][1].($this->obj->adminsettings['syncpublish']['advocate'] ? '&uid='.$_G['uid'] : '');	
		$message = trim($this->_filter($_POST['message']));
		
		$bindthread = sina_plugin_tools::get_bind_thread()->get_row_by_tid($param['param'][2]['tid'], 'thread');
		if($bindthread) {
			$comment = $this->_merge_message('', $message, $url, 'reply');
			$this->_send_comment($bindthread['mid'], $comment, $param['param'][2]['pid'], 'reply');
		}
		if($this->obj->adminsettings['syncpublish']['replynopublish']) {
			return true;
		}
		$message = $this->_merge_message($title, $message, $url, 'reply');
		$pic_path = $this->_get_picpath_from_post($_POST['message']);
		if(empty($pic_path)) {	
			$this->_send_weibo($message, $param['param'][2]['pid'], 'reply');
		} else {
			$this->_send_weibo_img($message, $pic_path, $param['param'][2]['pid'], 'reply');
		}
		
	}
	
	protected function newarticle($post) {
		global $_G;
		
		$pic_path = '';
		if($post['conver']) {
			$converfiles = dunserialize($post['conver']);
			$pic_path = pic_get($converfiles['pic'], '', 0, intval($converfiles['remote']), 1, 1);
			if(substr($pic_path, 0, 4) != 'http') {
				$pic_path = (!$converfiles['remote'] ? $_G['siteurl'] : '').$pic_path;
			}
		}
		if(empty($pic_path)) {
			$pic_path = $this->_get_first_img_from_html($post['content']);
			if(!empty($pic_path) && substr($pic_path, 0, 4) != 'http') {
				$pic_path = $_G['siteurl'].$pic_path;
			}
		}
		
		$title = trim($post['title']);
        $cat = $_G['cache']['portalcategory'][$post['catid']];
		$url = $this->_get_url('portal_article', $GLOBALS['aid']);
		
		if($post['summary']) {
			$message = $post['summary'];
		} else {
			$message = $post['content'];
		}
		$message = strip_tags($message);
		$message = str_replace('/\s+/', ' ', $message);
		$message = preg_replace('/\[flash.*?\](.*?)\[\/flash\]/i', '\1', $message);
		$message = $this->_merge_message($title, $message, $url, 'article', array('catname' => $cat['catname']));
		
		if(empty($pic_path)) {	
			$this->_send_weibo($message, $GLOBALS['aid'], 'portal');
		} else {
			$this->_send_weibo_img($message, $pic_path, $GLOBALS['aid'], 'portal');
		}
	}
	
    protected function comment($param) {
        if($param['type'] == 'portal') {
            $aid = $param['id'];
            $url = $this->_get_url('portal_article', $aid);
            $comment = sina_plugin_tools::substr($param['message'], 0, 138, CHARSET);
            $syncrows = sina_plugin_tools::get_bind_thread()->get_rows_by_tid($aid, 'portal');
            if($syncrows) {
                foreach($syncrows as $row) {
                    $this->_send_comment($row['mid'], $comment, $aid, 'portal');
                }
            }
        }
    }

	protected function newblog($param) {
		global $_G;

		$title = $GLOBALS['newblog']['subject'];
		$pic_path = $this->_get_first_img_from_html($_POST['message']);
		if($pic_path && substr($pic_path, 0, 4) != 'http') {
			$pic_path = $_G['siteurl'].$pic_path;
		}
		
		$message = strip_tags($_POST['message']);
		$message = str_replace('&nbsp;', ' ', $message);
		$message = preg_replace('/\s+/', ' ', $message);
		$message = preg_replace('/\[flash.*?\](.*?)\[\/flash\]/i', '\1', $message);
		
		$url = $this->_get_url('home_blog', $GLOBALS['newblog']['blogid']);
		
		$message = $this->_merge_message($title, $message, $url, 'blog');
		
		if(empty($pic_path)) {
			$this->_send_weibo($message, $GLOBALS['newblog']['blogid'], 'blog');
		} else {
			$this->_send_weibo_img($message, $pic_path, $GLOBALS['newblog']['blogid'], 'blog');
		}
	}
	
	protected function newshare($sid) {
		global $_G;
		if(!$GLOBALS['arr']) {
			return ;
		}
		$share = $GLOBALS['arr'];
		$share['body_data'] = unserialize($share['body_data']);
		
		switch (strtolower($share['type'])){
			case 'space':	$type = 'username';		break;
			case 'blog':	$type = 'subject';		break;
			case 'album':	$type = 'albumname';	break;
			case 'pic':		$type = 'albumname';	break;
			case 'thread':	$type = 'subject';		break;
			case 'article':	$type = 'title';		break;
			case 'link':
			case 'video':
			case 'music':
			case 'flash':	$type = 'link';	break;
			default:
				break;
		}
		if(empty($type)) {
			return ;
		} else if('link' != $type) {
			preg_match('/^<a.*?href="(.*?)".*?>(.*?)<\/a>$/i', $share['body_data'][$type], $match);
			if(3 !== count($match)) {
				return ;
			}
			$sharelink = $_G['siteurl'].$match[1];
			$title_extra = $match[2];
			if(isset($share['image']) && !empty($share['image'])){
				$pic_path = str_replace('.thumb.jpg', '', $share['image']);
				if(substr($pic_path, 0, 4) != 'http') {
					$pic_path = $_G['siteurl'].$pic_path;
				}
			}
		} else {
			$sharelink = $share['body_data']['data'];
		}
		
		$url = $_G['siteurl'].'home.php?mod=space&uid='.$_G['uid'].'&do=share&id='.$sid.($this->obj->adminsettings['syncpublish']['advocate'] ? '&uid='.$_G['uid'] : '');
		
		$titleformat = lang('plugin/'.sina_plugin_tools::get_plugin_name(), 'shareformat').$share['title_template'].(!empty($title_extra) ? ' '.$title_extra.' ' : '').' '.lang('core', 'title_share_link').':';
		$title = str_replace('{$bbname}', $_G['setting']['bbname'], $titleformat);
		$title .= ''.$sharelink.' ';
		
		$message = strip_tags($share['body_general']);
		$message = $this->_merge_message($title, $message, $url);
		
		if(empty($pic_path)) {
			$this->_send_weibo($message, $sid, 'share');
		} else {
			$this->_send_weibo_img($message, $pic_path, $sid, 'share');
		}
	}
	
	protected function newdoing($doid) {
		global $_G;

		$title = '';		
		$url = $_G['siteurl'].'home.php?mod=doing&uid='.$_G['uid'].($this->obj->adminsettings['syncpublish']['advocate'] ? '&uid='.$_G['uid'] : '');
		$message = trim($_POST['message']);
		$message = preg_replace('/\[em:\d:\]/', '', $message);
		if(empty($message)) {
			return ;
		}
		$message = $this->_merge_message($title, $message, $url, 'doing');
		
		$this->_send_weibo($message, $doid, 'doing');
	}
	
	protected  function newfollow($param) {
		global $_G;
		$title = '';
		$url = $_G['siteurl'].'home.php?mod=doing&uid='.$_G['uid'].($this->obj->adminsettings['syncpublish']['advocate'] ? '&uid='.$_G['uid'] : '');
		$message = trim($this->_filter($_POST['message']));
		if(empty($message)) {
			return '';
		}
		$message = $this->_merge_message($title, $message, $url, 'follow');
		
		$pic_path = $this->_get_picpath_by_id('pid', $param['param'][2]['pid']);
		
		if(empty($pic_path)) {
			$this->_send_weibo($message, $param['param'][2]['pid'], 'follow');
		} else {
			$this->_send_weibo_img($message, $pic_path, $param['param'][2]['pid'], 'follow');
		}
	}
	
	private function _merge_message($title, $message, $url, $type, $extra = array()) {
		global $_G;
		$urllength = 0;
		$format = $this->obj->adminsettings['pubtpl'][$type];
		if(!$format) {
			$format = lang('plugin/'.sina_plugin_tools::get_plugin_name(), $type.'format');
		}
		if($this->obj->adminsettings['syncpublish']['shorturl']) {
			$url = $this->_get_short_url($url);
		}
		return sina_plugin_tools::format_weibo($format, $title, $message, $url, $extra);
	}
	
	private function _get_url($type,$id) {
		global $_G;
		$rewrite = false;
		if(!$this->obj->adminsettings['syncpublish']['advocate'] && $_G['setting']['rewritestatus'] && in_array($type, $_G['setting']['rewritestatus'])) {
			$rewrite = true;
		} 
		return sina_plugin_tools::get_url_by_type($type, $id, $rewrite, $this->obj->adminsettings['syncpublish']['advocate']);
	}
	
	private function _send_weibo($status, $tid, $type) {
		$client = null;
		$status = $this->_dconvert($status);
		foreach($this->unexpireduser as $sinausers) {
			$client = sina_plugin_tools::get_client($this->obj->adminsettings['appkey'], $this->obj->adminsettings['appsecret'], $sinausers['access_token']);
			$response = $client->update($status);
			$this->_dresponse($response, $tid, $type, $sinausers['uid'], $sinausers['sina_uid']);
		}
	}
	
	private function _send_weibo_img($status, $pic_path, $tid, $type) {
		$client = null;
		$status = $this->_dconvert($status);
		foreach($this->unexpireduser as $sinausers) {
			$client = sina_plugin_tools::get_client($this->obj->adminsettings['appkey'], $this->obj->adminsettings['appsecret'], $sinausers['access_token']);
			if($this->obj->adminsettings['syncpublish']['upload_url_text']) { 
				$response = $client->upload_url_text($status, $pic_path);
			} else {
				$response = $client->upload($status, $pic_path);
			}
			$this->_dresponse($response, $tid, $type, $sinausers['uid'], $sinausers['sina_uid']);
		}
	}
	
	private function _send_comment($mid, $status, $tid, $type, $comment_ori = 0) {
		$client = null;
		$status = $this->_dconvert($status);
		foreach($this->unexpireduser as $sinausers) {
			$client = sina_plugin_tools::get_client($this->obj->adminsettings['appkey'], $this->obj->adminsettings['appsecret'], $sinausers['access_token']);
			$response = $client->send_comment($mid, $status, $comment_ori);
			$this->_dresponse($response, $tid, $type, $sinausers['uid'], $sinausers['sina_uid'], true);
			
		}
		
	}
	
	function _get_short_url($urllong) {
		return sina_plugin_tools::get_short_url($urllong, $this->obj->adminsettings['appkey'], $this->obj->adminsettings['appsecret']);
	}
	
	private function _dresponse($response, $tid, $type, $uid, $sina_uid, $iscomment = false) {
		if($response['error'] || !$response['id']) {
			sina_plugin_tools::log('publish error tid:'.$tid.' type:'.$type, $response);
		} else {
			$data = array();
			$data['sina_uid'] = $sina_uid;
			$data['mid'] = trim($response['idstr']);
			$data['tid'] = $tid;
			$data['type'] = $type;
			$data['iscomment'] = $iscomment ? 1 : 0;
			$data['synctime'] = strtotime($response['created_at']);
			$data['lastpushbacktime'] = $data['synctime'];
			sina_plugin_tools::get_bind_thread()->insert($data);
		}
	}
	
	private function _get_first_img_from_html($html) {
		$img_path = '';
		$pattern = '/<img.*?src=(\'|")(.*?)\\1.*?>/i';
		$match = array();
		preg_match($pattern, $html, $match);
		return $match[2];
	}
	
	private function _dconvert($needle) {
		if('gbk' == strtolower(CHARSET)) {
			return sina_plugin_tools::convert($needle, 'GBK', 'UTF-8');
		}
		return $needle;
	}
	
	private function _get_unexpired_user() {
		$result = false;
		foreach($this->obj->weibouserarr as $sinauser) {
			if($sinauser['oauth_time'] + $sinauser['expires_in'] > TIMESTAMP) {
				$this->unexpireduser[$sinauser['sina_uid']] = $sinauser;
				$result = true;
			}
		}
		return $result;
	}
	
	private function _get_picpath_from_post($post, $getaids = false) {
		if($getaids) {
			$matches = array();
			$pattern = '/\[attachimg\](.*?)\[\/attachimg\]/i';
			preg_match_all($pattern, $post, $matches);
			if($matches[1]) {
				return $this->_get_picpath_by_aids($matches[1]);
			} else {
				return '';
			}
		} else {
			$matches = array();
			$pattern = '/\[img.*?\](.*?)\[\/img\]/i';
			preg_match_all($pattern, $post, $matches);
			if($matches[1]) {
				return $matches[1][0];
			} else {
				return $this->_get_picpath_from_post($post, true);
			}
		}
	}
	
    private function _get_picpath_by_id($idtype, $id) {
		global $_G;
        $picpath = '';
        $attachdetail = C::t('forum_attachment_n')->fetch_all_by_id($idtype.':'.$id, $idtype, $id, '', false, false, false, 1);
        if($attachdetail) {
        	foreach($attachdetail as $val) {
        		$attachdetail = $val;
        		break;
        	}
            if($attachdetail['remote']) {
            	$pic_path = $_G['setting']['ftp']['attachurl'].'forum/'.$attachdetail['attachment'];
            } else {
          	 	$pic_path = $_G['setting']['attachurl'].'forum/'.$attachdetail['attachment'];
           		if(substr($pic_path, 0, 4) != 'http') {
           			$pic_path = $_G['siteurl'].$pic_path;
          		}
            }
        }
		return $pic_path;
    }

	private function _get_picpath_by_aids(array $aids) {
		global $_G;
		
		$pic_path = '';
		$attachments = C::t('forum_attachment')->fetch_all_by_id('aid', $aids);
		foreach($attachments as $attach) {
			$attachdetail = C::t('forum_attachment_n')->fetch_by_aid_uid(intval($attach['tableid']), $attach['aid'], $_G['uid']);
			if($attachdetail && $attachdetail['price'] <=0 ) {
				if($attachdetail['remote']) {
					$pic_path = $_G['setting']['ftp']['attachurl'].'forum/'.$attachdetail['attachment'];
				} else {
					$pic_path = $_G['setting']['attachurl'].'forum/'.$attachdetail['attachment'];
					if(substr($pic_path, 0, 4) != 'http') {
						$pic_path = $_G['siteurl'].$pic_path;
					}
				}
				break;
			}
		}
		return $pic_path;
	}
	
	
	function _filter($content) {
		global $_G;
	
		$content = preg_replace('!\[(attachimg|attach)\]([^\[]+)\[/(attachimg|attach)\]!', '', $content);
		$content = preg_replace('|\[img.*?\](.*?)\[/img\]|', '', $content);
	
		$re ="#\[([a-z]+)(?:=[^\]]*)?\](.*?)\[/\\1\]#sim";
		while(preg_match($re, $content)) {
			$content = preg_replace($re, '\2', $content);
		}
	
		$re = isset($_G['cache']['smileycodes']) ? (array)$_G['cache']['smileycodes'] : array();
		$smiles_searcharray = isset($_G['cache']['smilies']['searcharray']) ? (array)$_G['cache']['smilies']['searcharray'] : array();
		$content = str_replace($re, '', $content);
		$content = preg_replace($smiles_searcharray, '', $content);
		$content = preg_replace('/\s+/', ' ', $content);
		return $content;
	}
}

?>
