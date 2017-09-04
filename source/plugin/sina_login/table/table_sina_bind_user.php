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

class table_sina_bind_user extends discuz_table
{
	public function __construct() {

		$this->_table = 'plugin_sina_sync_bind_user';
		$this->_pk    = 'sina_uid';
		parent::__construct();
	}
	
	public function delete_by_uid($uid) {
		
		DB::query('DELETE FROM %t WHERE uid=%d', array($this->_table, $uid));
	}
	
	public function delete_by_sina_uid($sina_uid) {
		
		DB::query('DELETE FROM %t WHERE sina_uid=%s', array($this->_table, $sina_uid));
	}
	
	public function update($data, $sina_uid) {
		
		return DB::update($this->_table, $data, array('sina_uid' => $sina_uid));
	}
	
	public function get_user_by_sina_uid($sina_uid) {
		
		return DB::fetch_first('SELECT * FROM %t WHERE sina_uid=%s', array($this->_table, $sina_uid));
	}
	
	public function get_sina_users_by_uid($uid) {

		return DB::fetch_all('SELECT * FROM %t WHERE uid=%d', array($this->_table, $uid));
	}
}
