<?php

class Logger
{
	const LEVEL_MIN = 1;
	const LEVEL_MAX = 9;
	
	const LEVEL_TRACE = 1;
	const LEVEL_DEBUG = 2;
	const LEVEL_INFO = 3;
	const LEVEL_WARN = 4;
	const LEVEL_ERROR = 5;
	const LEVEL_FATAL = 6;
	const LEVEL_ALL = 7;
	const LEVEL_OFF = 8;
	const LEVEL_STATUS = 9;
	
	const FILENAME_DEBUG = 'sharedcoin_debug.log';
	const FILENAME_TRACE = 'sharedcion_debug.log';
	
	private $FILENAME_INFO; #determined in constructor
	
	private $level;
	
	private $currentFunction = '';
	
	function __construct($logLevel)
	{
		if ($logLevel < Logger::LEVEL_MIN)
		{
			die("Logger class instantiated with log level too low.");	
		}	
		elseif($logLevel > Logger::LEVEL_MAX)
		{
			die("Logger class instantiated with log level too high.");	
		}
		
		$this->level = $logLevel;
		
		if ($logLevel == Logger::LEVEL_INFO or $logLevel == Logger::LEVEL_ALL)
		{
			//set custom filename for info
			date_default_timezone_set('America/New_York');
			$timeStamp = date("Y-m-d G-i-s");
			$this->FILENAME_INFO = "scdump_$timeStamp.log";	
		}
	}	
	
	public function log($message)
	{
		if ($this->level == Logger::LEVEL_ALL)
		{
			$this->log_all($message);
		}	
		elseif ($this->level == Logger::LEVEL_TRACE)
		{
			$this->log_trace($message);
		}
		elseif ($this->level == Logger::LEVEL_DEBUG)
		{
			#$this->log_debug($message);
			$this->log_status($message);
		}
		elseif ($this->level == Logger::LEVEL_INFO)
		{
			$this->log_info($message);
		}
		elseif ($this->level == Logger::LEVEL_WARN)
		{
			$this->log_warn($message);
		}
		elseif ($this->level == Logger::LEVEL_ERROR)
		{
			$this->log_error($message);
		}
		elseif ($this->level == Logger::LEVEL_FATAL)
		{
			$this->log_fatal($message);
		}
		elseif ($this->level == Logger::LEVEL_OFF)
		{
			//do nothing :3
		}
		elseif ($this->level == Logger::LEVEL_STATUS)
		{
			$this->log_status($message);	
		}
	}
	
	public function log_var($var)
	{
		$varAsString = Logger::var_dump_noprint($var);
		$this->log($varAsString);
	}
	
	public static function static_log($logLevel, $message)
	{
		$logger = new Logger($logLevel);
		$logger->log($message);	
	}
	
	private function log_debug($message)
	{
		date_default_timezone_set('America/New_York');
		$timeStamp = date("Y/m/d G:i:s");
		$logFile = Logger::FILENAME_DEBUG;
		$fh = fopen($logFile, 'a') or die("can't open file");
		
		fwrite($fh, "DEBUG ($timeStamp): $message");
		fclose($fh);
	}
	
	private function log_all($message)
	{
		$this->log_debug($message);
		$this->log_trace($message);
		$this->log_info($message);
		$this->log_warn($message);
		$this->log_error($message);
		$this->log_fatal($message);
	}
	
	private function log_trace($message)
	{
		date_default_timezone_set('America/New_York');
		$timeStamp = date("Y/m/d G:i:s");
		$logFile = Logger::FILENAME_TRACE;
		$fh = fopen($logFile, 'a') or die("can't open file");
		fwrite($fh, "($timeStamp) $message");
		fclose($fh);
	}
	
	private function log_info($message)
	{
		#date_default_timezone_set('America/New_York');
		#$timeStamp = date("Y/m/d G:i:s");
		$logFile = $this->FILENAME_INFO;
		$fh = fopen($logFile, 'a') or die("can't open file");
		#fwrite($fh, "($timeStamp) $message");
		fwrite($fh, "$message");
		fclose($fh);

	}
	
	private function log_warn($message)
	{
		date_default_timezone_set('America/New_York');
		$timeStamp = date("Y/m/d G:i:s");
		print "($timeStamp) WARNING: $message";
	}
	
	private function log_error($message)
	{
		
	}
	
	private function log_fatal($message)
	{
		
	}
	
	private function log_status($message)
	{	
		date_default_timezone_set('America/New_York');
		$timeStamp = date("Y/m/d G:i:s");
		print "($timeStamp) STATUS: $message";
	}
	
	private static function var_dump_noprint($var)
	{
		ob_start();
		var_dump($var);
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}
}
?>