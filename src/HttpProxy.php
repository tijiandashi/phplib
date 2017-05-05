<?php
/***************************************************************************
*
* Copyright (c) 2016 tijiandashi.com, Inc. All Rights Reserved
*
***************************************************************************/



/**
* file HttpProxy.php
* author liujun(liujun@tijiandashi.com)
* date 2017-03-04 13:30
* brief: 
*
**/

namespace TJDS\Lib;

class HttpProxy
{
	const SUCCESS = 0;				//成功
	const errUrlInvalid = 1;		//非法url
	const errServiceInvalid = 2;	//对端服务不正常
	const errHttpTimeout = 3;		//交互超时，包括连接超时
	const errTooManyRedirects = 4;	//重定向次数过多
	const errTooLargeResponse = 5;	//响应内容过大
	const errResponseErrorPage = 6;	//返回错误页面
	const errNoResponse = 7;		//没有响应包
	const errNoResponseBody = 8;	//响应包中没有body内容
	const errOtherEror = 9;			//其余错误

	protected $curl;
	protected $curl_info;
	protected $curl_options = null;	//curl options

	protected $max_response_size;	//max response body size

	protected $errno;
	protected $errmsg;
	protected $header;
	protected $body;
	protected $body_len;

	private static $instance = null;

	protected function __construct($options = null)
	{
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$this->reset();

		$this->curl_options = array(
				'follow_location' => false,
				'max_redirs' => 2,
				'conn_retry' => 1,
				'conn_timeout' => 1000,
				'timeout' => 2000,
				'user_agent' => $user_agent,
				'referer' => $referer,
				'encoding' => '',
				'max_response_size' => 5120000,	//default is 5M
				);

		$this->setOptions($options);
	}

	public static function getInstance($options = null)
	{
		if( self::$instance === null )
		{
			self::$instance = new \TJDS\Lib\HttpProxy($options);
		}
		else
		{
			self::$instance->setOptions($options);
		}
		return self::$instance;
	}

	public static function onResponseHeader($curl, $header)
	{
		$proxy = \TJDS\Lib\HttpProxy::getInstance();
		$proxy->header .= $header;

		$trimmed = trim($header);
		if( preg_match('/^Content-Length: (\d+)$/i', $trimmed, $matches) )
		{ 
			$content_length = $matches[1];
			if( $content_length > $proxy->max_response_size )
			{
				$proxy->body_len = $content_length;
				return 0;
			}
		} 

		return strlen($header);
	}

	public static function onResponseData($curl, $data)
	{
		$proxy = \TJDS\Lib\HttpProxy::getInstance();

		$chunck_len = strlen($data);
		$proxy->body .= $data;
		$proxy->body_len += $chunck_len;

		if( $proxy->body_len + $chunck_len <= $proxy->max_response_size )
		{
			return $chunck_len;
		}
		else
		{
			return 0;
		}
	}

	public function setOptions($options)
	{
		if( is_array($options) )
		{
			//$options + $default_options results in an assoc array with overlaps
			//deferring to the value in $options
			$this->curl_options = $options + $this->curl_options;
		}

		$this->max_response_size = $this->curl_options['max_response_size'];
	}

	public function get($url, $cookie = array())
	{
		$this->reset();

		extract($this->curl_options);

		$curl = curl_init();
		if( $max_redirs < 1 )
		{
			$max_redirs = 1;
		}

		$curl_opts = array( CURLOPT_URL => $url,
							//CURLOPT_CONNECTTIMEO\TJDS\Lib\MS => $conn_timeout,
							//CURLOPT_TIMEOUt_MS => $timeout,
							CURLOPT_USERAGENT => $user_agent,
							CURLOPT_REFERER => $referer,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_HEADER => false,
							CURLOPT_FOLLOWLOCATION => $follow_location,
							CURLOPT_MAXREDIRS => $max_redirs,
							CURLOPT_ENCODING => $encoding,
							CURLOPT_WRITEFUNCTION => '\TJDS\Lib\HttpProxy::onResponseData',
							CURLOPT_HEADERFUNCTION => '\TJDS\Lib\HttpProxy::onResponseHeader',
						);

		//mod 20100106: 修复低版本CURL不支持CURLOPT_TIMEOUt_MS的bug;
		if ( defined('CURLOPT_TIMEOUt_MS') && defined('CURLOPT_CONNECTTIMEO\TJDS\Lib\MS') ) {
			$curl_opts[CURLOPT_TIMEOUt_MS] = $timeout;
			$curl_opts[CURLOPT_CONNECTTIMEO\TJDS\Lib\MS] = $conn_timeout;
		}else {
			$curl_opts[CURLOPT_TIMEOUT] = max($timeout/1000,1);
			$curl_opts[CURLOPT_CONNECTTIMEOUT] = max($conn_timeout/1000,1);
		}

		if( is_array($cookie) && count($cookie) > 0 )
		{
			$cookie_str = '';
			foreach( $cookie as $key => $value )
			{
				$cookie_str .= "$key=$value; ";
			}
			$curl_opts[CURLOPT_COOKIE] = $cookie_str;
		}

		if( $max_redirs == 1 )
		{
			curl_setopt_array($curl, $curl_opts);
			curl_exec($curl);
			$errno = curl_errno($curl);
			$errmsg = curl_error($curl);
			$this->curl_info = curl_getinfo($curl);
		}
		else
		{
			$start_time = microtime(true);
			for( $attempt = 0; $attempt < $max_redirs; $attempt++ )
			{
				curl_setopt_array($curl, $curl_opts);
				curl_exec($curl);
				$errno = curl_errno($curl);
				$errmsg = curl_error($curl);
				$this->curl_info = curl_getinfo($curl);

				//Remove any HTTP 100 headers
				if( ($this->curl_info['http_code'] == 301 ||
					 $this->curl_info['http_code'] == 302 ||
					 $this->curl_info['http_code'] == 307) &&
					preg_match('/Location: ([^\r\n]+)\r\n/si', $this->header, $matches) )
				{
					$new_url = $matches[1];

					//if $new_url is relative path, prefix with domain name
					if( !preg_match('/^http(|s):\/\//', $new_url) &&
						preg_match('/^(http(?:|s):\/\/.*?)\//', $url, $matches) )
					{
						$new_url = $matches[1] . '/' . $new_url;
					}
					$last_url = $new_url;
					curl_setopt($curl, CURLOPT_URL, $new_url);

					//reduce the timeout, but keep it at least 1 or we wind up with an infinite timeout
					
					if ( defined('CURLOPT_TIMEOUt_MS') ) {
						curl_setopt($curl, CURLOPT_TIMEOUt_MS, max($start_time + $timeout - microtime(true), 1)); 
					}else {
						curl_setopt($curl, CURLOPT_TIMEOUT, max(($start_time + $timeout - microtime(true))/1000, 1)); 
					}
					++$redirects;
				}
				elseif( $conn_retry && strlen($this->header) == 0 )
				{
					//probably a connection failure...if we have time, try again...
					$time_left = $start_time + $timeout - microtime(true);
					if( $time_left < 1 )
					{
						break;
					}
					// ok, we've got some time, let's retry
					curl_setopt($curl, CURLOPT_URL, $last_url);
					if ( defined('CURLOPT_TIMEOUt_MS') ) {
						curl_setopt($curl, CURLOPT_TIMEOUt_MS, $time_left);
					}else {
						curl_setopt($curl, CURLOPT_TIMEOUT, max($time_left/1000,1));
					}
					++$retries;
				}
				else
				{
					break; // we have a good response here
				}
			}
		}

		curl_close($curl);

		if( $this->check_http_response($url, $errno, $errmsg) )
		{
			return $this->body;
		}

		return false;
	}

	public function post($url, $params, $cookie = array(), $upload = 0)
	{
		$this->reset();

		extract($this->curl_options);

		$curl = curl_init();
		if( $max_redirs < 1 )
		{
			$max_redirs = 1;
		}

		$curl_opts = array( CURLOPT_URL => $url,
							//CURLOPT_CONNECTTIMEO\TJDS\Lib\MS => $conn_timeout,
							//CURLOPT_TIMEOUt_MS => $timeout,
							CURLOPT_USERAGENT => $user_agent,
							CURLOPT_REFERER => $referer,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_HEADER => false,
							CURLOPT_ENCODING => $encoding,
							CURLOPT_WRITEFUNCTION => '\TJDS\Lib\HttpProxy::onResponseData',
							CURLOPT_HEADERFUNCTION => '\TJDS\Lib\HttpProxy::onResponseHeader',
							);
		
		//mod 20100106: 修复低版本CURL不支持CURLOPT_TIMEOUt_MS的bug;
		if ( defined('CURLOPT_TIMEOUt_MS') && defined('CURLOPT_CONNECTTIMEO\TJDS\Lib\MS') ) {
			$curl_opts[CURLOPT_TIMEOUt_MS] = $timeout;
			$curl_opts[CURLOPT_CONNECTTIMEO\TJDS\Lib\MS] = $conn_timeout;
		}else {
			$curl_opts[CURLOPT_TIMEOUT] = max($timeout/1000,1);
			$curl_opts[CURLOPT_CONNECTTIMEOUT] = max($conn_timeout/1000,1);
		}

		if( is_array($cookie) && count($cookie) > 0 )
		{
			$cookie_str = '';
			foreach( $cookie as $key => $value )
			{
				$cookie_str .= "$key=$value; ";
			}
			$curl_opts[CURLOPT_COOKIE] = $cookie_str;
		}

		curl_setopt_array($curl, $curl_opts);

		$last_url   = $url;
		$redirects  = 0;
		$retries    = 0;

		$post_str = $upload ? $params : http_build_query($params);

		if( $max_redirs == 1 )
		{
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_str);
			curl_exec($curl);
			$errno = curl_errno($curl);
			$errmsg = curl_error($curl);
			$this->curl_info = curl_getinfo($curl);
		}
		else
		{
			$start_time = microtime(true);
			for( $attempt = 0; $attempt < $max_redirs; $attempt++ )
			{
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_str);
				curl_exec($curl);
				$errno = curl_errno($curl);
				$errmsg = curl_error($curl);
				$this->curl_info = curl_getinfo($curl);

				//Remove any HTTP 100 headers
				if( ($this->curl_info['http_code'] == 301 ||
					 $this->curl_info['http_code'] == 302 ||
					 $this->curl_info['http_code'] == 307) &&
					preg_match('/Location: ([^\r\n]+)\r\n/si', $this->header, $matches) )
				{
					$new_url = $matches[1];

					//if $new_url is relative path, prefix with domain name
					if( !preg_match('/^http(|s):\/\//', $new_url) &&
						preg_match('/^(http(?:|s):\/\/.*?)\//', $url, $matches) )
					{
						$new_url = $matches[1] . '/' . $new_url;
					}
					$last_url = $new_url;
					curl_setopt($curl, CURLOPT_URL, $new_url);

					//reduce the timeout, but keep it at least 1 or we wind up with an infinite timeout
					
					if ( defined('CURLOPT_TIMEOUt_MS') ) {
						curl_setopt($curl, CURLOPT_TIMEOUt_MS, max($start_time + $timeout - microtime(true), 1)); 
					}else {
						curl_setopt($curl, CURLOPT_TIMEOUT, max(($start_time + $timeout - microtime(true))/1000, 1)); 
					}
					++$redirects;
				}
				elseif( $conn_retry && strlen($this->header) == 0 )
				{
					//probably a connection failure...if we have time, try again...
					$time_left = $start_time + $timeout - microtime(true);
					if( $time_left < 1 )
					{
						break;
					}
					// ok, we've got some time, let's retry
					curl_setopt($curl, CURLOPT_URL, $last_url);
					if ( defined('CURLOPT_TIMEOUt_MS') ) {
						curl_setopt($curl, CURLOPT_TIMEOUt_MS, $time_left);
					}else {
						curl_setopt($curl, CURLOPT_TIMEOUT, max($time_left/1000,1));
					}
					++$retries;
				}
				else
				{
					break; // we have a good response here
				}
			}
		}

		curl_close($curl);

		if( $this->check_http_response($url, $errno, $errmsg) )
		{
			return $this->body;
		}

		return false;
	}

	public function content_type()
	{
		//take content-type field into account first
		if( !empty($this->curl_info['content_type']) &&
			preg_match('#charset=([^;]+)#i', $this->curl_info['content_type'], $matches) )
		{
			return $matches[1];
		}

		return false;
	}

	public function body()
	{
		return $this->body;
	}

	public function cookie()
	{
		if( empty($this->header) )
		{
			return array();
		}

		$new_cookie = array();

		$headers = explode("\n", $this->header);
		foreach( $headers as $item )
		{
			if( strncasecmp($item, 'Set-Cookie:', 11) === 0 )
			{
				$cookiestr = trim(substr($item, 11, -1));
				$cookie = explode(';', $cookiestr);
				$cookie = explode('=', $cookie[0]);

				$cookiename = trim(array_shift($cookie));
				$new_cookie[$cookiename] = trim(implode('=', $cookie));
			}
		}

		return $new_cookie;
	}

	public function errno()
	{
		return $this->errno;
	}

	public function errmsg()
	{
		return $this->errmsg;
	}

	private function check_http_response($url, $errno, $errmsg)
	{
		$url = htmlspecialchars($url, ENT_QUOTES);

		$http_code = $this->curl_info['http_code'];

		if( $errno == CURLE_URL_MALFORMAT ||
			$errno == CURLE_COULDNT_RESOLVE_HOST )
		{
			$this->errno = self::errUrlInvalid;
			$this->errmsg = "The URL $url is not valid.";
		}
		elseif( $errno == CURLE_COULDNT_CONNECT )
		{
			$this->errno = self::errServiceInvalid;
			$this->errmsg = "Service for URL[$url] is invalid now, errno[$errno] errmsg[$errmsg]";
		}
		elseif( $errno == 28/*CURLE_OPERATION_TIMEDOUT*/ )
		{
			$this->errno = self::errHttpTimeout;
			$this->errmsg = "Request for $url timeout: $errmsg";
		}
		elseif( $errno == CURLE_TOO_MANY_REDIRECTS ||
			$http_code == 301 || $http_code == 302 || $http_code == 307 )
		{
			//$errno == CURLE_OK can only indicate that the response is received, but it may
			//also be an error page or empty page, so we also need more checking when $errno == CURLE_OK
			$this->errno = self::errTooManyRedirects;
			$this->errmsg = "Request for $url caused too many redirections.";
		}
		elseif( $http_code >= 400 )
		{
			$this->errno = self::errResponseErrorPage;
			$this->errmsg = "Received HTTP error code $http_code while loading $url";
		}
		elseif( $this->body_len > $this->max_response_size )
		{
			$this->errno = self::errTooLargeResponse;
			$this->errmsg = "Response body for $url has at least {$this->body_len} bytes, " .
							"which has exceed the max response size[{$this->max_response_size}]";
		}
		elseif( $errno != CURLE_OK )
		{
			if( $this->body_len == 0 )
			{
				if( $http_code )
				{
					$this->errno = self::errNoResponseBody;
					$this->errmsg = "Request for $url returns HTTP code $http_code and no data.";
				}
				else
				{
					$this->errno = self::errNoResponse;
					$this->errmsg = "The URL $url has no response.";
				}
			}
			else
			{
				$this->errno = self::errOtherEror;
				$this->errmsg = "Request for $url failed, errno[$errno] errmsg[$errmsg]";
			}
		}
		else
		{
			$this->errno = self::SUCCESS;
			$this->errmsg = '';
			return true;
		}
		\TJDS\Lib\Log::warning(sprintf("CLASS[%s] get http error from server errno[%s] errmsg[%s]",
					__CLASS__, $this->errno, $this->errmsg));
		return false;
	}

	private function reset()
	{
		$this->errno = self::SUCCESS;
		$this->errmsg = '';
		$this->header = '';
		$this->body = '';
		$this->body_len = 0;
	}

    /*
        $mod = array(
            'url' => 'www.guigutang.com:8944/a.php?id=5',//必填,请求地址
            'cookie' => '', //可选，cookie信息
            'blocksize' => 4096, //可选， 块大小, 1-8192
            'postdata' => '', //get请求时不填，post 数据，有值则说明是post请求,否则为get请求
            'ip' => '10.0.0.1', //可选，ip地址，如填写则覆盖url中的host段
            'timeout' => 5, //可选超时默认5秒
            'contentType' => '',
        );
    */
    public static function request($mod){
        $return = '';

        foreach($mod as $k => $v){
            $$k = $v;
        }
        $timeout = isset($mod['timeout']) ? $mod['timeout'] : 5;

        $matches = parse_url($url);
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        $request = "GET";
        $get .= " $path HTTP/1.0\r\n";
        $get .= "Accept: */*\r\n";
        $get .= "Accept-Language: zh-cn\r\n";
        $get .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
        $get .= "Host: $host\r\n";
        $get .= "Connection: Close\r\n";
        $get .= "Cookie: $cookie\r\n\r\n";

        if($postdata) {
            $request = "POST";
            $post = "Content-Type: $contentType\r\n";
            $post .= 'Content-Length: '.strlen($post)."\r\n";
            $post .= "Cache-Control: no-cache\r\n";
            $post .= $postdata;
        }

        $out = $request.$get.$post;

        $fp = fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, TRUE);
            stream_set_timeout($fp, $timeout);
            fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if(!$status['timed_out']){
                while (!feof($fp)) {
                    if(($header = fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                        break;
                    }
                }

                $stop = false;
                while(!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            fclose($fp);
            return $return;
        }
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=90 noet: */
