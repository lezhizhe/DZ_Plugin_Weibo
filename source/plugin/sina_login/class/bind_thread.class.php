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

class sina_bind_thread {
	
	private $table_obj = null;
	private $plugin_name = '';
	
	function sina_bind_thread() {
	
		$this->table_obj = C::t('#'.sina_plugin_tools::get_plugin_name().'#sina_bind_thread');
	}
	
	function insert($data, $return_insert_id = false, $replace = false, $silent = false) {

		$this->table_obj->insert($data, $return_insert_id, $replace, $silent);
	}
	
	function update($data, $tid, $type) {
		
		$this->table_obj->update($data, $tid, $type);
	}
	
	function delete($tid, $type) {
		
		$this->table_obj->delte($tid, $type);
	}
	
	function get_row_by_tid($tid, $type) {
		
		return $this->table_obj->get_row_by_tid($tid, $type);
	}

	function get_rows_by_tid($tid, $type) {
		
		return $this->table_obj->get_rows_by_tid($tid, $type);
	}
	
	function get_comment_by_mid($mid) {
	
		return $this->table_obj->get_comment_by_mid($mid);
	}

	function get_row_by_mid($mid) {
	
		return $this->table_obj->get_row_by_mid($mid);
	}

	function get_rows_by_tids($tids, $type) {
		
		return $this->table_obj->get_rows_by_tids($tids, $type);
	}
}
?>
