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
error_reporting(0);
define('IN_SINA_API', true);
define('SINA_OPEN_VERSION', '2.0');
class sina_bind_api {
	
	private $config = array();
    private $key = '';
	
    function __construct() {
        if(!defined('SINA_OPEN_KEY')) {
           include_once dirname(__FILE__).'/api.config.php'; 
        } 
    }

	function init_config($config) {
		$this->config = $config;
		return $this;
	}
	
	function execute($apitype) {
		switch ($apitype) {
            case 'valid':
                $this->valid();
                break;
            case 'version':
                $this->version();
                break;
			case 'fetchconfig':
				$this->get_config();
				break;
			case 'setconfig':
				$this->set_config();
				break;
			case 'fetchforum':
				$this->forums();
				break;
            case 'thread':
                $this->push_thread();
                break;
			case 'threadclass':
                $this->threadclass();
                break;
			case 'repeats':
				$this->get_repeats();
				break;
			default:
				die('Access Dined');
		}
	}
	
	/**
	 * 返回版块列表
	 */
	function forums() {
        if($this->checksignature()) {
            global $_G;
            if(!isset($_G['cache']['forums'])) {
                loadcache('forums');
            }
            $forumcache = $this->_encode($_G['cache']['forums']);
            print_r($forumcache);
        } else {
            $this->checksignaturefailed();
        }
    }
	
	/**
	 * 返回版块分类列表
	 */
	function threadclass() {
		if($this->checksignature()) {
            $result = array();
			$classlist = C::t('forum_threadclass')->range();
			if($classlist) {
				$fids = array();
				foreach($classlist as $val) {
					$fids[$val['fid']] = $val['fid'];
				}
				foreach($classlist as $val) {
					$result[$val['fid']][$val['typeid']] = array('typeid' => $val['typeid'], 'name' => $val['name'], 'displayorder' => $val['displayorder']);
				}
			}
            print_r($this->_encode($result));
        } else {
            $this->checksignaturefailed();
        }
	}
	
	/**
	 *返回当前客户端版本
	 */
    function version() {
    	if($this->checksignature()) {
    		$version = $this->_encode(array('version' => SINA_OPEN_VERSION));
    		print_r($version);
    	} else {
    		$this->checksignaturefailed();
    	}
    }
    
    /**
     * 返回配置信息
     */
    function get_config() {
        if($this->checksignature()) {
            $config = sina_plugin_tools::get_plugin_admin_settings();
            $config = $this->_encode($config);
            print_r($config);
        } else {
            $this->checksignaturefailed();
        }
    }

    /**
     * 设置配置信息
     */
    function set_config() {
        if($this->checksignature()) {
            $config = trim($_POST['config']);
            $config = $this->_decode($config);
            if(is_array($config)) {
                sina_plugin_tools::set_plugin_admin_settings($config);
                $result = array('errcode' => 0, 'msg' => 'ok');
            } else {
                $result = array('errcode' => 701, 'msg' => 'config data is not array : '.var_export($config, true));
            }
            $result = $this->_encode($result);
            print_r($result);
        } else {
            $this->checksignaturefailed();
        }
    }

	/**
	 *获取马甲用户 array('sina_uid' => array(username1, username2, ...), ...)
	 */
	function get_repeats() {
		if($this->checksignature()) {
			global $_G;
			$result = array();
			$sina_uids = trim($_POST['sina_uids']);
			if('gbk' == strtolower(CHARSET)) {
				$sina_uids = sina_plugin_tools::convert($sina_uids, 'UTF-8', 'GBK');
			}
			if($sina_uids && $_G['cache']['plugin']['myrepeats']['usergroups']) {
				$sina_uids = explode(',', $sina_uids);
				$sina_uids = array_unique($sina_uids);
				$uids = array();
				foreach($sina_uids as $sina_uid) {
					if(is_numeric($sina_uid)) {
						$sina_user = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($sina_uid);
						if(!$sina_user || $sina_user['status'] == 0) {
							continue;
						}
						$uids[$sina_user['sina_uid']] = $sina_user['uid'];
					}
				}
				if($uids) {
					$unique_uids = array_unique($uids);
					$users = array();
					foreach($unique_uids as $uid) {
						foreach(C::t('#myrepeats#myrepeats')->fetch_all_by_uid($uid) as $val) {
							$users[$uid][] = $val['username'];
						}
					}
					foreach($uids as $sina_uid => $uid) {
						$result[$sina_uid] = $users[$uid];
					}
				}
			}
			$result = $this->_encode($result);
            print_r($result);
		} else {
            $this->checksignaturefailed();
        }
	}
	
    function push_thread() {
        if($this->checksignature()) {
            global $_G;
            $result = array();
			$username = trim($_POST['username']);
            $sina_uid = trim($_POST['sina_uid']);
            $typeid = trim($_POST['typeid']);
            $fid = intval($_POST['fid']);
			$mid = trim($_POST['queryid']);
            if('gbk' == strtolower(CHARSET)) {
            	$sina_uid = sina_plugin_tools::convert($sina_uid, 'UTF-8', 'GBK');
            	$mid = sina_plugin_tools::convert($mid, 'UTF-8', 'GBK');
            	$username = sina_plugin_tools::convert($username, 'UTF-8', 'GBK');
            }
            $sina_user = sina_plugin_tools::get_bind_user()->get_user_by_sina_uid($sina_uid);
            if(!$sina_user || $sina_user['status'] == 0) {
                $result = array('errcode' => 402, 'msg' => 'sina user no bind');
            } else {
				if(empty($username)) {
					$repeats = false;
					$member =  getuserbyuid($sina_user['uid'], 1);
				} else {
					$repeats = true;
					$member = C::t('common_member')->fetch_by_username($username);
				}
                if($member) {
                    global $_G;
                    if(!isset($_G['cache']['forums'])) {
                        loadcache('forums');
                    }
                    $forumcache = &$_G['cache']['forums'];
                    if(isset($forumcache[$fid]) && $forumcache[$fid]['type'] != 'group') {
                        $subject = trim($_POST['title']);
                        $message = trim($_POST['message']);
                        if('gbk' == strtolower(CHARSET)) {
                        	$subject = sina_plugin_tools::convert($subject, 'UTF-8', 'GBK');
                        	$message = sina_plugin_tools::convert($message, 'UTF-8', 'GBK');
                        }
                        $message = "\t".$message;
                        $new_thread = sina_plugin_tools::get_newthread();
                        $new_thread->init($message);
                        $tid = $new_thread->new_thread($member['uid'], $member['username'], $fid, $typeid, $subject, $_G['clientip']);
                        if($tid > 0) {
                            $data = array();
                            $data['sina_uid'] = $sina_uid;
                            $data['mid'] = $mid;
                            $data['tid'] = $tid;
                            $data['type'] = 'thread';
                            $data['iscomment'] = 0;
                            $data['synctime'] = TIMESTAMP;
                            $data['lastpushbacktime'] = TIMESTAMP - 60;
                            sina_plugin_tools::get_bind_thread()->insert($data);                            
                            $result = array('errcode' => 0, 'msg' => $tid);
                        } else {
                            $result = array('errcode' => 411, 'msg' => 'push thread failed');
                        }
                    } else {
                        $result = array('errcode' => 404, 'msg' => 'bbs forum not found!');
                    }
                } else {
					if($repeats) {
						$result = array('errcode' => 407, 'msg' => 'bbs user not found!');
					} else {
						$result = array('errcode' => 405, 'msg' => 'bbs user not found!');
					}
                }
            }
            print_r($this->_encode($result));
        } else {
            $this->checksignaturefailed();
        }
    }
	
	/**
	 * 验证失败
	 */
    function checksignaturefailed() {
        $result = array('errcode' => 401, 'msg' => 'checksignature failed');
        print_r($this->_encode($result));
    }

    /**
     * 验证成功 errcode 为0
     */
    function valid() {
        if($this->checksignature()) {
            $echostr = trim($_GET['echostr']);
            $result = array('errcode' => 0, 'msg' => $echostr);
            print_r($this->_encode($result));
        } else {
            $this->checksignaturefailed();
        }
    }

    /*
     * 所有返回都经过这里, 需要执行编码转换
     */
    function _encode($data) {
   		if('gbk' == strtolower(CHARSET)) {
			$data = sina_plugin_tools::convert($data, 'GBK', 'UTF-8');
		}
        return serialize($data);
    }
	
    /**
     * 解码
     * @param unknown_type $data
     */
    function _decode($data) {
    	
    	$data = unserialize($data);
    	if('gbk' == strtolower(CHARSET)) {
    		$data = sina_plugin_tools::convert($data, 'UTF-8', 'GBK');
    	}
    	return $data;
    }

    /**
     * 签名验证
     * @return boolean
     */
    function checksignature() {
        $signature = trim($_GET["signature"]);
        $timestamp = trim($_GET["tstamp"]);
        $nonce = trim($_GET["nonce"]);
        $secret = SINA_OPEN_SECRET;
        $array = array($secret, $timestamp, $nonce);
        sort($array, SORT_STRING);
        $array = implode($array);
        $tmpstr = sha1($array);

        if($tmpstr == $signature){
           return true;
        } else {
           return false;
        }
    }

}
