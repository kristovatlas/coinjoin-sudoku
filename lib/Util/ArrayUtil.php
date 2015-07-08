<?php

include_once(__DIR__ . '/StringUtil.php');

class ArrayUtil
{
	public static function returnSmallerList($valList1, $valList2)
	{
		if (count($valList1) < count($valList2)) { return $valList1; }
		if (count($valList2) < count($valList1)) { return $valList2; }
		return $valList1; #default	
	}
	
	public static function returnLargerList($valList1, $valList2)
	{
		if (count($valList1) > count($valList2)) { return $valList1; }
		if (count($valList2) > count($valList1)) { return $valList2; }
		return $valList2; #default
	}
	
	public static function arrayToString($arr)
	{
		$ret = '[';
		foreach ($arr as $val)
		{
			$ret .= "$val, ";
		}	
		
		$ret = StringUtil::removeTrailingCommas($ret);
		$ret .= ']';
		return $ret;
	}
	
	public static function arrayMapToString($arr)
	{
		$ret = '[';
		foreach ($arr as $key => $val)
		{
			$ret .= "[$key]=>$val, ";
		}	
		
		$ret = StringUtil::removeTrailingCommas($ret);
		$ret .= ']';
		return $ret;
	}
	
	public static function indexed2DArrayToString($arr)
	{
		$ret = '[';
		foreach ($arr as $innerArrIndex => $innerArr)
		{
			$ret .= "[$innerArrIndex]:" . ArrayUtil::arrayToString($innerArr) . ", ";
		}
		
		$ret = StringUtil::removeTrailingCommas($ret);
		$ret .= ']';
		return $ret;
	}
	
	public static function arrayCopy($arr)
	{
		$new = array();
		foreach ($arr as $key => $val)
		{
			$new[$key] = $val;
		}
		return $new;
	}
	
	public static function array2DCopy($arr)
	{
		$newOuter = array();
		foreach ($arr as $key => $innerArr)
		{
			$newInner = ArrayUtil::arrayCopy($innerArr);
			$newOuter[$key] = $newInner;
		}
		return $newOuter;
	}
	
	#returns: nothing (modifies param0)
	public static function indexedArrayValueSwap(&$arr, $index1, $index2)
	{
		$temp = $arr[$index1];
		$arr[$index1] = $arr[$index2];
		$arr[$index2] = $temp;
	}
}

?>