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
$plugin_sina_config	=	array (
  'appkey' => '',
  'appsecret' => '',
  'rewriterule' => '',
  'showoption' => 
  array (
    0 => 'fastlogin',
  ),
  'expireoption' => 
  array (
    'ftime' => 36,
    'creditid' => 0,
    'credit' => 0,
  ),
  'quick_register' => 0,
  'use_signature' => 1,
  'medalid' => '',
  'sysncpushback' => 
  array (
    'option' => 
    array (
      0 => 'threads',
      1 => 'portal',
      2 => 'blog',
    ),
    'ltime' => '3',
	'pushbackrepost' => 0,
	'pushbackshare' => 0,
  	'pushbackshare_uids' => 
  	array(
  	),
  ),
  'weibofollow' => 
  array (
    'followopen' => 0,
    'officialuid' => '1848091743',
  	'focus_position' => 'status_extra',
  	'focus_width' => 200,
  	'focus_qqwidth' => 0,
  	'focus_color' => 1,
  	'focus_style' => 2,
    'recomenduids' => 
    array (
      0 => '1848091743',
      1 => '1686868603',
      2 => '1831711412',
      3 => '1051409000',
      4 => '1758909427',
      5 => '2814004662',
      6 => '2133996230',
    ),
  ),
  'syncpublish' => 
  array (
    'option' => 
    array (
      0 => 'threads',
      1 => 'portal',
      2 => 'blog',
      3 => 'share',
      4 => 'doing',
      5 => 'follow',
    ),
  	'sync_checked' => '1',
    'forwarding' => '1',
    'advocate' => '1',
	'replynopublish' => '1',
	'shorturl' => '0',
	'upload_url_text' => '0',
  ),
  'shareoption' => array(
	'open' => 0,
	'sharepic' => '',
	'sharecontnet' => '',
	'shareforce' => 0,
  ),
  'commentuid' => 0,
  'commentusername' => '',
);
