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

class table_sina_bind_sina extends discuz_table
{
	public function __construct() {

		$this->_table = 'plugin_sina_sync_bind_sina';
		$this->_pk    = 'sina_uid';
		parent::__construct();
	}
	
	public function get_user_by_sina_uid($sina_uid) {
		
		return $this->fetch($sina_id);
	}
	
	public function get_sina_users_by_sina_uids($sina_uids) {

		return $this->fetch_all($sina_uids);
	}
}