<?php

include_once(__DIR__ . '/../Block.php');
include_once(__DIR__ . '/BlockchainInfoBlockReader.php');
include_once(__DIR__ . '/../../Util/Logger.php');

class BlockchainInfoTxFinder
{
	const BLOCK_DIRECTION_FORWARD = 2;
	const BLOCK_DIRECTION_BACKWARD = 3;
	
	const DEFAULT_MAX_NUM_TRANSACTIONS_TO_SEARCH = 10;
	
	#For testing purposes. The rest of the time, just leave at zero.
	const NUM_TRANSACTIONS_TO_SKIP_AT_START = 0;
	public $numTransactionsToSkipRemaining = 0;
	
	public $startingBlockHash;
	public $currentBlockHash;
	public $nextBlockHash;
	public $searchDirection;
	public $maxNumTxsToSearch;
	public $txRemaningToSearch;
	
	protected $infoLog; //init'd in constructor
	protected $statusLog; //init'd in constructor
	const PRINT_QUALIFYING_TRANSACTIONS_TO_FILE_ON = TRUE;
	const PRINT_STATUS_OF_SEARCH_ON = TRUE;
	
	public $numQualified = 0;
	
	public function __construct($startingBlockHash, $searchDirection, $optional_MaxNumTxs)
	{
		if (BlockchainInfoTxFinder::PRINT_QUALIFYING_TRANSACTIONS_TO_FILE_ON)
		{
			$this->infoLog = new Logger(Logger::LEVEL_INFO);
		}
		else
		{
			$this->debugLog = new Logger(Logger::LEVEL_OFF);
		}
		
		if (BlockchainInfoTxFinder::PRINT_STATUS_OF_SEARCH_ON)
		{
			$this->statusLog = new Logger(Logger::LEVEL_STATUS);
		}
		else
		{
			$this->statusLog = new Logger(Logger::LEVEL_OFF);
		}
		
		if ($searchDirection === BlockchainInfoTxFinder::BLOCK_DIRECTION_FORWARD)
		{
			$this->searchDirection = BlockchainInfoTxFinder::BLOCK_DIRECTION_FORWARD;
			die("Forward search not yet supported. TODO: Figure out how to convert between block index and block height.\n");
		}
		elseif($searchDirection === BlockchainInfoTxFinder::BLOCK_DIRECTION_BACKWARD)
		{
			$this->searchDirection = BlockchainInfoTxFinder::BLOCK_DIRECTION_BACKWARD;
		}
		else
		{
			die("Death: Invalid searchDirection in BlockchainInfoTxFinder.\n");
		}
		
		if (!is_null($optional_MaxNumTxs) and $optional_MaxNumTxs != '' and $optional_MaxNumTxs != 0)
		{
			$this->maxNumTxsToSearch = $optional_MaxNumTxs;
		}
		else
		{
			$this->maxNumTxsToSearch = BlockchainInfoTxFinder::DEFAULT_MAX_NUM_TRANSACTIONS_TO_SEARCH;
		}

		$this->startingBlockHash = $startingBlockHash;
		$this->currentBlockHash = $startingBlockHash;
		$this->txRemaningToSearch = $this->maxNumTxsToSearch; #initialize. This will be decremented until 0
		
		if (BlockchainInfoTxFinder::NUM_TRANSACTIONS_TO_SKIP_AT_START)
		{
			$this->numTransactionsToSkipRemaining = BlockchainInfoTxFinder::NUM_TRANSACTIONS_TO_SKIP_AT_START;
			$this->statusLog->log("Will skip " . $this->numTransactionsToSkipRemaining . " at the beginning.\n");
		}
	}
	
	#returns array of Transaction objects for all transactions that qualify
	public function getAllQualifyingTxs()
	{
		$currentBlockReader = new BlockchainInfoBlockReader($this->currentBlockHash);
		$currentBlock = $currentBlockReader->block;
		if ($this->searchDirection === BlockchainInfoTxFinder::BLOCK_DIRECTION_FORWARD)
		{
			#TODO: not yet implemented
		}
		else
		{
			$this->nextBlockHash = $currentBlock->previousBlockHash;
			$this->infoLog->log("Searching blocks backwards starting at block " . $this->currentBlockHash . "\n");
		}
		
		#array of Transaction objects
		$qualifyingTransactions = array();
		
		while ($this->txRemaningToSearch > 0)
		{
			$this->statusLog->log($this->txRemaningToSearch . " txs remaining to search.\n");
			if (!$currentBlock->hasNextTransaction())
			{
				#done looking through this block. Go up or down depending on direction.
				if ($this->searchDirection === BlockchainInfoTxFinder::BLOCK_DIRECTION_FORWARD)
				{
					die("Death: Forward direction not yet supported in BlockchainInfoTxFinder.\n");
					#
					#
					#TODO
					/* deprecated
					$this->currentBlockHeight++;
					print "DEBUG: Now on block height " . $this->currentBlockHeight . "\n";
					$currentBlockReader = new BlockchainInfoBlockReader($this->currentBlockHeight);
					$currentBlock = $currentBlockReader->block;
					*/
				}
				else #backward
				{
					#go to previous block
					$this->currentBlockHash = $this->nextBlockHash;
					
					$this->statusLog->log("Now on block hash " . $this->currentBlockHash . "\n");
					
					$currentBlockReader = new BlockchainInfoBlockReader($this->currentBlockHash);
					$currentBlock = $currentBlockReader->block;
					$this->nextBlockHash = $currentBlock->previousBlockHash;
				}
				
				if (!$currentBlock->hasNextTransaction())
				{
					die("Death: Changed blocks but couldn't get next transaction hash for block hash " .
						$this->currentBlockHash . "\n");
				}
			}#done changing blocks if necessary, current block must have a next tx
			
			if ($this->numTransactionsToSkipRemaining > 0)
			{
				#just skip this xaction, no need to process it.
				$this->numTransactionsToSkipRemaining--;
				$this->statusLog->log("Skipped transaction, " . $this->numTransactionsToSkipRemaining . " remaining to skip.\n");
				
				#only decrement # remaining to search if it wasn't skipped
				$currentBlock->skipNextTransaction();
			}
			else
			{
				$currentTx = $currentBlock->getNextTransaction();
				$this->processCurrentTransaction($qualifyingTransactions, $currentTx); #TODO: did this go here or in while loop?
				
				#only decrement # remaining to search if it wasn't skipped
				$this->txRemaningToSearch--;
			}	
			
		} #end while loop

		return $qualifyingTransactions;
	}
	
	#override me in order to update to subclass's version of does_tx_qualify
	protected function processCurrentTransaction(&$qualifyingTransactions, $currentTx)
	{
		if (BlockchainInfoTxFinder::does_tx_qualify($currentTx))
		{
			$qualifyingTransactions[] = $currentTx;
		}
	}
	
	#determines which transactions will be returned by getAllQualifyingTxs(). Must be override by subclasses.
	public static function does_tx_qualify($transaction)
	{
		die("Death: Executing parent class version of does_tx_qualify() :-(\n");
		#override me
		return TRUE;
	}
}

?>