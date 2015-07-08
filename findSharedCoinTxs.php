<?php

/*
*	This utility script is used to search the blockchain for SharedCoin transactions. You must provide
*	  the hash of a block to start out looking, and the number of transactions you want to look through.
*	The only direction for searching currently supported is looking backwards, at older and older blocks
*	  from the starting block indicated.
*	The Blockchain.info API is currently used for blockchain exploration.
*/

include_once(__DIR__ . '/lib/Bitcoin/BlockchainInfo/BlockchainInfoSharedcoinTxFinder.php');
include_once(__DIR__ . '/lib/Bitcoin/BlockchainInfo/BlockchainInfoTxFinder.php');

#TODO: set me to the first identifiable sharedcoin tx
#For now, how about right before public Twitter announcement of SharedCoin, instead?
$startingBlockHash = '000000000000000577eaffd76cae92c7eef94cc2e8b4affb9fefd7f3d66cc32c'; //Block Height 270192 2013-11-17 21:28:20

$searchDirection  = BlockchainInfoTxFinder::BLOCK_DIRECTION_BACKWARD; 
$numTransactionsToExamine = 2000;

$finder = new BlockchainInfoSharedcoinTxFinder($startingBlockHash, $searchDirection, $numTransactionsToExamine);

$txs = $finder->getAllQualifyingTxs();

foreach ($txs as $tx)
{
	print "Possible SharedCoin transaction: ";
	print $tx->getFancyString() . "\n";
}

print "Found " . count ($txs) . " SharedCoin transactions.\n";
?>