<?php
include_once(__DIR__ . '/BlockchainInfoTxFinder.php');

class BlockchainInfoSharedcoinTxFinder extends BlockchainInfoTxFinder
{
	
	#how to recognize a SharedCoin transaction from other transactions
	const BLOCKCHAIN_RELAYED_STRING_1 = 'Blockchain.info';
	const BLOCKCHAIN_RELAYED_STRING_2 = '127.0.0.1';
	const MINIMUM_INPUTS = 5;
	const MINIMUM_OUTPUTS = 5;
	
	#TODO: Can also blacklist certain regexes for tags, for example:
	#"addr_tag_link":"http:\/\/satoshidice.com"
	#"addr_tag":"SatoshiDICE 18%"
	
	#inherited:
	#public function getAllQualifyingTxs()
	##returns array of Transaction objects for all transactions that qualify
	
	#inherited:
	#public function __construct($startingBlockHeight, $searchDirection, $optional_MaxNumTxs)
	
	#override me in order to update to subclass's version of does_tx_qualify
	protected function processCurrentTransaction(&$qualifyingTransactions, $currentTx)
	{
		print "DEBUG: about to die?\n";
		if (BlockchainInfoSharedcoinTxFinder::does_tx_qualify($currentTx))
		{
			$qualifyingTransactions[] = $currentTx;
			
			$this->numQualified++;
			$this->infoLog->log("SharedCoin transaction #" . $this->numQualified . 
				"in block " . $this->currentBlockHash . ":\n" . $currentTx->getFancyString());
		}
	}
	
	#determines which transactions will be returned by getAllQualifyingTxs(). Must be override by subclasses.
	public static function does_tx_qualify($transaction)
	{
		//print "DEBUG: Entered does_tx_qualify() with transaction: " . $transaction->getFancyString() . "\n";
		#should have at least 5 inputs and 5 outputs excluding fees
		#fees should divide evenly by typical fee amount
		#should be received by blockchain.info
		
		$numInputs = count($transaction->inputList);
		$numOutputs = 0;
		
		foreach ($transaction->outputList as $outputQuantity)
		{
			if ($outputQuantity->type === TransactionQuantity::OUTPUT)
			{
				$numOutputs++;
			}
		}
		
		//print "DEBUG: does_tx_qualify(): $numInputs inputs and $numOutputs outputs and relayedByIP: " . trim($transaction->relayedByIP) . "\n";
		
		if ($numInputs >= BlockchainInfoSharedcoinTxFinder::MINIMUM_INPUTS and 
			$numOutputs >= BlockchainInfoSharedcoinTxFinder::MINIMUM_OUTPUTS and
			(
				trim($transaction->relayedByIP) == BlockchainInfoSharedcoinTxFinder::BLOCKCHAIN_RELAYED_STRING_1 or
				trim($transaction->relayedByIP) == BlockchainInfoSharedcoinTxFinder::BLOCKCHAIN_RELAYED_STRING_2
			))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}

?>