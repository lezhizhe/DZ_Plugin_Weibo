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

class sina_bind_pushback {
	
	private $table_obj = null; 
	private $plugin_name = '';
	
	function sina_bind_pushback() {
	
		$this->table_obj = C::t('#'.sina_plugin_tools::get_plugin_name().'#sina_bind_pushback');
	}
	
	function get_row_by_id($id, $type) {
		
		return $this->table_obj->get_row_by_id($id, $type);
	}

	function get_rows_by_ids($ids, $type) {

		return $this->table_obj->get_rows_by_ids($ids, $type);
	}
}
?>
