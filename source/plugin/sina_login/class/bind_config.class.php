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

class sina_bind_config {
	
	private $table_obj = null;
	
	function sina_bind_config() {
		
		$this->table_obj = C::t('#'.sina_plugin_tools::get_plugin_name().'#sina_bind_config');
	}
	
	function insert($data, $return_insert_id = false, $replace = false) {
		
		return $this->table_obj->insert($data, $return_insert_id, $replace);
	}
	
	function update($skey, $data) {
		
		return $this->table_obj->update($skey, $data);
	}
	
	function delete($skey) {
	
		return $this->table_obj->delete($skey);
	}
	
	function fetch_one_by_key($skey) {
		
		return $this->table_obj->fetch($skey);
	}
}
?>