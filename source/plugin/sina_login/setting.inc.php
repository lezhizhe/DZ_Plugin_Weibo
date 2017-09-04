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
require_once dirname(__FILE__).'/class/control.class.php';

$sina_conrol = new sina_control();

$sina_conrol->_call('dosetting');