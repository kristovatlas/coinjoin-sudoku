<?php

class StringUtil
{
	public static function removeTrailingCommas($str)
	{
		return preg_replace("/,(\s*)$/", "$1", $str);
	}
	
	public static function removeTrailingChar($str, $char)
	{
		return preg_replace('/' . preg_quote($char) . '(\s*)$/', "$1", $str);
	}
	
	#param0
	#param1: include in one line, or separate params by newlines? TRUE or FALSE
	public static function paramValueMapAsString($paramValMap, $multiLine)
	{
		$ret = '[';
		if ($multiLine === TRUE)
		{
			$ret .= "\n";	
		}
		foreach ($paramValMap as $param => $val)
		{
			$ret .= "$param: $val, ";
			if ($multiLine === TRUE)
			{
				$ret .= "\n";
			}
		}
		$ret = StringUtil::removeTrailingCommas($ret);
		$ret .= ']';
		
		return $ret;
	}
	
	public static function listAsString($list, $multiLine)
	{
		$ret = '';
		if ($multiLine === TRUE) { $ret .= "\n"; }
		$ret = "{";
		if ($multiLine === TRUE) { $ret .= "\n"; }
		foreach ($list as $item)
		{
			$ret .= "$item";
			if ($multiLine === TRUE)
			{
				$ret .= "\n";	
			}
			else
			{
				$ret .= ", ";
			}
		}	
		if ($multiLine === FALSE)
		{
			$ret = StringUtil::removeTrailingCommas($ret);	
		}
		$ret .= "}";
		
		return $ret;
	}
	
	public static function getNumberDigitsAfterDecimal($float)
	{
		$amountAsString  = '' . $float;
		$index = strpos($amountAsString, '.');
		if ($index === FALSE)
		{
			return 0;	
		}
		else
		{
			return strlen(substr($amountAsString, $index + 1));	
		}
	}
	
	public static function getNumberDigitsBeforeDecimal($float)
	{
		$amountAsString = '' . $float;
		$index = strpos($amountAsString, '.');
		if ($index === FALSE)
		{
			return strlen($amountAsString);
		}
		else
		{
			return $index;	
		}
	}
}

?>