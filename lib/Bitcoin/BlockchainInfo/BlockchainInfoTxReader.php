<?php

include_once(__DIR__ . '/../../Web/simple_html_dom.php');
include_once(__DIR__ . '/../TransactionQuantity.php');
include_once(__DIR__ . '/../Transaction.php');
include_once(__DIR__ . '/../../Util/Logger.php');
include_once(__DIR__ . '/../../Web/ThrottledDomReader.php');
include_once(__DIR__ . '/../../Web/ThrottledJSONReader.php');

class BlockchainInfoTxReader
{
	public $html_dom = NULL;
	public $json = NULL;
	
	public $transaction = NULL;
	
	protected $debugLog; //init'd in constructor
	const DEBUG_ON = TRUE;
	
	const BLOCKCHAIN_INFO_TX_URL_PREFIX = 'https://blockchain.info/tx/';
	const BLOCKCHAIN_INFO_TX_URL_SUFFIX = '?show_adv=true';
	
	const BLOCKCHAIN_INFO_TX_JSON_URL_PREFIX = 'https://blockchain.info/tx-index/';
	const BLOCKCHAIN_INFO_TX_JSON_URL_SUFFIX = '?format=json';
	
	const BLOCKCHAIN_INFO_TX_JSON_API_KEY = ''; # leave me blank or set me
	
	#If true uses ThrottledDomReader to scrape DOM, if false uses ThrottledJSONReader to decode from JSON API
	const USE_DOM_READER = FALSE;
	
	#When TRUE, if there are two or more inputs with the same address, they are combined into a single
	# TransactionQuantity. Same for outputs.
	#const CONDENSE_FOR_DUPLICATE_ADDRESSES = TRUE;   #<-- TODO: not yet implemented
	
	const SHARED_COIN_FEES = '0.0001';
	
	private $domReader; //init'd in constructor OR
	private $jsonReader; //init'd in constructor
	
	const BLOCKCHAIN_INFO_API_TX_HASH_IDENTIFIER = 'hash';
	const BLOCKCHAIN_INFO_API_TX_SIZE_IDENTIFIER = 'size';
	const BLOCKCHAIN_INFO_API_TX_RECEIVED_TIME_IDENTIFIER = 'time'; #UNIX TIME
	const BLOCKCHAIN_INFO_API_TX_RELAYED_BY_IDENTIFIER = 'relayed_by';
	const BLOCKCHAIN_INFO_API_TX_ESTIMATED_AMOUNT_TRANSACTED_IDENTIFIER = ''; #TODO: can't find in API yet
	const BLOCKCHAIN_INFO_API_TX_INPUTS_IDENTIFIER = 'inputs';
	const BLOCKCHAIN_INFO_API_TX_OUTPUTS_IDENTIFIER = 'out';
	const BLOCKCHAIN_INFO_API_TX_INPUT_IDENTIFIER = 'prev_out';
	const BLOCKCHAIN_INFO_API_TX_INPUT_ADDRESS_IDENTIFIER = 'addr';
	const BLOCKCHAIN_INFO_API_TX_OUTPUT_ADDRESS_IDENTIFIER = 'addr';
	const BLOCKCHAIN_INFO_API_TX_INPUT_AMOUNT = 'value';
	const BLOCKCHAIN_INFO_API_TX_OUTPUT_AMOUNT = 'value';
	
	public function __construct($url_optional, $file_optional, $string_optional, $hash_optional)
	{
		if (BlockchainInfoTxReader::DEBUG_ON)
		{
			$this->debugLog = new Logger(Logger::LEVEL_DEBUG);
		}
		else
		{
			$this->debugLog = new Logger(Logger::LEVEL_OFF);
		}
		
		if (BlockchainInfoTxReader::USE_DOM_READER)
		{
			#initialize Throttled DOM Reader for access Blockchain.info via HTTP request scraping
			$this->domReader = new ThrottledDomReader();
		}
		else
		{
			$this->jsonReader = new ThrottledJSONReader();
		}
		
		if ($url_optional != '')
		{
			if (BlockchainInfoTxReader::USE_DOM_READER)
			{
				$this->html_dom = $this->domReader->getDom($url_optional);
			}
			else
			{
				$this->json = $this->jsonReader->getJSON($url_optional);
			}
		}
		elseif ($file_optional != '')
		{
			if (file_exists($file_optional))
			{
				if (BlockchainInfoTxReader::USE_DOM_READER)
				{
					$html_source = file_get_contents($file_optional);
					$this->html_dom = new simple_html_dom();
					$this->html_dom->load($html_source);
				}
				else
				{
					$json_source = file_get_contents($file_optional);
					$this->json = json_decode($json_source, TRUE);
				}
			}
			else
			{
				die ("Cannot access file '$file_optional'\n");
			}
		}
		elseif ($string_optional != '')
		{
			if (BlockchainInfoTxReader::USE_DOM_READER)
			{
				$html_source = $string_optional;
				$this->html_dom = new simple_html_dom();
				$this->html_dom->load($html_source);
			}
			else
			{
				$this->json = json_decode($string_optional, TRUE);
			}
		}
		elseif ($hash_optional != '')
		{
			if (BlockchainInfoTxReader::USE_DOM_READER)
			{
				$url = BlockchainInfoTxReader::generate_blockchain_info_tx_lookup_url($hash_optional);
				$this->html_dom = $this->domReader->getDom($url);
			}
			else
			{
				$url = BlockchainInfoTxReader::enerate_blockchain_info_tx_json_lookup_url($hash_optional);
				$this->debugLog->log("BlockchainInfoTxReader:: Fetching JSON for url: $url\n");
				$this->json = $this->jsonReader->getJSON($url);
			}
		}
		
		if (BlockchainInfoTxReader::USE_DOM_READER)
		{
			if (!is_null($this->html_dom))
			{
				$this->setTransactionFromDOM();
			}
		}
		else
		{
			if (!is_null($this->json))
			{
				$this->setTransactionFromJSON();
			}
		}
	}
	
	private function setTransactionFromDOM()
	{
		$hash = $this->getHashFromDOM();
		$byteSize = $this->getSizeInBytesFromDOM();
		$receivedTime = $this->getReceivedTimeFromDOM();
		$relayedByIP = $this->getRelayedByFromDOM();
		$estimatedAmountTransacted = $this->getEstimatedAmountFromDOM();
		
		$this->debugLog->log("hash: $hash byteSize: $byteSize receivedTime: $receivedTime relayed by: $relayedByIP ".
			"estimatedAmountTransacted: $estimatedAmountTransacted\n");
		
		$inputAddresses = $this->getInputAddressesFromDOM();
		$outputAddresses = $this->getOutputAddressesFromDOM();
		$inputValues = $this->getInputValuesFromDOM();
		$outputValues = $this->getOutputValuesFromDOM();
		
		$this->debugLog->log("input addresses: " . count($inputAddresses) . ' output addresses: ' . count($outputAddresses) . 
			' input values: ' . count($inputValues) . ' output values: ' . count($outputValues) . "\n");
		if (count($inputAddresses) != count($inputValues))
		{
			die ("Death. Number of input addresses does not match number of input values.");
		}
		if (count($outputAddresses) != count($outputValues))
		{
			die ("Death. Number of output addresses does not match number of output values.");
		}
		
		$quantityArray = array();
		for ($i = 0; $i < count($inputAddresses); $i++)
		{
			$tq = new TransactionQuantity(TransactionQuantity::INPUT, $inputAddresses[$i], $inputValues[$i], array());
			array_push($quantityArray, $tq);
		}
		for ($i = 0; $i < count($outputAddresses); $i++)
		{
			$tq = new TransactionQuantity(TransactionQuantity::OUTPUT, $outputAddresses[$i], $outputValues[$i], array());
			array_push($quantityArray, $tq);
		}
		
		$inputSum = Transaction::getSumOfArray($inputValues);
		$outputSum = Transaction::getSumOfArray($outputValues);
		$difference = bcsub(''. $inputSum, ''. $outputSum, 8);
		$numFeesToCreate = bcdiv($difference, BlockchainInfoTxReader::SHARED_COIN_FEES, 8);
		
		for ($i = 0; $i < $numFeesToCreate; $i++)
		{
			$fee = new TransactionQuantity(TransactionQuantity::FEE, "1FEE$i", BlockchainInfoTxReader::SHARED_COIN_FEES, array());
			array_push($quantityArray, $fee);
		}
		
		#public function __construct($hash, $byteSize, $receivedTime, $relayedByIP, $totalInput,
		#$totalOutput, $fees, $estimatedAmountTransacted, $quantityArr)
		$this->transaction = new Transaction($hash, $byteSize, $receivedTime, $relayedByIP,
			Transaction::BLANK_VALUE, Transaction::BLANK_VALUE, Transaction::BLANK_VALUE,
			$estimatedAmountTransacted, $quantityArray);
	}
	
	private static function isValidBitcoinAddress($str)
	{
		#A Bitcoin address, or simply address, is an identifier of 27-34 alphanumeric characters, 
		# beginning with the number 1 or 3, that represents a possible destination for a Bitcoin payment.
		
		if (strlen($str) < 27 or strlen($str) > 34)
		{
			return FALSE;
		}
		if (substr($str, 0, 1) != '1' and substr($str, 0 , 1) != '3')
		{
			return FALSE;
		}
		return TRUE;
	}
	
	private function getSizeInBytesFromDOM()
	{
		$nextIsSize = FALSE;
		foreach ($this->html_dom->find('div[class=row-fluid]') as $divElement)
		{
			foreach ($this->html_dom->find('td') as $col)
			{
				if ($nextIsSize)
				{
					$sizeAsString = $col->plaintext;
					preg_match('/(\d+)/', $sizeAsString, $matches);
					$size = $matches[1];
					return $size;
				}
				elseif ($col->plaintext == 'Size')
				{
					$nextIsSize = TRUE;
				}
			}
		}
	}
	
	private function getReceivedTimeFromDOM()
	{
		$nextIsTime = FALSE;
		foreach ($this->html_dom->find('div[class=row-fluid]') as $divElement)
		{
			foreach ($this->html_dom->find('td') as $col)
			{
				if ($nextIsTime)
				{
					$time = $col->plaintext;
					return trim($time);
				}
				elseif ($col->plaintext == 'Received Time')
				{
					$nextIsTime = TRUE;
				}
			}
		}
	}
	
	private function getHashFromDOM()
	{
		foreach ($this->html_dom->find('a[class=hash-link]') as $hashLinkElement)
		{	
			return $hashLinkElement->plaintext;
		}
	}
	
	private function getRelayedByFromDOM()
	{
		$nextIsStr = FALSE;
		foreach ($this->html_dom->find('div[class=row-fluid]') as $divElement)
		{
			foreach ($this->html_dom->find('td') as $col)
			{
				if ($nextIsStr)
				{
					$links = $col->find('a');
					$firstLink = $links[0];
					return $firstLink->plaintext;
				}
				elseif (substr($col->plaintext,0,13) == 'Relayed by IP')
				{
					$nextIsStr = TRUE;
				}
			}
		}
	}
	
	private function getEstimatedAmountFromDOM()
	{
		$nextIsEstimated = FALSE;
		foreach ($this->html_dom->find('div[class=span6]') as $divElement)
		{
			foreach ($this->html_dom->find('td') as $col)
			{
				if ($nextIsEstimated)
				{
					$estimatedAsString = $col->plaintext;
					preg_match('/([0123456789.]+)/', $estimatedAsString, $matches);
					$estimated = $matches[1];
					return $estimated;
				}
				elseif ($col->plaintext == 'Estimated BTC Transacted')
				{
					$nextIsEstimated = TRUE;
				}
			}
		}
	}
	
	private function getInputAddressesFromDOM()
	{
		$inputAddresses = array();
		foreach ($this->html_dom->find('div[class=txdiv]') as $transactionElement)
		{
			foreach($transactionElement->find('td[class=txtd hidden-phone]') as $inputColumnElement)
			{
				foreach($inputColumnElement->find('a') as $linkElement)
				{
					$addressCandidate = $linkElement->plaintext;
					if (BlockchainInfoTxReader::isValidBitcoinAddress($addressCandidate))
					{
						#print "DEBUG: Input: $addressCandidate\n";
						array_push($inputAddresses, $addressCandidate);
					}
				}
				foreach($inputColumnElement->find('span') as $spanElement)
				{
					$amountAsString = $spanElement->plaintext;
					preg_match('/([0123456789.]+)/', $amountAsString, $matches);
					$amount = $matches[1];
				}
				
			}
		}
		
		return $inputAddresses;
	}
	
	private function getInputValuesFromDOM()
	{
		$inputValues = array();
		foreach ($this->html_dom->find('div[class=txdiv]') as $transactionElement)
		{
			foreach($transactionElement->find('td[class=txtd hidden-phone]') as $inputColumnElement)
			{
				foreach($inputColumnElement->find('span') as $spanElement)
				{
					$amountAsString = $spanElement->plaintext;
					preg_match('/([0123456789.]+)/', $amountAsString, $matches);
					$amount = $matches[1];
					array_push($inputValues, $amount);
				}
			}
		}
		
		return $inputValues;
	}
	
	private function getOutputAddressesFromDOM()
	{
		$outputAddresses = array();
		foreach ($this->html_dom->find('div[class=txdiv]') as $transactionElement)
		{
			foreach($transactionElement->find('td[class=txtd]') as $outputColumnElement)
			{
 				if ($outputColumnElement->class == 'txtd') #do not repeat inputs
				{
					foreach($outputColumnElement->find('a') as $linkElement)
					{
						$addressCandidate = $linkElement->plaintext;
						if (BlockchainInfoTxReader::isValidBitcoinAddress($addressCandidate))
						{
							#print "DEBUG: Output: $addressCandidate\n";
							array_push($outputAddresses, $addressCandidate);
						}
					}
				}
			}
		}
		return $outputAddresses;
	}
	
	private function getOutputValuesFromDOM()
	{
		$outputValues = array();
		foreach ($this->html_dom->find('div[class=txdiv]') as $transactionElement)
		{
			foreach($transactionElement->find('td[class=txtd]') as $outputColumnElement)
			{
 				if ($outputColumnElement->class == 'txtd') #do not repeat inputs
				{
					foreach($outputColumnElement->find('span') as $spanElement)
					{
						#this has two spans, find() will discover both inner and outer
						if ($spanElement->class != 'pull-right hidden-phone')
						{
							$amountAsString = $spanElement->plaintext;
							preg_match('/([0123456789.]+)/', $amountAsString, $matches);
							$amount = $matches[1];
							array_push($outputValues, $amount);
						}
					}
				}
			}
		}
		
		return $outputValues;
	}
	
	private function setTransactionFromJSON()
	{		
		$hash = $this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_HASH_IDENTIFIER];
		$byteSize = $this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_SIZE_IDENTIFIER];
		$receivedTime = $this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_RECEIVED_TIME_IDENTIFIER]; #TODO: convert from UNIX time
		$relayedByIP = $this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_RELAYED_BY_IDENTIFIER];
		$estimatedAmountTransacted = 'TODO_not_implemented';
		
		$this->debugLog->log("hash: $hash byteSize: $byteSize receivedTime: $receivedTime relayed by: $relayedByIP ".
			"estimatedAmountTransacted: $estimatedAmountTransacted\n");
		
		$inputAddresses = $this->getInputAddressesFromJSON();
		$outputAddresses = $this->getOutputAddressesFromJSON();
		
		$inputValues = $this->getInputValuesFromJSON();
		$outputValues = $this->getOutputValuesFromJSON();
		
		$this->debugLog->log("input addresses: " . count($inputAddresses) . ' output addresses: ' . count($outputAddresses) . 
			' input values: ' . count($inputValues) . ' output values: ' . count($outputValues) . "\n");
		
		if (count($inputAddresses) != count($inputValues))
		{
			die ("Death. Number of input addresses does not match number of input values.");
		}
		if (count($outputAddresses) != count($outputValues))
		{
			die ("Death. Number of output addresses does not match number of output values.");
		}
		
		$quantityArray = array();
		for ($i = 0; $i < count($inputAddresses); $i++)
		{
			$tq = new TransactionQuantity(TransactionQuantity::INPUT, $inputAddresses[$i], $inputValues[$i], array());
			array_push($quantityArray, $tq);
		}
		for ($i = 0; $i < count($outputAddresses); $i++)
		{
			$tq = new TransactionQuantity(TransactionQuantity::OUTPUT, $outputAddresses[$i], $outputValues[$i], array());
			array_push($quantityArray, $tq);
		}
		
		$inputSum = Transaction::getSumOfArray($inputValues);
		$outputSum = Transaction::getSumOfArray($outputValues);
		$difference = bcsub(''. $inputSum, ''. $outputSum, 8);
		$numFeesToCreate = bcdiv($difference, BlockchainInfoTxReader::SHARED_COIN_FEES, 8);
		
		for ($i = 0; $i < $numFeesToCreate; $i++)
		{
			$fee = new TransactionQuantity(TransactionQuantity::FEE, "1FEE$i", BlockchainInfoTxReader::SHARED_COIN_FEES, array());
			array_push($quantityArray, $fee);
		}
		
		#public function __construct($hash, $byteSize, $receivedTime, $relayedByIP, $totalInput,
		#$totalOutput, $fees, $estimatedAmountTransacted, $quantityArr)
		$this->transaction = new Transaction($hash, $byteSize, $receivedTime, $relayedByIP,
			Transaction::BLANK_VALUE, Transaction::BLANK_VALUE, Transaction::BLANK_VALUE,
			$estimatedAmountTransacted, $quantityArray);
	}
	
	private function getInputAddressesFromJSON()
	{
		$inputAddresses = array();
		foreach($this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_INPUTS_IDENTIFIER] as $inputJSON)
		{
			$inputAddress = $inputJSON[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_INPUT_IDENTIFIER]
				[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_INPUT_ADDRESS_IDENTIFIER];
			$inputAddresses[] = $inputAddress;
		}
		
		return $inputAddresses;
	}

	private function getOutputAddressesFromJSON()
	{
		$outputAddresses = array();
		foreach($this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_OUTPUTS_IDENTIFIER] as $outputJSON)
		{
			$outputAddress = $outputJSON[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_OUTPUT_ADDRESS_IDENTIFIER];
			$outputAddresses[] = $outputAddress;
		}
		
		return $outputAddresses;
	}
	
	private function getInputValuesFromJSON()
	{
		$inputValues = array();
		foreach($this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_INPUTS_IDENTIFIER] as $inputJSON)
		{
			$inputSatoshi = $inputJSON[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_INPUT_IDENTIFIER]
				[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_INPUT_AMOUNT];
			$inputValue = BlockchainInfoTxReader::convert_satoshis_to_btc_string($inputSatoshi);
			$inputValues[] = $inputValue;
		}
		
		return $inputValues;
	}
	
	private function getOutputValuesFromJSON()
	{
		$outputValues = array();
		foreach($this->json[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_OUTPUTS_IDENTIFIER] as $outputJSON)
		{
			$outputSatoshi = $outputJSON[BlockchainInfoTxReader::BLOCKCHAIN_INFO_API_TX_OUTPUT_AMOUNT];
			$outputValue = BlockchainInfoTxReader::convert_satoshis_to_btc_string($outputSatoshi);
			$outputValues[] = $outputValue;
		}
		
		return $outputValues;
	}
	
	public static function generate_blockchain_info_tx_lookup_url($txHash)
	{
		$url = BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_URL_PREFIX . $txHash . 
			BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_URL_SUFFIX;
		if (BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_JSON_API_KEY)
		{
			$url .= '&api_code=' . BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_JSON_API_KEY;
		}
		return $url;
	}
	
	public static function enerate_blockchain_info_tx_json_lookup_url($txHash)
	{
		$url = BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_JSON_URL_PREFIX . $txHash . 
			BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_JSON_URL_SUFFIX;
		if (BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_JSON_API_KEY)
		{
			$url .= '&api_code=' . BlockchainInfoTxReader::BLOCKCHAIN_INFO_TX_JSON_API_KEY;
		}
		return $url;
	}
	
	public static function convert_satoshis_to_btc_string($satoshis)
	{
		return bcdiv($satoshis, '100000000', 9);
	}
}
?>