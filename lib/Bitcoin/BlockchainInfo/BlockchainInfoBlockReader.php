<?php

include_once(__DIR__ . '/../../Web/ThrottledJSONReader.php');
include_once(__DIR__ . '/../Block.php');

class BlockchainInfoBlockReader
{
	public $block; #init'd in constructor
	
	protected $jsonReader;
	
	public $json; #init'd in constructor
	
	const BLOCKCHAIN_INFO_BLOCK_URL_PREFIX = 'https://blockchain.info/block-index/';
	const BLOCKCHAIN_INFO_BLOCK_URL_SUFFIX = '?format=json';
	const BLOCKCHAIN_INFO_BLOCK_URL_API_KEY = ''; # leave me blank or set me
	
	#From: https://blockchain.info/api/blockchain_api
	const BLOCKCHAIN_INFO_TRANSACTION_ARRAY_IDENTIFER = 'tx';
	const BLOCKCHAIN_INFO_TRANSACTION_HASH_IDENTIFIER = 'hash';
	const BLOCKCHAIN_INFO_BLOCK_HASH_IDENTIFIER = 'hash';
	const BLOCKCHAIN_INFO_BLOCK_HEIGHT_IDENTIFIER = 'height';
	const BLOCKCHAIN_INFO_BLOCK_PREVIOUS_BLOCK_HASH_IDENTIFIER = 'prev_block';
	
	public function __construct($blockHash)
	{
		print "DEBUG: Entered BlockchainInfoBlockReader::__construct() with height $height\n";
		$this->jsonReader = new ThrottledJSONReader();
		
		$url = BlockchainInfoBlockReader::generate_blockchain_info_block_lookup_url($blockHash);
		
		print "DEBUG: url = $url\n";
		
		$this->json = $this->jsonReader->getJSON($url);
		
		if (!is_null($this->json))
		{
			$this->setBlockFromJSON();
		}
		else
		{
			die("Null JSON in BlockchainInfoBlockReader constructor. URL: '$url'\n");
		}
	}
	
	private function setBlockFromJSON()
	{
		print "DEBUG: Entered setHashesFromJSON()\n";
		$uniqueTxHashesMap = array();
		
		$blockHash =  $this->json[BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_HASH_IDENTIFIER];
		$blockHeight = $this->json[BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_HEIGHT_IDENTIFIER];
		$prevBlockHash = $this->json[BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_PREVIOUS_BLOCK_HASH_IDENTIFIER];
		
		$this->block = new Block($blockHeight, $blockHash, $prevBlockHash);
		
		$transactionsArray = $this->json[BlockchainInfoBlockReader::BLOCKCHAIN_INFO_TRANSACTION_ARRAY_IDENTIFER];
		foreach($transactionsArray as $tx)
		{
			$hash = $tx[BlockchainInfoBlockReader::BLOCKCHAIN_INFO_TRANSACTION_HASH_IDENTIFIER];
			
			$uniqueTxHashesMap[$hash] = 1;
			print "DEBUG: Found tx hash $hash with a total so far of " . count($uniqueTxHashesMap) . " hashes found.\n";
		}
		
		print "DEBUG: Found " . count($uniqueTxHashesMap) . " hashes.\n";
		
		foreach ($uniqueTxHashesMap as $hash => $val)
		{
			print "DEBUG: $hash\n";
			$this->block->addTxHash($hash);
		}
		
		
	}
	
	public static function generate_blockchain_info_block_lookup_url($blockHash)
	{
		$url = BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_URL_PREFIX . 
				$blockHash .
				BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_URL_SUFFIX;
		
		if (BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_URL_API_KEY)
		{
			$url .= '&api_code=' . BlockchainInfoBlockReader::BLOCKCHAIN_INFO_BLOCK_URL_API_KEY;
		}
		return $url;
	}
}

?>