<?php

include_once(__DIR__ . '/simple_html_dom.php');

class ThrottledDomReader
{
	
	const SLEUTH_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
	const NUM_SEC_DELAY_BEFORE_REQUEST = 0;
	
	protected $debugLog;
	const DEBUG_ON = FALSE;
	
	public function __construct()
	{
		if (ThrottledDomReader::DEBUG_ON)
		{
			$this->debugLog = new Logger(Logger::LEVEL_DEBUG);
		}
		else
		{
			$this->debugLog = new Logger(Logger::LEVEL_OFF);
		}
	}
	
	public function getDom($url)
	{
		#Throttle
		if (ThrottledDomReader::NUM_SEC_DELAY_BEFORE_REQUEST)
		{
			sleep(ThrottledDomReader::NUM_SEC_DELAY_BEFORE_REQUEST);
		}
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, ThrottledDomReader::SLEUTH_AGENT);
		$curl_scraped_page = curl_exec($ch);
		
		$this->debugLog->log("Curl scraped page is " . strlen($curl_scraped_page) . " bytes long.\n");
		
		if ($curl_scraped_page === FALSE)
		{
			die("Could not read URL '$url' " . curl_error($ch) . "\n");
		}
		else
		{
			$html_source = $curl_scraped_page;
			$html_dom = new simple_html_dom();
			$html_dom->load($html_source);
			return $html_dom;
		}
	}
}

?>