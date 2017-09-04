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

$query = $_SERVER["QUERY_STRING"];
if($operation == 'import') {
	require_once dirname(__FILE__).'/class/tools.class.php';
	if(!sina_plugin_tools::curl_support()) {
		cpmsg($installlang['not_surport_curl'], dreferer(), 'error');
	}
	if(!function_exists('mb_substr')) {
		cpmsg($installlang['not_surport_mb'], dreferer(), 'error');
	}
} else if($operation =='delete') {
	
	$savedata = daddslashes($_GET['savedata']);
	$cancelurl =  ADMINSCRIPT.'?'.$query.'&savedata=no';
	if(empty($savedata)) {
		$message = "<form method=\"post\" action=\"".ADMINSCRIPT."?$query&savedata=yes\"><input type=\"hidden\" name=\"formhash\" value=\"".FORMHASH."\">".
			"<br />{$installlang['is_save_data']}<br />".
			"<p class=\"margintop\"><input type=\"submit\" class=\"btn\" name=\"issavedata\" value=\"".cplang('yes')."\"> &nbsp; \n<input type=\"button\" class=\"btn\" value=\"".cplang('no')."\" onClick=\"location.href='$cancelurl'\">".
			"</p></form><br />";
		echo '<h3>'.cplang('discuz_message').'</h3><div class="infobox">'.$message.'</div>';
		exit();
		
	} else {
		dsetcookie('sina_login_savedata', $savedata);
	}
} else if($operation =='upgrade') {

}