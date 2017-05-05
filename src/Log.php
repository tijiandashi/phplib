<?php
/***************************************************************************
*
* Copyright (c) 2016 tijiandashi.com, Inc. All Rights Reserved
*
***************************************************************************/



/**
* file Log.php
* author liujun(liujun@tijiandashi.com)
* date 2017-03-04 13:30
* brief: 
*
**/

namespace TJDS\Lib;

class Log
{
	const LOG_LEVEL_NONE    = 0x00;
	const LOG_LEVEL_FATAL   = 0x01;
	const LOG_LEVEL_WARNING = 0x02;
	const LOG_LEVEL_NOTICE  = 0x04;
	const LOG_LEVEL_TRACE   = 0x08;
	const LOG_LEVEL_DEBUG   = 0x10;
	const LOG_LEVEL_ALL     = 0xFF;


	public static $arrLogLevels = array(
		self::LOG_LEVEL_NONE    => 'NONE',
		self::LOG_LEVEL_FATAL   => 'FATAL',
		self::LOG_LEVEL_WARNING => 'WARNING',
		self::LOG_LEVEL_NOTICE  => 'NOTICE',
		self::LOG_LEVEL_TRACE	=> 'TRACE',
		self::LOG_LEVEL_DEBUG   => 'DEBUG',
		self::LOG_LEVEL_ALL     => 'ALL',
	);

	protected $intLevel;
	protected $strLogFile;
	protected $arrSelfLogFiles;
	protected $intLogId;
	protected $intMaxFileSize;
	protected $addNotice = '';

	private static $instance = null;

	private function __construct($moduleName = 'default')
	{
		$arrLogConfig = \WApp\Conf\Log::$config;
		$arrLogConfig['strLogFile'] = APP_PATH."/../log/".$moduleName.".log";
		$this->intLevel         = intval($arrLogConfig['intLevel']);
		$this->strLogFile		= $arrLogConfig['strLogFile'];
		$this->arrSelfLogFiles  = $arrLogConfig['arrSelfLogFiles'];
		$this->intLogId		= 0;
		$this->intMaxFileSize	= $arrLogConfig['intMaxFileSize'];
	}

	public static function init($arrLogConfig) {
		if(self::$instance == null) {
			self::$instance = new \TJDS\Lib\Log($arrLogConfig);
		}
	}
	/*
	用新的配置重新创建一个log实例
	 */
	public static function newInstance($arrLogConfig) {
		if(self::$instance !== null) {
			self::$instance =null;
		}
		self::$instance = new \TJDS\Lib\Log($arrLogConfig);
	}

	public static function getInstance()
	{
		if( self::$instance === null )
		{
			self::$instance = new \TJDS\Lib\Log();
		}

		return self::$instance;
	}

	public function writeLog($intLevel, $str, $errno = 0, $arrArgs = null, $depth = 0)
	{
		if( !($this->intLevel & $intLevel) || !isset(self::$arrLogLevels[$intLevel]) )
		{
			return;
		}

		$strLevel = self::$arrLogLevels[$intLevel];

		$strLogFile = $this->strLogFile;
		if( ($intLevel & self::LOG_LEVEL_WARNING) || ($intLevel & self::LOG_LEVEL_FATAL) )
		{
			$strLogFile .= '.wf';
		}

		$trace = debug_backtrace();
		if( $depth >= count($trace) )
		{
			$depth = count($trace) - 1;
		}
		$file = basename($trace[$depth]['file']);
		$line = $trace[$depth]['line'];

		$strArgs = '';
		if( is_array($arrArgs) && count($arrArgs) > 0 )
		{
			foreach( $arrArgs as $key => $value )
			{
				$strArgs .= "{$key}[$value]";
			}
		}

		if(isset($this->addNotice{2})){
			$strArgs .= $this->addNotice;
		}

		$str = sprintf( "%s: %s [%s:%d] errno[%d] ip[%s] logId[%u] uri[%s] refer[%s] cookie[%s] %s %s\n",
			$strLevel,
			date('m-d H:i:s:', time()),
			$file, $line, $errno,
			self::getClientIP(),
			\TJDS\Lib\Log::getLogID(),
			isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
			isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
			isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '',
			$strArgs,
			$str);

		if($this->intMaxFileSize > 0)
		{
			clearstatcache();
			$arrFileStats = stat($strLogFile);
			if( is_array($arrFileStats) && floatval($arrFileStats['size']) > $this->intMaxFileSize )
			{
                $strLogFile .= date('YmdH');
			}
		}
		return file_put_contents($strLogFile, $str, FILE_APPEND);
	}

	public function writeSelfLog($strKey, $str, $arrArgs = null)
	{
		if( isset($this->arrSelfLogFiles[$strKey]) )
		{
			$strLogFile = $this->arrSelfLogFiles[$strKey];
		}
		else
		{
			return;
		}

		$strArgs = '';
		if( is_array($arrArgs) && count($arrArgs) > 0 )
		{
			foreach( $arrArgs as $key => $value )
			{
				$strArgs .= "{$key}[$value] ";
			}
		}

		$str = sprintf( "%s: %s ip[%s] logId[%u] uri[%s] %s%s\n",
			$strKey,
			date('m-d H:i:s:', time()),
			self::getClientIP(),
			$this->intLogId,
			isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
			$strArgs, $str);

		if($this->intMaxFileSize > 0)
		{
			clearstatcache();
			$arrFileStats = stat($strLogFile);
			if( is_array($arrFileStats) && floatval($arrFileStats['size']) > $this->intMaxFileSize )
			{
                $strLogFile .= date('YmdH');
				//unlink($strLogFile);
			}
		}
		return file_put_contents($strLogFile, $str, FILE_APPEND);
	}

	public static function selflog($strKey, $str, $arrArgs = null)
	{
		$log = \TJDS\Lib\Log::getInstance();
		return $log->writeSelfLog($strKey, $str, $arrArgs);
	}

	public static function debug($str, $errno = 0, $arrArgs = null, $depth = 0)
	{
		$log = \TJDS\Lib\Log::getInstance();
		return $log->writeLog(self::LOG_LEVEL_DEBUG, $str, $errno, $arrArgs, $depth + 1);
	}

	public static function trace($str, $errno = 0, $arrArgs = null, $depth = 0)
	{
		$log = \TJDS\Lib\Log::getInstance();
		return $log->writeLog(self::LOG_LEVEL_TRACE, $str, $errno, $arrArgs, $depth + 1);
    }

	public static function notice($str, $errno = 0, $arrArgs = null, $depth = 0)
	{
		$log = \TJDS\Lib\Log::getInstance();
		return $log->writeLog(self::LOG_LEVEL_NOTICE, $str, $errno, $arrArgs, $depth + 1);
	}

    public static function addNotice($key, $value){
		$log = \TJDS\Lib\Log::getInstance();
        $info = is_array($value) ? var_export($value, true) : $value;
        $log->addNotice .= " {$key}[$info]";
    }

	public static function warning($str, $errno = 0, $arrArgs = null, $depth = 0)
	{
		$log = \TJDS\Lib\Log::getInstance();
		return $log->writeLog(self::LOG_LEVEL_WARNING, $str, $errno, $arrArgs, $depth + 1);
    }

	public static function fatal($str, $errno = 0, $arrArgs = null, $depth = 0)
	{
		$log = \TJDS\Lib\Log::getInstance();
		return $log->writeLog(self::LOG_LEVEL_FATAL, $str, $errno, $arrArgs, $depth + 1);
	}

	public static function setLogId($intLogId)
	{
		\TJDS\Lib\Log::getInstance()->intLogId = $intLogId;
	}

	public static function getClientIP()
	{
        $uip = '';
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $uip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $uip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $uip = $_SERVER['REMOTE_ADDR'];
        }
        return $uip;
	}

    static function getLogID(){
		//HTTP_X_BD_LOGID64
		if ( isset($_SERVER['HTTP_X_BD_LOGID']) ) {
			return intval($_SERVER['HTTP_X_BD_LOGID']);
		} else {
			$arr = gettimeofday();
			return ((($arr['sec']*100000 + $arr['usec']/10) & 0x7FFFFFFF) | 0x80000000);
		}
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=90 noet: */
