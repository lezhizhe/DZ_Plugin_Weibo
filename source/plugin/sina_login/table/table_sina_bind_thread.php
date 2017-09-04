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

class table_sina_bind_thread extends discuz_table
{
	public function __construct() {

		$this->_table = 'plugin_sina_sync_bind_thread';
		$this->_pk    = '';
		parent::__construct();
	}
	
	public function update($data, $tid, $type) {
		
		$condition = array('tid' => $tid, 'type' => $type);
		return DB::update($this->_table, $data, $condition);
	}
	
	public function delete($tid, $type) {
		
		DB::query('DELETE FROM %t WHERE tid=%d AND type=%s', array($this->_table, $tid, $type));
	}
	
	public function get_comment_by_mid($mid) {
		
		return DB::fetch_first('SELECT * FROM %t WHERE mid=%s AND iscomment=1', array($this->_table, $mid));
	}
	
	public function get_row_by_mid($mid) {
		
		return DB::fetch_first('SELECT * FROM %t WHERE mid=%s', array($this->_table, $mid));
	}

	public function get_row_by_tid($tid, $type) {
		
		return DB::fetch_first('SELECT * FROM %t WHERE tid=%d AND type=%s AND iscomment=0', array($this->_table, $tid, $type));
	}
	
	public function get_rows_by_tid($tid, $type) {
		
		return DB::fetch_all('SELECT * FROM %t WHERE tid=%d AND type=%s AND iscomment=0', array($this->_table, $tid, $type));
	}

	public function get_rows_by_tids($tids, $type) {
		
		return DB::fetch_all('SELECT * FROM %t WHERE tid IN(%n) AND type=%s AND iscomment=0', array($this->_table, $tids, $type));
	}
}
