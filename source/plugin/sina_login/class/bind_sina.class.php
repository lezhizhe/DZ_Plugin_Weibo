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

class sina_bind_sina {
	
	private $table_obj = null;
	
	function sina_bind_sina() {
		
		$this->table_obj = C::t('#'.sina_plugin_tools::get_plugin_name().'#sina_bind_sina');
	}
	
	function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		
		$this->table_obj->insert($data, $return_insert_id, $replace, $silent);
	}

	function delete($sina_uid) {
		
		$this->table_obj->delete($sina_uid);
	}
	
	function update($sina_uid, $data) {
		
		$this->table_obj->update($sina_uid, $data);
	}
	
	function get_user_by_sina_uid($sina_uid) {
		
		return $this->table_obj->get_user_by_sina_uid($sina_uid);
	}
	
	function get_sina_users_by_sina_uids($sina_uids) {
		
		return $this->table_obj->get_sina_users_by_sina_uids($sina_uids, false, true);
	}
}

?>
