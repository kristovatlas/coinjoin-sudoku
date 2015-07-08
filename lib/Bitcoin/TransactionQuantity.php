<?php

/*
*	A quantity transacted in a crypto-currency transaction, either an input or output.
*	  Each quantity is assigned a unique, random id number for identification. This is not
*	  taken from the blockchain. This is to distinguish quanitities with identitical
*	  information, e.g. address, amount, etc.
*/

include_once(__DIR__ . '/../Util/StringUtil.php');

class TransactionQuantity
{
	public $id = 0;
	
	public $address = '';
	
	#string
	public $amount = '';
	
	#An id assigned to one or more TransactionQuantities aglorithmically in other classes
	# that use this class, based on which quantities those classes think should be
	# grouped/matched together.
	public $groupId = -1;
	
	#INPUT or OUTPUT or FEE
	public $type;
	
	const INPUT  = 1;
	const OUTPUT = 2;
	const FEE = 3;
	
	#param0
	#param1
	#param2: float as a string, starting with one or more integers 0-9, followed by a decimal
	# point, followed by one or more integers 0-9. If needed, this can also be a multiple of
	# ten rather than 0-9, such as in the case of artificial quantities required to balance
	# inputs/outputs when numbers carry over between digit places.
	public function __construct($type, $address, $amount, $optionalArgs)
	{
		if ($type == TransactionQuantity::INPUT or
			$type == TransactionQuantity::OUTPUT or
			$type == TransactionQuantity::FEE)
		{
			$this->type = $type;	
		}
		else
		{
			die("Death: Invalid type specified in TransactionQuanitity construct.\n");	
		}
		
		if (isset($optionalArgs) and isset($optionalArgs['identifier']))
		{
			$this->id = $optionalArgs['identifier'];
		}
		else
		{
			$this->id = TransactionQuantity::getNewQuantityIdentifier();
		}
		
		$this->address = $address;
		
		#in order to deal with issues related to carry-over digits, pad this amount
		# with a leading zero to ensure that carry-over digits won't go into a digit
		# place that doesn't exist. 
		$this->amount = '0' . $amount;
	}
	
	public function __toString()
	{
		$type = '';
		if ($this->type == TransactionQuantity::INPUT)
		{
			$type = 'INPUT';	
		}
		elseif ($this->type == TransactionQuantity::OUTPUT)
		{
			$type = 'OUTPUT';	
		}
		elseif ($this->type == TransactionQuantity::FEE)
		{
			$type = 'FEE';	
		}
		$paramValMap = 
			array(
			'type' 		=>	$type,
			'randid'	=>	$this->id,
			'address'	=>	$this->address,
			'amount'	=>	$this->amount
			);
		return StringUtil::paramValueMapAsString($paramValMap, FALSE);
	}
	
	# Returns the amount as an array, placing a digit (0-9) in each
	#   index, and making the decimal place a period '.'
	public function getAmountAsArray()
	{
		$arr = preg_split('//', $this->amount, -1, PREG_SPLIT_NO_EMPTY);
		return $arr;
	}
	
	#Returns the amount as an array, placing a digit (0-9) in each
	#  index, making the decimal place a period ".", and padding with
	#  leading and trailing zeroes in order to get the specified number
	#  of digits before and after the decimal point. The total number
	#  will be digitsBefore + digitsAfter + 1.
	public function getAmountsAsArrayWithSpecifiedDigits($digitsBeforeDecimal,
														 $digitsAfterDecimal)
	{
		#get the array with just the digits
		$arr = $this->getAmountAsArray();
		
		#now, add leading zeros until we have the correct number of digits in the arary to
		#  return. 0-based.
		$leadingZeros = $digitsBeforeDecimal - $this->getNumberDigitsBeforeDecimal();
		for ($i = 0; $i < $leadingZeros; $i++)
		{
			array_unshift($arr, 0);
		}
		
		#add trailing zeros
		$trailingZeros = $digitsAfterDecimal - $this->getNumberDigitsAfterDecimal();
		for ($i = 0; $i < $trailingZeros; $i++)
		{
			array_push($arr, 0);	
		}
		
		return $arr;
	}
	
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}
	
	public function getNumberDigitsAfterDecimal()
	{
		return StringUtil::getNumberDigitsAfterDecimal($this->amount);
	}
	
	public function getNumberDigitsBeforeDecimal()
	{
		return StringUtil::getNumberDigitsBeforeDecimal($this->amount);
	}
	
	public static function getNewQuantityIdentifier()
	{
		return "Q" . rand();
	}
	
	public static function getNewGroupIdentifier()
	{
		return "G" . rand();
	}
	
	public static function getGroupIdentifierFromQuantityIdentifier($identifier)
	{
		if (!TransactionQuantity::isQuantityIdentifier($identifier))
		{
			die("Death: Not a quantity identifier in TransactionQuantity::getGroupIdentifierFromQuantityIdentifier(): $identifier\n");
		}
		return 'G' . substr($identifier, 1);
	}
	
	public static function isQuantityIdentifier($identifier)
	{
		if (substr($identifier, 0, 1) == 'Q')
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public static function isGroupIdentifier($identifier)
	{
		if (substr($identifier, 0, 1) == 'G')
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