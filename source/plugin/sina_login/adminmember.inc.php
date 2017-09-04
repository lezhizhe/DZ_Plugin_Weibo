<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
loadcache(plugin);
$base_url = ADMINSCRIPT.'?action=plugins&operation=config&do='.$pluginid.'&identifier=sina_login&pmod=adminmember';
require_once dirname(__FILE__).'/class/tools.class.php';
$sinaop = trim($_GET['sinaop']);
if($sinaop == 'cancel') {
	$sina_uid = trim($_GET['sina_uid']);
	sina_plugin_tools::get_bind_user()->update(array('status' => 0), $sina_uid);
	cpmsg('&#35299;&#38500;&#32465;&#23450;&#25104;&#21151;', $_SERVER['HTTP_REFERER'], 'succeed');exit;
}
$where = " where 1";
$extra = '';
$sina_uid = isset($_GET['sina_uid']) ? intval($_GET['sina_uid']) : '';
if($sina_uid > 0) {
	$where .= " and sina_uid='{$sina_uid}'";
	$extra .= '&sina_uid='.$sina_uid;
} else {
	$sina_uid = '';
}
$username = trim($_GET['username']);
if($username) {
	$uid = C::t('common_member')->fetch_uid_by_username($username, 1);
	if($uid >= 0) {
		$where .= ' AND uid='.$uid;
	} else {
		$where .= " and uid='0'";
	}
	$extra .= '&username='.$username;
}
$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
if($status >= 0) {
	$where .= " and status='{$status}'";
	$extra .= '&status='.$status;
}
echo '<form name="cpform" method="post" autocomplete="off" action="'.$base_url.'" id="cpform">
<table class="tb tb2 ">
	<tbody>
		<tr><th colspan="7" class="partition">&#25628;&#32034;</th></tr>
		<tr class="hover">
			<td width="50">&#24494;&#21338;ID</td>
			<td width="150"><input size="15" name="sina_uid" type="text" value="'.$sina_uid.'"></td>
			<td width="80">&#35770;&#22363;&#29992;&#25143;</td>
			<td width="130"><input size="15" name="username" type="text" value="'.$username.'"></td>
			<td width="120">
				<select name="status">
					<option value="-1">&#32465;&#23450;&#29366;&#24577;</option>
					<option value="1"'.($status == 1 ? 'selected="selected"' : '').'>&#24050;&#32465;&#23450;</option>
					<option value="0"'.($status == 0 ? 'selected="selected"' : '').'>&#26410;&#32465;&#23450;</option>
				</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td width="40"><input class="btn" type="submit" value="&#25628;&#32034;"></td>
			<td></td>
		</tr>
	</tbody>
</table>
</form>';
$num = DB::result_first("SELECT COUNT(*) FROM ".DB::table('plugin_sina_sync_bind_user').$where);
$page = intval($_G['page']);
$limit = 20;
$max = 1000;
$page = ($page-1 > $num/$limit || $page > $max) ? 1 : $page;
$start_limit = ($page - 1) * $limit;
$multipage = multi($num, $limit, $page, $base_url.$extra, $max);

$query = DB::query("SELECT * FROM ".DB::table('plugin_sina_sync_bind_user').$where." ORDER BY sina_uid limit $start_limit,$limit");
$members = $sinausers = array();
$systime = time();
while($sina = DB::fetch($query)) {
	if($sina['status'] == 1 && $sina['uid']) {
		if(!isset($member[$sina['uid']])) {
			$members[$sina['uid']] = getuserbyuid($sina['uid']);
		}
	}
	$sina['profile'] = unserialize($sina['profile']);
	$expiretime = $sina['oauth_time'] + $sina['expires_in'];
	$sina['expire_info'] = date('Y-m-d H:i:s', $expiretime);
	if($systime > $expiretime) {
		$sina['expire_info'] = '<font color="red">'.$sina['expire_info'].'</font>';
	}
	$sinausers[] = $sina;
}
showtableheader();
if(empty($sinausers)){
		echo '<td colspan="6" align="center" style="color:red;">没有数据</td>';
} else {
	showsubtitle(array('&#24494;&#21338;ID', '&#24494;&#21338;&#26165;&#31216;', '&#32465;&#23450;&#35770;&#22363;&#36134;&#21495;', '&#25480;&#26435;&#36807;&#26399;&#26102;&#38388;', '&#25805;&#20316;'));
	foreach($sinausers as $sina){
			echo '<tbody><tr>
			<td>'.$sina['sina_uid'].'</td>
			<td><a href="http://weibo.com/'.$sina['sina_uid'].'" target="_blank">'.$sina['profile']['screen_name'].'</a></td>
			<td>'.($sina['status'] == 1 && $sina['uid'] ? '<a href="home.php?mod=space&uid='.$sina['uid'].'" target="_blank">'.$members[$sina['uid']]['username'].'</a>' : '<font color="red">&#26410;&#32465;&#23450;</font>').'</td>
			<td>'.$sina['expire_info'].'</td>
			<td>
				<a href="'.$base_url.'&formhash='.$_G['formhash'].'&sinaop=cancel&sina_uid='.$sina['sina_uid'].'">&#35299;&#38500;&#32465;&#23450;</a>
			</td>
			</tr>
			</tbody>';
	}
}
echo '<tr><td align="left"></td><td colspan="4" align="right">'.$multipage.'</td></tr>';
showtablefooter();	
?>