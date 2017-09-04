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

class sina_bind_log {
	
	private $table_obj = null;
	
	function sina_bind_log() {
		
		$this->table_obj = C::t('#'.sina_plugin_tools::get_plugin_name().'#sina_bind_log');
	}

	function insert($data, $return_insert_id = false, $replace = false, $silent = false) {

		$this->table_obj->insert($data, $return_insert_id, $replace, $silent);
	}
}
?>
