<?php

class ThrottledJSONReader
{
	
	const SLEUTH_AGENT = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36';
	const NUM_SEC_DELAY_BEFORE_REQUEST = 0; 		# How long to wait between all requests
	const NUM_SEC_DELAY_AFTER_FAILED_REQUEST = 1; 	# How long to wait between failed requests in 
													# addition to NUM_SEC_DELAY_BEFORE_REQUEST
	const ENABLE_LINEAR_BACKOFF_AFTER_FAILED_REQUEST = TRUE; # 1 seconds, then 2, etc. 
													# overrides NUM_SEC_DELAY_AFTER_FAILED_REQUEST
	
	const MAX_NUM_REATTEMPTS = 20;
	
	protected $debugLog;
	const DEBUG_ON = TRUE;
	
	public function __construct()
	{
		if (ThrottledJSONReader::DEBUG_ON)
		{
			$this->debugLog = new Logger(Logger::LEVEL_DEBUG);
		}
		else
		{
			$this->debugLog = new Logger(Logger::LEVEL_OFF);
		}
	}
	
	public function getJSON($url)
	{
		$backoff = 1;
		#keep trying to decode page until it works or we die()
		for ($i = 0; $i < ThrottledJSONReader::MAX_NUM_REATTEMPTS; $i++)
		{
			$jsonString = $this->fetchPage($url);
			$json = json_decode($jsonString, TRUE);
			if (!is_null($json))
			{
				#decoded successfully
				$backoff = 1;
				return $json;
			}
			else
			{
				#not decoded, consider pausing for some time before trying again
				#TODO: handle errors such as "Chain head not found"
				$this->debugLog->log("Could not decode JSON, will try to fetch again until MAX_NUM_REATTEMTPS is hit.\n");
				if (ENABLE_LINEAR_BACKOFF_AFTER_FAILED_REQUEST)
				{
					$this->debugLog->log("Sleeping for $backoff seconds.\n");
					sleep($backoff);
					$backoff++;
				}
				elseif (ThrottledJSONReader::NUM_SEC_DELAY_AFTER_FAILED_REQUEST)
				{
					$this->debugLog->log("Sleeping for " . ThrottledJSONReader::NUM_SEC_DELAY_AFTER_FAILED_REQUEST . " seconds.\n");
					sleep(ThrottledJSONReader::NUM_SEC_DELAY_AFTER_FAILED_REQUEST);
				}
			}
		}
		
		die("Could not decode JSON for acquired page in ThrottledJSONReader.php after " . ThrottledJSONReader::MAX_NUM_REATTEMPTS . " attempts.\n");
	}
	
	private function fetchPage($url)
	{
		$this->debugLog->log("URL: $url\n");
		#Throttle
		if (ThrottledJSONReader::NUM_SEC_DELAY_BEFORE_REQUEST)
		{
			$this->debugLog->log("Sleeping for " . ThrottledJSONReader::NUM_SEC_DELAY_BEFORE_REQUEST . " seconds.\n");
			sleep(ThrottledJSONReader::NUM_SEC_DELAY_BEFORE_REQUEST);
		}
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, ThrottledJSONReader::SLEUTH_AGENT);
		$curl_scraped_page = curl_exec($ch);
		
		$this->debugLog->log("Curl scraped page is " . strlen($curl_scraped_page) . " bytes long.\n");
		//$this->debugLog->log("Contents: '$curl_scraped_page'\n");
		
		if ($curl_scraped_page === FALSE)
		{
			return NULL;
		}
		else
		{
			return $curl_scraped_page;
		}
	}
}

?>