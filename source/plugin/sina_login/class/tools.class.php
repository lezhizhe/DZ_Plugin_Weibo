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
class sina_plugin_tools {
	
	public static function get_dz_version() {
		static $version = null;
		if(null == $version) {
			global $_G;
			$version = $_G['setting']['version'];
		}
		return $version;
	}
	
	public static function get_plugin_name() {
		return 'sina_login';
	}
	
	public static function get_rewrite_url($operation) {
		$data = &self::get_plugin_admin_settings();
		if($data['rewriterule'] && false !== strpos($data['rewriterule'], '{0}')) {
			return str_replace('{0}', $operation, $data['rewriterule']);
		}
		return 'plugin.php?id='.self::get_plugin_name().':index&operation='.$operation;
	}
	
	public static function curl_support() {
		return function_exists('curl_init');
	}
	
	public static function get_oauth_state() {
		$key = random(6);
		dsetcookie($key, TIMESTAMP, TIMESTAMP + 120);
		return $key.'___'.urlencode(dreferer());
	}
	
	public static function get_oauth2($key, $secret, $access_token, $refresh_token = NULL) {
		require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/sinaapi.class.php';
		return new sina_login_oauth($key, $secret, $access_token, $refresh_token);
	}
	
	public static function get_client($key, $secret, $access_token, $refresh_token = NULL) {
		$oauth2 = self::get_oauth2($key, $secret, $access_token, $refresh_token);
		return new sina_client($oauth2);
	}
	
	public static function get_publish_to_weibo() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/publish_to_weibo.class.php';
			$obj = new publish_to_weibo();
		}
		return $obj;
	}
	
	public static function get_pushback_to_bbs() {
		static $obj = null;	
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/pushback_to_bbs.class.php';
			$obj = new pushback_to_bbs();
		}
		return $obj;
	}
	
	public static function get_bind_user() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_user.class.php';
			$obj = new sina_bind_user();
		}
		return $obj;
	}
	
	public static function get_bind_sina() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_sina.class.php';
			$obj = new sina_bind_sina();
		}
		return $obj;
	}
	
	public static function get_bind_thread() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_thread.class.php';
			$obj = new sina_bind_thread();
		}
		return $obj;
	}
	
	public static function get_bind_pushback() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_pushback.class.php';
			$obj = new sina_bind_pushback();
		}
		return $obj;
	}
	
	public static function get_bind_pushback_repost() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_pushback_repost.class.php';
			$obj = new sina_bind_pushback_repost();
		}
		return $obj;
	}
	
	public static function get_bind_config() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_config.class.php';
			$obj = new sina_bind_config();
		}
		return $obj;
	}

	public static function get_bind_log() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/bind_log.class.php';
			$obj = new sina_bind_log();
		}
		return $obj;
	}

	public static function get_parseimg() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/parseimg.class.php';
			$obj = new sina_bind_parseimg();
		}
		return $obj;
	}
	
	public static function get_newthread() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/newthread.class.php';
			$obj = new sina_bind_newthread();
		}
		return $obj;
	}
	
	public static function get_share() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/share.class.php';
			$obj = new sina_bind_share();
		}
		return $obj;
	}
	
	public static function get_api() {
		static $obj = null;
		if($obj === null) {
			require_once DISCUZ_ROOT.'source/plugin/'.self::get_plugin_name().'/class/api.class.php';
			$obj = new sina_bind_api();
		}
		return $obj;
	}
	
	public static function set_plugin_admin_settings($data) {
		$data = array('data' => serialize($data), 'dateline' => TIMESTAMP);
		sina_plugin_tools::get_bind_config()->update('config', $data);
	}
	
	public static function &get_plugin_admin_settings($force = false) {
		static $data = array();
		if(!$data) {
			$rs = sina_plugin_tools::get_bind_config()->fetch_one_by_key('config');
			$data = unserialize($rs['data']);
		}
		return $data;
	}
	
	public static function get_pushback_filter() {
		$rs = sina_plugin_tools::get_bind_config()->fetch_one_by_key('pushback_filter');
		return unserialize($rs['data']);
	}

	public static function set_pushback_filter($data) {
		$data = array('skey' => 'pushback_filter', 'data' => serialize($data), 'dateline' => TIMESTAMP);
		sina_plugin_tools::get_bind_config()->insert($data, false, true);
	}

	public static function get_plugin_user_settings($uid) {
		$data = array();
		foreach(self::get_bind_user()->get_sina_users_by_uid($uid) as $sinauser) {
			if($sinauser['status'] == 1) {
				$data = unserialize($sinauser['settings']);
				break;
			}
		}
		return $data;
	}

	public static function get_url_by_type($type, $id, $rewrite, $advocate) {
		global $_G;
		switch($type) {
			case 'forum_viewthread':	//$id => tid
					$url = $rewrite ? rewriteoutput($type, 1, $_G['siteurl'], $id) : $_G['siteurl'].'forum.php?mod=viewthread&tid='.$id;
				break;
			case 'portal_article':	//$id => aid
					$url = $rewrite ? rewriteoutput($type, 1, $_G['siteurl'], $id) : $_G['siteurl'].'portal.php?mod=view&aid='.$id;
				break;
			case 'home_blog':	//$id => blogid
					$url = $rewrite ? rewriteoutput($type, 1, $_G['siteurl'], $_G['uid'], $id) : $_G['siteurl'].'home.php?mod=space&uid='.$_G['uid'].'&do=blog&id='.$id;
				break;
			case 'home_space':
				$url = $rewrite ? rewriteoutput($type, 1, $_G['siteurl'], $_G['uid'], $_G['username']) : $_G['siteurl'].'home.php?mod=space&uid='.$_G['uid'].'&do=doing';
				break;
			default:
				return '';
		}
		
		if($advocate && $_G['uid'] > 0 && !$rewrite) {
			$url .= '&fromuid='.$_G['uid'];
		}
		return $url;	
	}

	public static function get_short_url($urllong, $appkey, $secret) {
		$client = self::get_client($appkey, $secret, null);
		$response = $client->get_short_url($urllong);
		if(!$response['error']) {
			 if($response['urls'][0]['result']) {
				return $response['urls'][0]['url_short'];
			 }
		}
		return $urllong;
	}
	
	public static function format_weibo($format, $title, $message, $url, $extra = array()) {
		global $_G;
        if($extra['catname']) {
            $format = str_replace('{$catname}', $extra['catname'], $format);
        }
		$format = str_replace('{$title}', $title, $format);
		$format = str_replace('{$bbname}', $_G['setting']['bbname'], $format);
		$format = str_replace('{$url}', $url, $format);
		if(false !== strpos($format, '{$content}')) {
			$urllength = sina_plugin_tools::strlen(urlencode($url), CHARSET);
			$length = 120 + $urllength - sina_plugin_tools::strlen($format, CHARSET);
			$content = sina_plugin_tools::substr($message, 0, $length, CHARSET);
			$format = str_replace('{$content}', $content, $format);
		}
		return $format;
	}

	public static function strlen($str, $charset) {
		if(strtolower($charset) == 'gbk') {
			return mb_strlen($str, $charset);
		} else {
			$result = 0;
			$len = mb_strlen($str, $charset);
			for($i = 0; $i < $len; $i++) {
				$char = mb_substr($str, $i, 1);
				$ascii = ord($char);
				if($ascii < 128 && $ascii > 0) {
					$result += 0.5;
				} else {
					$result += 1;
				}
			}
			return $result;
		}
	}
	
	public static function substr($str, $start, $length, $charset) {
		if(strtolower($charset) == 'gbk') {
			return mb_substr($str, $start, $length, $charset);
		} else {
			$result = '';
			$len = mb_strlen($str, $charset);
			for($i = $start, $j = 0; $i < $len && round($j) < $length; $i++) {
				$char = mb_substr($str, $i, 1, $charset);
				$result .= $char;
				$ascii = ord($char);
				if($ascii < 128 && $ascii > 0) {
					$j += 0.5;
				} else {
					$j += 1;
				}
			}
			return $result;
		}
	}
	
	public static function convert($needle, $fromcharset, $tocharset) {
		if(is_array($needle)) {
			foreach($needle as $key => $string) {
				$needle[$key] = self::convert($string, $fromcharset, $tocharset);
			}
		} else {
			if(function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($needle, $tocharset, $fromcharset);
			} else {
				return iconv($fromcharset, $tocharset.'//IGNORE', $needle);
			}
		}
		return $needle;
	}

	public static function log($error, $data, $type = 'file') {
		if($type == 'file') {
			$filename = DISCUZ_ROOT.'/data/log/plugin_'.self::get_plugin_name().'_'.date('Y-m-d', TIMESTAMP).'.php';
			file_put_contents($filename, self::logformate($error, $data), FILE_APPEND);
		} else if($type == 'mysql') {
			self::get_bind_log()->insert(array(
				'error' => $error,
				'message' => self::logformate($error, $data),
				'dateline'=> date('Y-m-d H:i:s', TIMESTAMP)
			));
		}
	}

	protected static function logformate($error, $data) {
		$result = '<?php exit;?>';
		$result .= "[".date('Y-m-d H:i:s', TIMESTAMP)."]\t[$error]\t";
		if(is_array($data)) {
			$result .= var_export($data, true);
		} else {
			$result .= $data;
		}
		return $result."\n";
	}
}
