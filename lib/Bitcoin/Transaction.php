<?php

/*
*	Represents a crypto-currency transaction, e.g. a Bitcoin transaction.
*/

include_once(__DIR__ . '/TransactionQuantity.php');
include_once(__DIR__ . '/../Util/StringUtil.php');

class Transaction
{
	#The transaction's hash id (TODO: xaction malleability?)
	public $hash = '';
	
	#Size of xaction in bytes
	public $byteSize = 0;
	
	public $receivedTime = '';
	
	public $relayedByIP = '';
	
	public $totalInput = 0.0;
	
	#does not include mining fees
	public $totalOutput = 0.0;
	
	public $fees = 0.0;
	
	public $estimatedAmountTransacted = 0.0;
	
	public $inputList = array();
	
	public $outputList = array();
	
	const BLANK_VALUE = 1;
	
	#param0: xaction hash string
	#param1: byteSize as integer
	#param2: receivedTime as string
	#param3: relayedByIP as string
	#param4: totalInput as float (Transaction::BLANK_VALUE to compute automatically)
	#param5: totalOutput as float (Transaction::BLANK_VALUE to compute automatically)
	#param6: fees as float (Transaction::BLANK_VALUE to compute automatically)
	#param7: estimatedAmountTransacted as float
	#param8: quantityArr as array of TransactionQuantity objects	
	public function __construct($hash, $byteSize, $receivedTime, $relayedByIP, $totalInput,
		$totalOutput, $fees, $estimatedAmountTransacted, $quantityArr)
	{
		$this->hash = $hash;
		$this->byteSize = $byteSize;
		$this->receivedTime = $receivedTime;
		$this->relayedByIP = $relayedByIP;
		if ($totalInput != Transaction::BLANK_VALUE)
		{
			$this->totalInput = $totalInput;
		}
		if ($totalOutput != Transaction::BLANK_VALUE)
		{
			$this->totalOutput = $totalOuput;
		}
		if ($fees != Transaction::BLANK_VALUE)
		{
			$this->fees = $fees;
		}
		$this->estimatedAmountTransacted = $estimatedAmountTransacted;
		
		if (count($quantityArr) == 0)
		{
			die("Death: Empty quantity list in Transaction constructor.\n");	
		}
		else
		{
			foreach ($quantityArr as $quantity)
			{
				if ($quantity->type == TransactionQuantity::INPUT)
				{
					array_push($this->inputList, $quantity);
					
					if ($totalInput == Transaction::BLANK_VALUE)
					{
						$this->totalInput += $quantity->amount;	
					}
				}
				elseif($quantity->type == TransactionQuantity::OUTPUT)
				{
					array_push($this->outputList, $quantity);
					
					if ($totalOutput == Transaction::BLANK_VALUE)
					{
						$this->totalOutput += $quantity->amount;	
					}
				}
				elseif($quantity->type == TransactionQuantity::FEE)
				{
					array_push($this->outputList, $quantity);
					
					#not counted toward the ouptut total
				}
				else
				{
					die("Death: Invalid quantity type in Transaction.\n");	
				}
			}	
		}
		
		if ($fees == Transaction::BLANK_VALUE)
		{
			#Assume fees = total input - total output
			$this->fees = bcsub($this->totalInput, $this->totalOutput, 8);	
		}
	}
	
	public function __toString()
	{
		$inputString = StringUtil::listAsString($this->inputList, FALSE);
		$outputString = StringUtil::listAsString($this->outputList, FALSE);		
		$paramValMap = 
			array(
			'hash'						=>	$this->hash,
			'byteSize'					=> 	$this->byteSize,
			'receivedTime'				=>	$this->receivedTime,
			'relayedByIP'				=>	$this->relayedByIP,
			'totalInput'				=>	$this->totalInput,
			'totalOutput'				=>	$this->totalOutput,
			'fees'						=>	$this->fees,
			'estimatedAmountTransacted'	=>	$this->estimatedAmountTransacted,
			'inputs'					=>	$inputString,
			'outputs'					=> 	$outputString
			);
		
		return StringUtil::paramValueMapAsString($paramValMap, FALSE);
	}
	
	public function getFancyString()
	{
		$str = '';
		
		$inputString = StringUtil::listAsString($this->inputList, TRUE);
		$outputString = StringUtil::listAsString($this->outputList, TRUE);		
		
		$str .= '' .
			'hash:	' . $this->hash . "\n" .
			'byteSize:	' . $this->byteSize . "\n" .
			'receivedTime:	' . $this->receivedTime . "\n" .
			'relayedByIP:	' . $this->relayedByIP . "\n" .
			'totalInput:	' . $this->totalInput . "\n" .
			'totalOutput:	' . $this->totalOutput . "\n" .
			'fees:	' . $this->fees . "\n" .
			'estimatedAmountTransacted:	' . $this->estimatedAmountTransacted . "\n" .
			'';
		$str .= "inputs:\n" .
			"$inputString\n";
		$str .= "outputs:\n" .
			"$outputString\n";
		
		return $str;
	}
	
	#returns deep copy of this object
	public function getClone()
	{
		#__construct($hash, $byteSize, $receivedTime, $relayedByIP, $totalInput,
		#  $totalOutput, $fees, $estimatedAmountTransacted, $quantityArr)	
		
		$quantityArr = array_merge($this->inputList, $this->outputList);
		
		$clone = new Transaction(
						$this->hash,
						$this->byteSize,
						$this->receivedTime,
						$this->relayedByIP,
						$this->totalInput,
						$this->totalOutput,
						$this->fees,
						$this->estimatedAmountTransacted,
						$quantityArr);
		
		return $clone;
	}
	
	public static function getSumOfArray($arr)
	{
		$sum = '0.0';
		foreach ($arr as $val)
		{
			$sum = bcadd($sum, "" . $val, 8);
		}
		
		return $sum;
	}
}

?>