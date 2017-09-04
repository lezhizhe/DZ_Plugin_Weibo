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
if(!defined('SINA_LOGIN_FSOCKOPEN')) {
	define('SINA_LOGIN_FSOCKOPEN', false);
}
class OAuthException extends Exception {
}
class sina_login_oauth {
	
	public $client_id;
	public $client_secret;
	public $access_token;
	public $refresh_token;
	public $http_code;
	public $url;
	public $host = "https://api.weibo.com/";
	public $timeout = 30;
	public $connecttimeout = 30;
	public $ssl_verifypeer = FALSE;
	public $format = 'json';
	public $decode_json = TRUE;
	public $http_info;
	public $useragent = 'Sae T OAuth2 v0.1';
	public $debug = FALSE;
	public $http = NULL;	
	public static $boundary = '';

	function accessTokenURL()  { return $this->host.'oauth2/access_token'; }
	
	function authorizeURL()    { return $this->host.'oauth2/authorize'; }

	function setHttp() {
		if(NULL == $this->http) {
			$this->http = new sina_login_fsockopenHttp();
		}
	}
	
	function __construct($client_id, $client_secret, $access_token = NULL, $refresh_token = NULL) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->access_token = $access_token;
		$this->refresh_token = $refresh_token;
	}

	function getAuthorizeURL( $url, $response_type = 'code', $state = NULL, $display = NULL , $forcelogin = 'false') {
		$params = array();
		$params['client_id'] = $this->client_id;
		$params['redirect_uri'] = $url;
		$params['response_type'] = $response_type;
		$params['state'] = $state;
		$params['display'] = $display;
		$params['forcelogin'] = $forcelogin;
		return $this->authorizeURL() . "?" . http_build_query($params);
	}

	function getAccessToken( $type = 'code', $keys ) {
		$params = array();
		$params['client_id'] = $this->client_id;
		$params['client_secret'] = $this->client_secret;
		if ( $type === 'token' ) {
			$params['grant_type'] = 'refresh_token';
			$params['refresh_token'] = $keys['refresh_token'];
		} elseif ( $type === 'code' ) {
			$params['grant_type'] = 'authorization_code';
			$params['code'] = $keys['code'];
			$params['redirect_uri'] = $keys['redirect_uri'];
		} elseif ( $type === 'password' ) {
			$params['grant_type'] = 'password';
			$params['username'] = $keys['username'];
			$params['password'] = $keys['password'];
		} else {
			throw new OAuthException("wrong auth type");
		}

		$response = $this->oAuthRequest($this->accessTokenURL(), 'POST', $params);
		$token = json_decode($response, true);
		if ( is_array($token) && !isset($token['error']) ) {
			$this->access_token = $token['access_token'];
			$this->refresh_token = $token['refresh_token'];
		} else {
			throw new OAuthException("get access token failed." . var_export($token, true));
		}
		return $token;
	}

	
	function parseSignedRequest($signed_request) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2); 
		$sig = self::base64decode($encoded_sig) ;
		$data = json_decode(self::base64decode($payload), true);
		if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') return '-1';
		$expected_sig = hash_hmac('sha256', $payload, $this->client_secret, true);
		return ($sig !== $expected_sig)? '-2':$data;
	}

	
	function base64decode($str) {
		return base64_decode(strtr($str.str_repeat('=', (4 - strlen($str) % 4)), '-_', '+/'));
	}

	
	function getTokenFromArray( $arr ) {
		if (isset($arr['access_token']) && $arr['access_token']) {
			$token = array();
			$this->access_token = $token['access_token'] = $arr['access_token'];
			if (isset($arr['refresh_token']) && $arr['refresh_token']) {
				$this->refresh_token = $token['refresh_token'] = $arr['refresh_token'];
			}

			return $token;
		} else {
			return false;
		}
	}

	
	function get($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response, true);
		}
		return $response;
	}

	
	function post($url, $parameters = array(), $multi = false) {
		$response = $this->oAuthRequest($url, 'POST', $parameters, $multi );
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response, true);
		}
		return $response;
	}

	
	function delete($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response, true);
		}
		return $response;
	}

	function oAuthRequest($url, $method, $parameters, $multi = false) {

		if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) {
			$url = "{$this->host}{$url}.{$this->format}";
		}

		switch ($method) {
			case 'GET':
				$url = $url . '?' . http_build_query($parameters);
				return $this->http($url, 'GET');
			default:
				$headers = array();
				if (!$multi && (is_array($parameters) || is_object($parameters)) ) {
					$body = http_build_query($parameters);
				} else {
					$body = self::build_http_query_multi($parameters);
					$headers[] = "Content-Type: multipart/form-data; boundary=" . self::$boundary;
				}
				return $this->http($url, $method, $body, $headers);
		}
	}
	
	function http($url, $method, $postfields = NULL, $headers = array()) {
	
		if(!SINA_LOGIN_FSOCKOPEN && $this->curl_on()) {
			return $this->curl_http($url, $method, $postfields, $headers);
		} else {
			return $this->fsockopen_http($url, $method, $postfields, $headers);
		}
	}
	
	function curl_on() {
	
		return function_exists('curl_init') && function_exists('curl_exec');
	}
	 /**
     * Make an HTTP request
     * @param string $url 完整的URL
     * @param string $method 方法，大写 
     * @param string $post 需要post提交的数据
     * @param array $header 
     * @return 原始数据
     */
	function fsockopen_http($url, $method, $postfields = NULL, $headers = array()) {
		$this->setHttp();
		$method = strtoupper($method);
		$this->http->setHeader('API-RemoteIP', $_SERVER['REMOTE_ADDR']);
		$this->http->setHeader('User-Agent', $this->useragent);
		foreach($headers as $val) {
			list($key, $value) = explode(':', $val, 2);		
			$this->http->setHeader($key, $value);
		}
		if(isset($this->access_token) && $this->access_token) {
			$this->http->setHeader('Authorization', 'OAuth2 '.$this->access_token);
		}
		
        switch ($method) {
        	case 'GET':
				$this->http->setUrl($url);
				$result = $this->http->request('get', true);
				break;

			case 'DELETE':
			default:
				$this->http->setUrl($url);
				$this->http->setData($postfields);
				$result = $this->http->request($method == 'DELETE' ? 'delete' : 'post', true);
				break;
      	 }
		$this->http_code = $code = $this->http->getState();
		$this->url = $http_url = $this->http->getUrl();
		
		if( 200 != $code ){
			$this->http_info = array('error' => 'http error');
		}
		return $result;
		
	}
	
	function curl_http($url, $method, $postfields = NULL, $headers = array()) {
		$this->http_info = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_ENCODING, "");
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);

		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
					$this->postdata = $postfields;
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}

		if ( isset($this->access_token) && $this->access_token )
			$headers[] = "Authorization: OAuth2 ".$this->access_token;

		$headers[] = "API-RemoteIP: " . $_SERVER['REMOTE_ADDR'];
		curl_setopt($ci, CURLOPT_URL, $url );
		curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );

		$response = curl_exec($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		
		curl_close ($ci);
		return $response;
	}
	
	function getHeader($ch, $header) {
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}

	public static function build_http_query_multi($params) {
		if (!$params) return '';

		uksort($params, 'strcmp');

		$pairs = array();

		self::$boundary = $boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';
		$multipartbody = '';

		foreach ($params as $parameter => $value) {

			if( in_array($parameter, array('pic', 'image')) && $value{0} == '@' ) {
				$url = ltrim( $value, '@' );
				$content = file_get_contents( $url );
				$array = explode( '?', basename( $url ) );
				$filename = $array[0];

				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
				$multipartbody .= "Content-Type: image/unknown\r\n\r\n";
				$multipartbody .= $content. "\r\n";
			} else {
				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
				$multipartbody .= $value."\r\n";
			}

		}

		$multipartbody .= $endMPboundary;
		return $multipartbody;
	}
}

class sina_client {
	
	var $oauth;
	
	function __construct($sina_oauth2) {
		
		$this->oauth = $sina_oauth2;
	}
	
	function repost( $sid, $text = NULL, $is_comment = 0 ) {
		$this->id_format($sid);

		$params = array();
		$params['id'] = $sid;
		$params['is_comment'] = $is_comment;
		if( $text ) $params['status'] = $text;

		return $this->oauth->post( 'statuses/repost', $params  );
	}
	
	function delete( $id ) {
		return $this->destroy( $id );
	}

	/**
	 * 通过微博（评论、私信）ID获取其MID
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/statuses/querymid statuses/querymid}
	 *
	 * @param int|string $id  需要查询的微博（评论、私信）ID，批量模式下，用半角逗号分隔，最多不超过20个。
	 * @param int $type  获取类型，1：微博、2：评论、3：私信，默认为1。
	 * @param int $is_batch 是否使用批量模式，0：否、1：是，默认为0。
	 * @return array
	 */
	function querymid( $id, $type = 1, $is_batch = 0 ) {
		$params = array();
		$params['id'] = $id;
		$params['type'] = intval($type);
		$params['is_batch'] = intval($is_batch);
		return $this->oauth->get( 'statuses/querymid',  $params);
	}

	/**
	 * 通过微博（评论、私信）MID获取其ID
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/statuses/queryid statuses/queryid}
	 *
	 * @param int|string $mid  需要查询的微博（评论、私信）MID，批量模式下，用半角逗号分隔，最多不超过20个。
	 * @param int $type  获取类型，1：微博、2：评论、3：私信，默认为1。
	 * @param int $is_batch 是否使用批量模式，0：否、1：是，默认为0。
	 * @param int $inbox  仅对私信有效，当MID类型为私信时用此参数，0：发件箱、1：收件箱，默认为0 。
	 * @param int $isBase62 MID是否是base62编码，0：否、1：是，默认为0。
	 * @return array
	 */
	function queryid( $mid, $type = 1, $is_batch = 0, $inbox = 0, $isBase62 = 0) {
		$params = array();
		$params['mid'] = $mid;
		$params['type'] = intval($type);
		$params['is_batch'] = intval($is_batch);
		$params['inbox'] = intval($inbox);
		$params['isBase62'] = intval($isBase62);
		return $this->oauth->get('statuses/queryid', $params);
	}

	function destroy( $id ) {
		$this->id_format($id);
		$params = array();
		$params['id'] = $id;
		return $this->oauth->post( 'statuses/destroy',  $params );
	}
	
	function update( $status, $lat = NULL, $long = NULL, $annotations = NULL ) {
		return $this->share($status);
		$params = array();
		$params['status'] = $status;
		if ($lat) {
			$params['lat'] = floatval($lat);
		}
		if ($long) {
			$params['long'] = floatval($long);
		}
		if (is_string($annotations)) {
			$params['annotations'] = $annotations;
		} elseif (is_array($annotations)) {
			$params['annotations'] = json_encode($annotations);
		}

		return $this->oauth->post( 'statuses/update', $params );
	}

	function upload($status, $pic_path, $lat = NULL, $long = NULL ) {
		return $this->share($status, $pic_path);
		$params = array();
		$params['status'] = $status;
		$params['pic'] = '@'.$pic_path;
		if ($lat) {
			$params['lat'] = floatval($lat);
		}
		if ($long) {
			$params['long'] = floatval($long);
		}

		return $this->oauth->post( 'statuses/upload', $params, true );
	}

	function upload_url_text($status,  $url) {
		return $this->share($status, $url);
		$params = array();
		$params['status'] = $status;
		$params['url'] = $url;
		return $this->oauth->post('statuses/upload_url_text', $params);
	}

	function share($status, $pic_path = false) {
		$params = array();
		$params['status'] = $status;
		if(false !== $pic_path) {
			$params['pic'] = '@'.$pic_path;
			return $this->oauth->post( 'statuses/share', $params, true );
		} else {
			return $this->oauth->post( 'statuses/share', $params );
		}
	}

	function emotions( $type = "face", $language = "cnname" ) {
		$params = array();
		$params['type'] = $type;
		$params['language'] = $language;
		return $this->oauth->get( 'emotions', $params );
	}
	
	function send_comment( $id , $comment , $comment_ori = 0) {
		$params = array();
		$params['comment'] = $comment;
		$this->id_format($id);
		$params['id'] = $id;
		$params['comment_ori'] = $comment_ori;
		return $this->oauth->post( 'comments/create', $params );
	}

	/**
	 * 根据ID获取单条微博信息内容
	 *
	 * 获取单条ID的微博信息，作者信息将同时返回。
	 * <br />对应API：{@link http://open.weibo.com/wiki/2/statuses/show statuses/show}
	 * 
	 * @access public
	 * @param int $id 要获取已发表的微博ID, 如ID不存在返回空
	 * @return array
	 */
	function show_status( $id ) {
		$this->id_format($id);
		$params = array();
		$params['id'] = $id;
		return $this->oauth->get('statuses/show', $params);
	}

	/**
	 * 根据微博ID返回某条微博的评论列表
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/comments/show comments/show}
	 *
	 * @param int $sid 需要查询的微博ID。
	 * @param int $page 返回结果的页码，默认为1。
	 * @param int $count 单页返回的记录条数，默认为50。
	 * @param int $since_id 若指定此参数，则返回ID比since_id大的评论（即比since_id时间晚的评论），默认为0。
	 * @param int $max_id  若指定此参数，则返回ID小于或等于max_id的评论，默认为0。
	 * @param int $filter_by_author 作者筛选类型，0：全部、1：我关注的人、2：陌生人，默认为0。
	 * @return array
	 */
	function get_comments_by_sid( $sid, $page = 1, $count = 50, $since_id = 0, $max_id = 0, $filter_by_author = 0 )
	{
		$params = array();
		$this->id_format($sid);
		$params['id'] = $sid;
		if ($since_id) {
			$this->id_format($since_id);
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$this->id_format($max_id);
			$params['max_id'] = $max_id;
		}
		$params['count'] = $count;
		$params['page'] = $page;
		$params['filter_by_author'] = $filter_by_author;
		return $this->oauth->get( 'comments/show',  $params );
	}
	
	/**
	 * 返回一条原创微博消息的最新n条转发微博消息。本接口无法对非原创微博进行查询。
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/statuses/repost_timeline statuses/repost_timeline}
	 *
	 * @access public
	 * @param int $sid 要获取转发微博列表的原创微博ID。
	 * @param int $page 返回结果的页码。
	 * @param int $count 单页返回的最大记录数，最多返回200条，默认50。可选。
	 * @param int $since_id 若指定此参数，则只返回ID比since_id大的记录（比since_id发表时间晚）。可选。
	 * @param int $max_id 若指定此参数，则返回ID小于或等于max_id的记录。可选。
	 * @param int $filter_by_author 作者筛选类型，0：全部、1：我关注的人、2：陌生人，默认为0。
	 * @return array
	 */
	function repost_timeline( $sid, $page = 1, $count = 50, $since_id = 0, $max_id = 0, $filter_by_author = 0 )
	{
		$this->id_format($sid);
	
		$params = array();
		$params['id'] = $sid;
		if ($since_id) {
			$this->id_format($since_id);
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$this->id_format($max_id);
			$params['max_id'] = $max_id;
		}
		$params['filter_by_author'] = intval($filter_by_author);
	
		return $this->request_with_pager( 'statuses/repost_timeline', $page, $count, $params );
	}
	
	/**
	 * 获取当前登录用户所发出的评论列表
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/comments/by_me comments/by_me}
	 *
	 * @param int $since_id 若指定此参数，则返回ID比since_id大的评论（即比since_id时间晚的评论），默认为0。
	 * @param int $max_id 若指定此参数，则返回ID小于或等于max_id的评论，默认为0。
	 * @param int $count  单页返回的记录条数，默认为50。
	 * @param int $page 返回结果的页码，默认为1。
	 * @param int $filter_by_source 来源筛选类型，0：全部、1：来自微博的评论、2：来自微群的评论，默认为0。
	 * @return array
	 */
	function comments_by_me( $page = 1 , $count = 50, $since_id = 0, $max_id = 0,  $filter_by_source = 0 )
	{
		$params = array();
		if ($since_id) {
			$this->id_format($since_id);
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$this->id_format($max_id);
			$params['max_id'] = $max_id;
		}
		$params['count'] = $count;
		$params['page'] = $page;
		$params['filter_by_source'] = $filter_by_source;
		return $this->oauth->get( 'comments/by_me', $params );
	}
	
	function get_short_url($url) {
		$params=array();
		$params['source'] = $this->oauth->client_id;
		$params['url_long'] = $url;
		return $this->oauth->get('2/short_url/shorten', $params);
	}

	function show_user_by_id( $uid ) {
		$params=array();
		if ( $uid !== NULL ) {
			$this->id_format($uid);
			$params['uid'] = $uid;
		}

		return $this->oauth->get('users/show', $params );
	}
	
	function follow_by_id( $uid ) {
		$params = array();
		$this->id_format($uid);
		$params['uid'] = $uid;
		return $this->oauth->post( 'friendships/create', $params );
	}
	
	function follow_by_name( $screen_name ) {
		$params = array();
		$params['screen_name'] = $screen_name;
		return $this->oauth->post( 'friendships/create', $params);
	}

	function unfollow_by_id( $uid ) {
		$params = array();
		$this->id_format($uid);
		$params['uid'] = $uid;
		return $this->oauth->post( 'friendships/destroy', $params);
	}
	
	function unfollow_by_name( $screen_name ) {
		$params = array();
		$params['screen_name'] = $screen_name;
		return $this->oauth->post( 'friendships/destroy', $params);
	}

	function account_profile_basic( $uid = NULL  ) {
		$params = array();
		if ($uid) {
			$this->id_format($uid);
			$params['uid'] = $uid;
		}
		return $this->oauth->get( 'account/profile/basic', $params );
	}

	function account_education( $uid = NULL )
	{
		$params = array();
		if ($uid) {
			$this->id_format($uid);
			$params['uid'] = $uid;
		}
		return $this->oauth->get( 'account/profile/education', $params );
	}

	function account_education_batch( $uids  )
	{
		$params = array();
		if (is_array($uids) && !empty($uids)) {
			foreach($uids as $k => $v) {
				$this->id_format($uids[$k]);
			}
			$params['uids'] = join(',', $uids);
		} else {
			$params['uids'] = $uids;
		}

		return $this->oauth->get( 'account/profile/education_batch', $params );
	}


	function account_career( $uid = NULL )
	{
		$params = array();
		if ($uid) {
			$this->id_format($uid);
			$params['uid'] = $uid;
		}
		return $this->oauth->get( 'account/profile/career', $params );
	}

	function account_career_batch( $uids )
	{
		$params = array();
		if (is_array($uids) && !empty($uids)) {
			foreach($uids as $k => $v) {
				$this->id_format($uids[$k]);
			}
			$params['uids'] = join(',', $uids);
		} else {
			$params['uids'] = $uids;
		}

		return $this->oauth->get( 'account/profile/career_batch', $params );
	}

	function get_privacy()
	{
		return $this->oauth->get('account/get_privacy');
	}
	
	/**
	 * 获取当前登录用户的API访问频率限制情况
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/account/rate_limit_status account/rate_limit_status}
	 * 
	 * @access public
	 * @return array
	 */
	function rate_limit_status()
	{
		return $this->oauth->get( 'account/rate_limit_status' );
	}

	/**
	 * OAuth授权之后，获取授权用户的UID
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/account/get_uid account/get_uid}
	 * 
	 * @access public
	 * @return array
	 */
	function get_uid()
	{
		return $this->oauth->get( 'account/get_uid' );
	}

	/**
	 * 获取系统推荐用户
	 *
	 * 返回系统推荐的用户列表。
	 * <br />对应API：{@link http://open.weibo.com/wiki/2/suggestions/users/hot suggestions/users/hot}
	 * 
	 * @access public
	 * @param string $category 分类，可选参数，返回某一类别的推荐用户，默认为 default。如果不在以下分类中，返回空列表：<br />
	 *  - default:人气关注
	 *  - ent:影视名星
	 *  - hk_famous:港台名人
	 *  - model:模特
	 *  - cooking:美食&健康
	 *  - sport:体育名人
	 *  - finance:商界名人
	 *  - tech:IT互联网
	 *  - singer:歌手
	 *  - writer：作家
	 *  - moderator:主持人
	 *  - medium:媒体总编
	 *  - stockplayer:炒股高手
	 * @return array
	 */
	function hot_users( $category = "default" )
	{
		$params = array();
		$params['category'] = $category;

		return $this->oauth->get( 'suggestions/users/hot', $params );
	}

	/**
	 * 获取用户可能感兴趣的人
	 *
	 * 对应API：{@link http://open.weibo.com/wiki/2/suggestions/users/may_interested suggestions/users/may_interested}
	 * 
	 * @access public
	 * @param int $page 返回结果的页码，默认为1。
	 * @param int $count 单页返回的记录条数，默认为10。
	 * @return array
	 * @ignore
	 */
	function suggestions_may_interested( $page = 1, $count = 10 )
	{   
		$params = array();
		$params['page'] = $page;
		$params['count'] = $count;
		return $this->oauth->get( 'suggestions/users/may_interested', $params);
	}

	// =========================================

	protected function request_with_pager( $url, $page = false, $count = false, $params = array() )
	{
		if( $page ) $params['page'] = $page;
		if( $count ) $params['count'] = $count;

		return $this->oauth->get($url, $params );
	}

	
	protected function request_with_uid( $url, $uid_or_name, $page = false, $count = false, $cursor = false, $post = false, $params = array())
	{
		if( $page ) $params['page'] = $page;
		if( $count ) $params['count'] = $count;
		if( $cursor )$params['cursor'] =  $cursor;

		if( $post ) $method = 'post';
		else $method = 'get';

		if ( $uid_or_name !== NULL ) {
			$this->id_format($uid_or_name);
			$params['id'] = $uid_or_name;
		}

		return $this->oauth->$method($url, $params );

	}

	
	protected function id_format(&$id) {
		if ( is_float($id) ) {
			$id = number_format($id, 0, '', '');
		} elseif ( is_string($id) ) {
			$id = trim($id);
		}
	}

}
class sina_login_fsockopenHttp {
    // local variables
    var $headers, $status, $resttl, $cookies, $socks, $verbose;
    var $post_files, $post_fields;
    var $triggered_error = array();
    //此值至少要大于等于2
	var $max_retries = 3;
	var $_serverUrl;
	var $mimes = array(
						'gif' => 'image/gif',
						'png' => 'image/png',
						'bmp' => 'image/bmp',
						'jpeg' => 'image/jpeg',
						'pjpg' => 'image/pjpg',
						'jpg' => 'image/jpeg',
						'tif' => 'image/tiff',
						'htm' => 'text/html',
						'css' => 'text/css',
						'html' => 'text/html',
						'txt' => 'text/plain',
						'gz' => 'application/x-gzip',
						'tgz' => 'application/x-gzip',
						'tar' => 'application/x-tar',
						'zip' => 'application/zip',
						'hqx' => 'application/mac-binhex40',
						'doc' => 'application/msword',
						'pdf' => 'application/pdf',
						'ps' => 'application/postcript',
						'rtf' => 'application/rtf',
						'dvi' => 'application/x-dvi',
						'latex' => 'application/x-latex',
						'swf' => 'application/x-shockwave-flash',
						'tex' => 'application/x-tex',
						'mid' => 'audio/midi',
						'au' => 'audio/basic',
						'mp3' => 'audio/mpeg',
						'ram' => 'audio/x-pn-realaudio',
						'ra' => 'audio/x-realaudio',
						'rm' => 'audio/x-pn-realaudio',
						'wav' => 'audio/x-wav',
						'wma' => 'audio/x-ms-media',
						'wmv' => 'video/x-ms-media',
						'mpg' => 'video/mpeg',
						'mpga' => 'video/mpeg',
						'wrl' => 'model/vrml',
						'mov' => 'video/quicktime',
						'avi' => 'video/x-msvideo',
						'xml' => 'text/xml',
						'bin' => 'application/octet-stream',
						'js' => 'application/x-javascript',
					);


    // construct function
    function sina_login_fsockopenHttp($verbose = false) {
        $this->__construct($verbose);
    }

    function __construct($verbose = false) {
        $this->verbose = $verbose;
        $this->cookies = array();
        $this->socks = array();

        $this->_reset_status();
    }

    function __destruct() {
        foreach ($this->socks as $host => $sock) { @fclose($sock); }
    }

	function setUrl($url) {
		$this->_serverUrl = $url;
		return $this;
	}

	function setData($data) {
        $this->_reset_status();
		if (is_array($data)) {
			foreach ($data as $key => $var) {
				$this->post_fields[] = array($key, $var);
			}
		} else {
			$this->post_fields = $data;
		}

		return $this;
	}

	function setConfig($config) {
		foreach ($config as $var) {
			foreach($var as $k) {
				$headers = explode(':', $k);
				$headers[1] = trim($headers[1]);
				if (empty($headers[1])) {
					continue;
				}
				$this->setHeader($headers[0], $headers[1]);
			}
		}
	}

	function getState() {
        return $this->status;
	}

	/**
	 * 获取调用的url
	 *
	 * @return string
	 */
	function getUrl() {
		return $this->_serverUrl;
	}	
	
	function request($method = null, $https = false) {
		$method = empty($method) ? 'GET' : strtoupper($method);
        switch ($method) {
			case 'GET':
				$result = $this->Get($this->_serverUrl);
				break;
			case 'POST':
				$result = $this->Post($this->_serverUrl);
				break;
			case 'HEAD':
				$result = $this->Head($this->_serverUrl);
				break;
			default:
				$result = $this->_do_url($this->_serverUrl, $method);
				break;
        }
		return $result;
	}

    // get the HTTP status of the last request!!
    function getStatus() {
        return $this->status;
    }

    // get the HTTP respond Ttitle
    function getResttl() {
        return $this->resttl;
    }

    // set a http header for the next request!
    function setHeader($key, $value) {
        $this->_reset_status();
        $key = strtolower($key);
        $this->headers[$key] = $value;
    }

    // set a cookie for the next request!
    function setCookie($key, $value) {
        if (!isset($this->headers['cookie'])) $this->headers['cookie'] = array();
        $this->headers['cookie'][$key] = $value;
    }

    // get the HTTP header from the last request!
    function getHeader($key = null) {
        if (is_null($key)) return $this->headers;
        $key = strtolower($key);
        if (!isset($this->headers[$key])) return null;
        return $this->headers[$key];
    }

    // get the cookie from the last request or by host
    function getCookie($key = null, $host = null) {
        if (is_null($host))
        {
            if (!isset($this->headers['cookie'])) return null;
            if (is_null($key)) return $this->headers['cookie'];
            if (isset($this->headers['cookie'][$key])) return $this->headers['cookie'][$key];
            return null;
        }
        else
        {
            if (!isset($this->cookies[$host])) return null;
            if (is_null($key)) return $this->cookies[$host];
            if (isset($this->cookies[$host][$key])) return $this->cookies[$host][$key];
            return null;
        }
    }

    // save cookie to external place
    function saveCookie($fpath)
    {
        if ($fd = @fopen($fpath, 'w'))
        {
            $data = serialize($this->cookies);
            fwrite($fd, $data);
            fclose($fd);
            return true;
        }
        return false;
    }

    // restore cookie from external place
    function loadCookie($fpath)
    {
        if (file_exists($fpath)) $this->cookies = unserialize(@file_get_contents($fpath));
    }

    // add post field for next request
    function addPostField($key, $value)
    {
        $this->_reset_status();
        $this->post_fields[] = array($key, $value);
    }

    // add a multipart post file for the next request
    function addPostFile($key, $fname, $content = null)
    {
        $this->_reset_status();
        if (is_null($content) && is_file($fname))
        {
            $content = file_get_contents($fname);
            $fname = basename($fname);
        }
        $this->post_files[] = array($key, $fname, $content);
    }

    // do a HTTP/get
    function Get($url, $redir = true)
    {
        return $this->_do_url($url, 'get', null, $redir);
    }

    // do a HTTP/head
    function Head($url)
    {
        return $this->_do_url($url, 'head');
    }

    // do a HTTP/post
    function Post($url, $redir = true)
    {
        $data = '';
        if (count($this->post_files) > 0)
        {
            $boundary = md5($url . microtime());
            foreach ($this->post_fields as $tmp)
            {
                $data .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$tmp[0]}\"\r\n\r\n{$tmp[1]}\r\n";
            }
            foreach ($this->post_files as $tmp)
            {
                $type = 'application/octet-stream';
                $ext = strtolower(substr($tmp[1], strrpos($tmp[1],'.')+1));
                if (isset($this->mimes[$ext])) $type = $this->mimes[$ext];
                $data .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$tmp[0]}\"; filename=\"{$tmp[1]}\"\r\nContent-Type: $type\r\nContent-Transfer-Encoding: binary\r\n\r\n";
                $data .= $tmp[2] . "\r\n";
            }
            $data .= "--{$boundary}--\r\n";
            $this->setHeader('content-type', 'multipart/form-data; boundary=' . $boundary);
        }
        else
        {
			if (!is_array($this->post_fields)) {
				$data = $this->post_fields;
			} else {
				foreach ($this->post_fields as $tmp)
				{
					$data .= '&' . $this->_format_field($tmp[0], $tmp[1]);
				}
			}
            $data = ltrim($data, '&');
        }
        $dlen = strlen($data);
        $this->setHeader('content-length', $dlen);
		if (empty($this->headers['content-type'])) {
			$this->setHeader('content-type', 'application/x-www-form-urlencoded');
		}
        return $this->_do_url($url, 'post', $data, $redir);
    }

    // -------------------------------------------------
    // private functions
    // -------------------------------------------------
    // read data from socket
    function _sock_read($fd, $maxlen = 4096)
    {
        $rlen = 0;
        $data = '';
        $ntry = $this->max_retries;
        while (!feof($fd))
        {
            $part = fread($fd, $maxlen - $rlen);
            if ($part === false || $part === '') $ntry--;
            else $data .= $part;
            $rlen = strlen($data);
            if ($rlen == $maxlen || $ntry == 0) break;
        }
        if ($ntry == 0) fclose($fd);
        return $data;
    }

    // write data to socket
    function _sock_write($fd, $buf)
    {
        $wlen = 0;
        $tlen = strlen($buf);
        $ntry = $this->max_retries;
        while ($wlen < $tlen)
        {
            $nlen = fwrite($fd, substr($buf, $wlen), $tlen - $wlen);
            if (!$nlen) { if (--$ntry == 0) return false; }
            else $wlen += $nlen;
        }
        return true;
    }

    // reset some request data (status)
    function _reset_status()
    {
        if ($this->status !== 0)
        {
            $this->status = 0;
            $this->headers = $this->post_files = $this->post_fields = array();
        }
    }

    // format post field
    function _format_field($key, $value)
    {
        if (!is_array($value))
            $ret = $key . '=' . rawurlencode($value);
        else
        {
            $ret = '';
            foreach ($value as $k => $v)
            {
                $ret .= '&' . $this->_format_field($key . '[' . $k . ']', $v);
            }
            $ret = substr($ret, 1);
        }
        return $ret;
    }

    // do a url method
    function _do_url($url, $method, $data = null, $redir = true)
    {
        // check the url
        if (strncasecmp($url, 'http://', 7) && strncasecmp($url, 'https://', 8))
        {
            $base = 'http://' . $_SERVER['HTTP_HOST'];
            if (substr($url, 0, 1) != '/')
                $url = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')+1) . $url;
            $url = $base . $url;
        }

        // parse the url
        $url = str_replace('&amp;', '&', $url);
        $pa = @parse_url($url);
        if ($pa['scheme'] && $pa['scheme'] != 'http' && $pa['scheme'] != 'https')
        {
            $this->_trigger_error("Unsupported scheme `{$pa['scheme']}`", E_USER_WARNING);
            return false;
        }
        if (!isset($pa['port'])) $pa['port'] = ($pa['scheme'] == 'https' ? 443 : 80);
        if (!isset($pa['path'])) $pa['path'] = '/';
        $host = strtolower($pa['host']);
        $port = intval($pa['port']);
        $skey = $host . ':' . $port;
        if ($pa['scheme'] && $pa['scheme'] == 'https') $host_conn = 'ssl://' . $host;
        else $host_conn = 'tcp://' . $host;

        // make the query buffer
        $method = strtoupper($method);
        $buf = $method . ' ' . $pa['path'];
        if (isset($pa['query'])) $buf .= '?' . $pa['query'];
        $buf .= " HTTP/1.1\r\nHost: {$host}\r\n";

        // set default HTTP/headers
        $this->_reset_status();
//        if (!isset($this->headers['user-agent'])) $buf .= "User-Agent: Xweibo 1.0\r\n";
        if (!isset($this->headers['accept'])) $buf .= "Accept: */*\r\n";
//        if (!isset($this->headers['accept-language'])) $buf .= "Accept-Language: zh-cn,zh\r\n";
//        if (!isset($this->headers['connection'])) $buf .= "Connection: Keep-Alive\r\n";
        if (isset($this->headers['accept-encoding'])) unset($this->headers['accept-encoding']);
        if (isset($this->headers['host'])) unset($this->headers['host']);

        // saved cookies (session data)
        $now = time();
        $ck_str = '';
        foreach ($this->cookies as $ck_host => $ck_list)
        {
            if (!stristr($host, $ck_host)) continue;
            foreach ($ck_list as $ck => $cv)
            {
                if ($cv['expires'] > 0 && $cv['expires'] < $now) continue;
                if (strncmp($pa['path'], $cv['path'], strlen($cv['path']))) continue;
                $ck_str .= '; ' . rawurlencode($ck) . '=' . rawurlencode($cv['value']);
            }
        }
        if ($ck_str != '') $buf .= 'Cookie:' . substr($ck_str, 1) . "\r\n";
        foreach ($this->headers as $k => $v)
        {
        	if($k == 'api-remoteip'){
        		$buf .= "API-RemoteIP: " . $v . "\r\n";
        	}elseif ($k != 'cookie'){
                $buf .= ucfirst($k) . ": " . $v . "\r\n";
    		}else{
                $vv = '';
                foreach ($v as $ck => $cv) $vv .= '; ' . rawurlencode($ck) . '=' . rawurlencode($cv);
                if ($vv != '') $buf .= 'Cookie:' . substr($vv, 1) . "\r\n";
            }
        }
        $buf .= "\r\n";
        if ($method == 'POST') $buf .= $data . "\r\n";

        // force reset status for next query even if failed this time.
        $this->status = -1;

        // show the header buf
        if ($this->verbose)
        {
            echo "[SEND] request buffer\r\n----\r\n";
            echo $buf;
            echo "----\r\n";
        }

        // create the sock & send the header
        $ntry = $this->max_retries;
        $sock = isset($this->socks[$skey]) ? $this->socks[$skey] : false;
        do
        {
            if ($sock && $this->_sock_write($sock, $buf)) break;
            if ($sock) @fclose($sock);
            $sock = @fsockopen($host_conn, $port, $errno, $error, 3);
            if ($sock)
            {
                stream_set_blocking($sock, 1);
                stream_set_timeout($sock, 10);
            }
        }
        while (--$ntry);
        if (!$sock)
        {
            if (isset($this->socks[$skey])) unset($this->socks[$skey]);
            $this->_trigger_error("Cann't connect to `$host:$port'", E_USER_WARNING);
            return false;
        }
        $this->socks[$skey] = $sock;
        if ($this->verbose)
        {
            echo "[SEND] using socket = {$sock}\r\n";
            echo "[RECV] http respond header\r\n----\r\n";
        }

        // read the respond header
        $this->headers = array();
        while ($line = fgets($sock, 2048))
        {
            if ($this->verbose) echo $line;
            $line = trim($line);
            if ($line === '') break;
            if (!strncasecmp('HTTP/', $line, 5))
            {
                $line = trim(substr($line, strpos($line, ' ')));
                list($this->status, $this->resttl) = explode(' ', $line, 2);
                $this->status = intval($this->status);
            }
            else if (!strncasecmp('Set-Cookie: ', $line, 12))
            {
                $ck_key = '';
                $ck_val = array('value' => '', 'expires' => 0, 'path' => '/', 'domain' => $host);
                $tmpa = explode(';', substr($line, 12));
                foreach ($tmpa as $tmp)
                {
                    $tmp = trim($tmp);
                    if (empty($tmp)) continue;
                    list($tmpk, $tmpv) = explode('=', $tmp, 2);
                    $tmpk2 = strtolower($tmpk);
                    if ($ck_key == '')
                    {
                        $ck_key = rawurldecode($tmpk);
                        $ck_val['value'] = rawurldecode($tmpv);
                    }
                    else if ($tmpk2 == 'expires')
                    {
                        $ck_val['expires'] = strtotime($tmpv);
                        if ($ck_val['expires'] < $now)
                        {
                            $ck_val['value'] = '';
                            break;
                        }
                    }
                    else if (isset($ck_val[$tmpk2]) && $tmpv != '')
                    {
                        $ck_val[$tmpk2] = $tmpv;
                    }
                }

                // delete cookie?
                if ($ck_key == '') continue;
                if ($ck_val['value'] == '') unset($this->cookies[$ck_val['domain']][$ck_key]);
                else $this->cookies[$ck_val['domain']][$ck_key] = $ck_val;

                // headers.
                $this->headers['cookie'][$ck_key] = $ck_val;
            }
            else
            {
                list($k, $v) = explode(':', $line, 2);
                $k = strtolower(trim($k));
                $v = trim($v);
                $this->headers[$k] = $v;
            }
        }
        if ($this->verbose) echo "----\r\n";

        // get body
        if ($method == 'HEAD') return ($this->status == 200);
        $connection = $this->getHeader('connection');
        $encoding = $this->getHeader('transfer-encoding');
        $length = $this->getHeader('content-length');
        if ($encoding && !strcasecmp($encoding, 'chunked'))
        {
            $body = '';
            while (true)
            {
                if (!($line = fgets($sock, 1024))) break;
                if ($this->verbose) echo "[RECV] Chunk Line: " . $line;
                if ( false !== ( $p1 = strpos($line, ';') ) ) $line = substr($line, 0, $p1);
                $chunk_len = hexdec(trim($line));
                if ($chunk_len <= 0) break;    // end the chunk
                $body .= $this->_sock_read($sock, $chunk_len);
                fread($sock, 2);            // eat the CRLF
            }

            // trailer header
            if ($this->verbose) echo "[RECV] chunk tailer\r\n----\r\n";
            while ($line = fgets($sock, 2048))
            {
                if ($this->verbose) echo $line;
                $line = trim($line);
                if ($line === '') break;
                list($k, $v) = explode(':', $line, 2);
                $k = strtolower(trim($k));
                $v = trim($v);
                $this->headers[$k] = $v;
            }
            if ($this->verbose) echo "----\r\n";
        }
        else if (isset($length))
        {
            $length = intval($length);
            if ($length > 0) $body = $this->_sock_read($sock, $length);
            else $body = '';
        }
        else
        {
            $body = '';
            $ntry = $this->max_retries;
            while (!feof($sock) && $ntry > 0)
            {
                $part = fread($sock, 8192);
                if ($part === false || $part === '') $ntry--;
                else $body .= $part;
            }
            $connection = 'close';
        }

        // check close connection?
        if ($connection && !strcasecmp($connection, 'close'))
        {
            @fclose($sock);
            unset($this->socks[$skey]);
        }

        // check redirect
        if ($redir && $this->status != 200 && ($location = $this->getHeader('location')))
        {
            if (!preg_match('/^http[s]?:\/\//i', $location))
            {
                $url2 = $pa['scheme'] . '://' . $pa['host'];
                if (strpos($url, ':', 8)) $url2 .= ':' . $pa['port'];
                if (substr($location, 0, 1) == '/') $url2 .= $location;
                else $url2 .= substr($pa['path'], 0, strrpos($pa['path'], '/') + 1) . $location;
                $location = $url2;
            }
            return $this->_do_url($location, 'get');
        }

        // return the body buf
        return $body;
    }
    
    /**
     * 添加一个错误触发器，主要为了方便外部debug
     * 
     * @since 2010-08-24 15:39
     * @param $errmsg
     * @param $errno
     */
    function _trigger_error( $errmsg, $errno ){
    	$this->triggered_error[] = array('errmsg' => $errmsg, 'errno' => $errno );
    	trigger_error($errmsg, $errno);
    }
    
    /**
     * 获取已经触发的错误信息
     * @since 2010-08-24 15:39
     */
    function get_triggered_error(){
    	return $this->triggered_error;
    }
    
    
}
