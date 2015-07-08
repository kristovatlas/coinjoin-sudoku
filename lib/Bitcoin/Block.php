<?php

include_once(__DIR__ . '/Transaction.php');
include_once(__DIR__ . '/BlockchainInfo/BlockchainInfoTxReader.php');

class Block
{
	/*
	*	Block attributes that can be acquired from Blockchain.info. Only some of these will be implemented for now.
	*	Number Of Transactions
	*	Output Total	X BTC
	*	Estimated Transaction Volume	X BTC
	*	Transaction Fees	X BTC
	*	Height	int
	*	Timestamp	e.g. 2013-06-02 11:15:08
	*	Received Time	e.g. 2013-06-02 11:15:08
	*	Relayed By	e.g. Unknown
	*	Difficulty	e.g. 12,153,411.70977583
	*	Bits e.g. 436298084
	*	Size e.g. 198.0791015625 KB
	*	Version	e.g. 2
	*	Nonce e.g. 1160223591
	*	Block Reward e.g. 25 BTC
	*	Hash e.g. 000000000000001b61d890cdf70c2200cbad594f609ab01e85f635706ab6517e
	*	Previous Block Hash e.g. 0000000000000149efb8e2fb73a038c66c4f97c02e3cd8fd9cd38f68e8a188e2
	*	Next Block(s) Hash e.g. 000000000000006305a075a6c0bcc09128108ef652c2d79ae485ced550c1f265
	*	Merkle Root	e.g. 400e4fc50426a005eec011e34da2ec69fb3792f77c381225255e1176cdcf6de0
	*	List of transaction hashes for this block
	*/
	
	public $height;	#int, set in constructor
	public $transactionHashes = array();	#added to by hasNextTransaction();
	private $currentTxHashIndex = 0;
	public $hash;
	public $previousBlockHash;
	
	public function __construct($height, $blockHash, $previousBlockHash)
	{
		if (!is_numeric($height))
		{
			die("Death: Height supplied '$height' is not numeric in Block.\n");
		}
		
		$this->height = $height;
		$this->hash = $blockHash;
		$this->previousBlockHash = $previousBlockHash;
	}
	
	public function addTxHash($txHash)
	{
		$this->transactionHashes[] = $txHash;
		//print "DEBUG: Block::addTxHash(): Added hash $txHash\n";
	}
	
	#returns whether all transactions have been iterated by getNextTransaction().
	public function hasNextTransaction()
	{
		//print "DEBUG: Entered hasNextTransaction() with currentTxHashIndex = " .
			$this->currentTxHashIndex . " and " . count($this->transactionHashes) .
			" total hashes\n";
		if ($this->currentTxHashIndex + 1 < count($this->transactionHashes))
		{
			//print "DEBUG: This block has more tx's\n";
			return TRUE;
		}
		else
		{
			//print "DEBUG: This block has no more tx's\n";
			return FALSE;
		}
	}
	
	#Returns a Transaction object for the next transaction to be iterated for this Block
	public function getNextTransaction()
	{
		if (!$this->hasNextTransaction())
		{
			die("Death: This Block object does not have additional hashes to return in getNextTxHash().\n");
		}
		
		$currentTxHash = $this->transactionHashes[$this->currentTxHashIndex];
		
		$reader = new BlockchainInfoTxReader('','','',$currentTxHash);
		$this->currentTxHashIndex++;
		return $reader->transaction;
	}
	
	#increments the current tx hash index, but without fetching data from the Blockchain.info website.
	public function skipNextTransaction()
	{
		$this->currentTxHashIndex++;
	}
}
?>