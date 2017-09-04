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
class sina_bind_newthread {
	
	protected $message = '';
	
	/**
	 *初始化 微博内容和图片
	 *@param string $message 微博内容, 用来做微博内容
	 */
	function init($message) {
		$this->message = $message;
	}
	
	/**
	 *同步评论过的新浪微博到论坛，标题为评论内容
	 *@param string $subject 即评论内容， 用来做帖子标题
	 *@param string $clientip 发帖IP, CGI模式下用默认IP
	 *@param int 返回主题ID
	 */
	function new_thread($uid, $username, $fid, $typeid, $subject, $clientip = '127.0.0.1') {

		$arg_list = func_get_args();
		file_put_contents('/workspace/webapps/bbs.lezhizhe.net/data/log/t.php', var_export($arg_list, true), FILE_APPEND);
		$newthread = array(
				'fid' => $fid,
				'posttableid' => 0,
				'readperm' => 0,
				'price' => 0,
				'typeid' => $typeid,
				'sortid' => 0,
				'author' => $username,
				'authorid' => $uid,
				'subject' => $subject,
				'dateline' => TIMESTAMP,
				'lastpost' => TIMESTAMP,
				'lastposter' => $username,
				'displayorder' => 0,
				'digest' => 0,
				'special' => 0,
				'attachment' => 0,
				'moderated' => 0,
				'status' => 512,
				'isgroup' => 0,
				'replycredit' => 0,
				'closed' => 0
		);

		$tid = C::t('forum_thread')->insert($newthread, true);
		useractionlog($uid, 'tid');
		
		require_once libfile('function/forum');
		$pid = insertpost(array(
				'fid' => $fid,
				'tid' => $tid,
				'first' => '1',
				'author' => $username,
				'authorid' => $uid,
				'subject' => $subject,
				'dateline' => TIMESTAMP,
				'message' => $this->message,
				'useip' => $clientip,
				'invisible' => 0,
				'anonymous' => 0,
				'usesig' => 1,
				'htmlon' => 0,
				'bbcodeoff' => 0,
				'smileyoff' => 1,
				'parseurloff' => 1,
				'attachment' => '0',
				'tags' => '',
				'replycredit' => 0,
				'status' => 0 
		));
		
		require_once libfile('function/post');
		updatepostcredits('+',  $uid, 'post', $fid);
		
		$subject = str_replace("\t", ' ', $subject);
		$lastpost = "$tid\t".$subject."\t".TIMESTAMP."\t$username";
		C::t('forum_forum')->update($fid, array('lastpost' => $lastpost));
		C::t('forum_forum')->update_forum_counter($fid, 1, 1, 1);
		$forum = C::t('forum_forum')->fetch_all_by_fid($fid);
		if($forum[$fid] && $forum[$fid]['type'] == 'sub') {
			C::t('forum_forum')->update($forum[$fid]['fup'], array('lastpost' => $lastpost));
		}
	
		if($forum[$fid] && $forum[$fid]['allowfeed']) {
			
			$feed['icon'] = 'thread';
			$feed['title_template'] = 'feed_thread_title';
			$feed['body_template'] = 'feed_thread_message';
			$feed['body_data'] = array(
					'subject' => "<a href=\"forum.php?mod=viewthread&tid=$tid\">$subject</a>",
					'message' => $this->message
			);

			$feed['title_data']['hash_data'] = "tid{$tid}";
			$feed['id'] = $tid;
			$feed['idtype'] = 'tid';
			if($feed['icon']) {
				require_once libfile('function/feed');
				feed_add($feed['icon'], $feed['title_template'], $feed['title_data'], $feed['body_template'], $feed['body_data'], '', $feed['images'], $feed['image_links'], '', '', '', 0, $feed['id'], $feed['idtype'], $uid, $username);
			}
		}
		return $tid;
	}
}
?>
