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

class table_sina_bind_config extends discuz_table
{
	public function __construct() {

		$this->_table = 'plugin_sina_sync_bind_config';
		$this->_pk    = 'skey';
		parent::__construct();
	}
}