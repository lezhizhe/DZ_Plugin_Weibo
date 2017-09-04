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
class sina_bind_parseimg {
	
	var $special_gid = 7;
	
	public function init_gid($gid) {
		
		$this->special_gid = $gid;
	}
	
	public function connectParseBbcode($bbcode, $fId, $pId, $isHtml, &$attachImages) {
		include_once libfile('function/discuzcode');
		$images = $this->getStaticImg($bbcode);
		$result = preg_replace('/\[hide(=\d+)?\].+?\[\/hide\](\r\n|\s)/i', '', $bbcode);
		$result = preg_replace('/\[payto(=\d+)?\].+?\[\/payto\](\r\n|\s)/i', '', $result);
		$result = preg_replace('/\[quote\].*\[\/quote\](\r\n|\n|\r){0,}/is', '', $result);
		$result = discuzcode($result, 0, 0, $isHtml, 1, 2, 1, 0, 0, 0, 0, 1, 0);
		$result = strip_tags($result, '<img><a>');
		$result = preg_replace('/<img src="images\//i', "<img src=\"".$_G['siteurl']."images/", $result);
		$result = $this->connectParseAttach($result, $fId, $pId, $attachImages);
		return $result;
	}
	
	public function connectParseAttach($content, $fId, $pId, &$attachImages) {
		global $_G;
	
		$permissions = $this->connectGetUserGroupPermissions($this->special_gid, $fId);
		$visitorPermission = $permissions[$this->special_gid];
	
		$attachIds = array();
		$attachImages = array ();
		$attachments = C::t('forum_attachment')->fetch_all_by_id('pid', $pId);
		$attachments = C::t('forum_attachment_n')->fetch_all("pid:$pId", array_keys($attachments));
	
		foreach ($attachments as $k => $attach) {
			$aid = $attach['aid'];
			if($attach['isimage'] == 0 || $attach['price'] > 0 || $attach['readperm'] > $visitorPermission['readPermission'] || in_array($fId, $visitorPermission['forbidViewAttachForumIds']) || in_array($attach['aid'], $attachIds)) {
				continue;
			}
	
			$imageItem = array ();
			$thumbWidth = '100';
			$thumbHeight = '100';
			$bigWidth = '400';
			$bigHeight = '400';
			$key = dsign($aid.'|'.$thumbWidth.'|'.$thumbHeight);
			$thumbImageURL = $_G['siteurl'] . 'forum.php?mod=image&aid='.$aid.'&size='.$thumbWidth.'x'.$thumbHeight.'&key='.rawurlencode($key).'&type=fixwr&nocache=1';
			$imageItem['aid'] = $aid;
			$imageItem['thumb'] = $thumbImageURL;
			if($attach['remote']) {
				$imageItem['path'] = $_G['setting']['ftp']['attachurl'].'forum/'.$attach['attachment'];
				$imageItem['remote'] = true;
			} else {
				$imageItem['path'] = $_G['setting']['attachurl'].'forum/'.$attach['attachment'];
				if(strpos($imageItem['path'], $_G['siteurl']) === false) {
					$imageItem['path'] = $_G['siteurl'].$imageItem['path'];
				}
			}
	
			$attachIds[] = $aid;
			$attachImages[] = $imageItem;
		}
		$content = preg_replace('/\[attach\](\d+)\[\/attach\]/ie', '$this->connectParseAttachTag(\\1, $attachNames)', $content);
	
		return $content;
	}
	
	public function connectParseAttachTag($attachId, $attachNames) {
		include_once libfile('function/discuzcode');
		if(array_key_exists($attachId, $attachNames)) {
			return '<span class="attach"><a href="'.$_G['siteurl'].'/attachment.php?aid='.aidencode($attachId).'">'.$attachNames[$attachId].'</a></span>';
		}
		return '';
	}
	
	function connectGetUserGroupPermissions($gid, $fid) {
		global $_G;
	
		loadcache('usergroups');
		$fields = array (
				'groupid' => 'userGroupId',
				'grouptitle' => 'userGroupName',
				'readaccess' => 'readPermission',
				'allowvisit' => 'allowVisit'
		);
		$userGroup = C::t('common_usergroup')->fetch_all($gid);
		$userGroupInfo = array();
		foreach ($userGroup as $id => $value) {
			$userGroupInfo[$id] = array_merge($value, $_G['cache']['usergroups'][$id]);
			$userGroupInfo[$id]['forbidForumIds'] = array ();
			$userGroupInfo[$id]['allowForumIds'] = array ();
			$userGroupInfo[$id]['specifyAllowForumIds'] = array ();
			$userGroupInfo[$id]['allowViewAttachForumIds'] = array ();
			$userGroupInfo[$id]['forbidViewAttachForumIds'] = array ();
			foreach ($fields as $k => $v) {
				$userGroupInfo[$id][$v] = $userGroupInfo[$id][$k];
			}
		}
		$forumField = C::t('forum_forumfield')->fetch($fid);
		$allowViewGroupIds = array ();
		if($forumField['viewperm']) {
			$allowViewGroupIds = explode("\t", $forumField['viewperm']);
		}
		$allowViewAttachGroupIds = array ();
		if($forumField['getattachperm']) {
			$allowViewAttachGroupIds = explode("\t", $forumField['getattachperm']);
		}
	
		foreach ($userGroupInfo as $groupId => $value) {
			if($forumField['password']) {
				$userGroupInfo[$groupId]['forbidForumIds'][] = $fid;
				continue;
			}
			$perm = unserialize($forumField['formulaperm']);
			if(is_array($perm)) {
				if($perm[0] || $perm[1] || $perm['users']) {
					$userGroupInfo[$groupId]['forbidForumIds'][] = $fid;
					continue;
				}
			}
	
			if(!$allowViewGroupIds) {
				$userGroupInfo[$groupId]['allowForumIds'][] = $fid;
			} elseif (!in_array($groupId, $allowViewGroupIds)) {
				$userGroupInfo[$groupId]['forbidForumIds'][] = $fid;
			} elseif (in_array($groupId, $allowViewGroupIds)) {
				$userGroupInfo[$groupId]['allowForumIds'][] = $fid;
				$userGroupInfo[$groupId]['specifyAllowForumIds'][] = $fid;
			}
	
			if(!$allowViewAttachGroupIds) {
				$userGroupInfo[$groupId]['allowViewAttachForumIds'][] = $fid;
			} elseif (!in_array($groupId, $allowViewAttachGroupIds)) {
				$userGroupInfo[$groupId]['forbidViewAttachForumIds'][] = $fid;
			} elseif (in_array($groupId, $allowViewGroupIds)) {
				$userGroupInfo[$groupId]['allowViewAttachForumIds'][] = $fid;
			}
		}
	
		return $userGroupInfo;
	}
	
	function getStaticImg($message) {
		$matches = $result = array();
		preg_match_all("/\[(attachimg|img).*?\](.*?)\[\/\\1\]/", $message, $matches);
		if($matches) {
			foreach($matches[2] as $match) {
				if(!preg_match('/^\d+$/', $match)) {
					$result[] = $match;
				}
			}
		}
		return $result;
	}
}
