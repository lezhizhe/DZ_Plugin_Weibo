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
$args = array();
$operation = strtolower(trim($_GET['operation']));
$allow_operation = array('init', 'finit', 'callback', 'cancel', 'sync', 'focus', 'share', 'sharebinding', 'api');

if(!in_array($operation, $allow_operation)) {
	if('cancel' == substr($operation, 0, 6)) {
		$args['sina_uid'] = substr($operation, 6);
		$operation = 'cancel';
	} else {
		$operation = 'setting';
	}
}

require_once dirname(__FILE__).'/class/control.class.php';
$sina_conrol = new sina_control();
$sina_conrol->_call('do'.$operation, $args); 