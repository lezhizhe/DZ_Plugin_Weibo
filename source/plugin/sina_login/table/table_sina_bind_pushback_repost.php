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

class table_sina_bind_pushback_repost extends discuz_table {

	public function __construct() {

		$this->_table = 'plugin_sina_sync_bind_pushback_repost';
		$this->_pk    = '';
		parent::__construct();
	}
	
	public function get_rows_by_ids($ids, $type) {
		
		return DB::fetch_all('SELECT * FROM %t WHERE id IN(%n) AND type=%s', array($this->_table, $ids, $type));
	}

	public function get_row_by_id($id, $type) {
	
		return DB::fetch_first('SELECT * FROM %t WHERE id=%d AND type=%s', array($this->_table, $id, $type));
	}
}
