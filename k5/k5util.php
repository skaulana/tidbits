<?php

##### K5UTIL.PHP

##### Powers the kaulana.com weblog, revision five.
##### Including this file defines miscellaneous bits of functionality.

####################################################
##### Definitions
####################################################

$pwd = dirname($_SERVER["PHP_SELF"]);

define('PATH_URL', $pwd == "/" ? "" : $pwd);
define('NICE_URL', file_exists('.htaccess'));

####################################################
##### Input Cleaning
####################################################

function clean_normal($str)
// Remove whitespace and unescape quotes if necessary.
{
	if (get_magic_quotes_gpc())	// Then we need to convert \" to "...
	{
		return stripslashes(trim($str));
	}
	else				// Simply remove the whitespace...
	{
		return trim($str);
	}
}

function clean_html($str)
// In addition to the above, escape HTML markup.
{
	return htmlspecialchars(clean_normal($str));
}

function clean_singleline($str)
// In addition to the above, remove carriage returns.
{
	$str = str_replace("\r", "", clean_html($str));
	return str_replace("\n", " ", $str);
}

function unclean_html($str)
// Restore HTML markup which was previous escaped.
{
	return html_entity_decode(clean_normal($str));
}

function unclean_dir($str)
// In addition to the above, escape directory slashes.
{
	$str = str_replace("/", " ", clean_normal($str));
	return str_replace("\\", " ", $str);
}

function clean_for_url($str)
// Use to provide clean encodings for arguments passed into a URL.
{
	$str = ereg_replace("[][)(><\"?!&%^,./|\\+-]", " ", $str);
	return ereg_replace("( )+", "-", trim($str));
}

####################################################
##### Token Generation
####################################################

function get_token()
// Return a unique value to be used only once.
{
	return sha1(uniqid(rand(), true));
}

####################################################
##### Date Manipulations
####################################################

/*    Use this class to manipulate dates received from MySQL in their
 *    format (YYYY-MM-DD HH:MM:SS). If you don't provide a date in the
 *    constructor, the current time will always be used.
 */

class k5date
{
	var $udate;

	function get_8()       { return $this->call_php_date("Ymd");    }
	function get_14()      { return $this->call_php_date("YmdHis"); }
	function get_time()    { return $this->call_php_date("U");      }
	function get_rfc2822() { return $this->call_php_date("r");      }

	function get_mm()      { return $this->call_php_date("m"); }
	function get_dd()      { return $this->call_php_date("d"); }
	function get_yyyy()    { return $this->call_php_date("Y"); }

	function get_month()   { return $this->call_php_date("F");            }
	function get_spell()   { return $this->call_php_date("l, F j, Y");    }
	function get_human()   { return $this->call_php_date("n/j/Y g:i:sa"); }
	function get_sql()     { return $this->call_php_date("Y-m-d H:i:s");  }

	function k5date($d = NULL)
	// Transforms YYYY-MM-DD HH:MM:SS into a stored timestamp.
	{
		if ($d) $this->udate = mktime(
			substr($d, 11, 2), // HH
			substr($d, 14, 2), // MM
			substr($d, 17, 2), // SS
			substr($d, 5, 2),  // MM
			substr($d, 8, 2),  // DD
			substr($d, 0, 4)); // YY

		else $this->udate = $d;
	}

	function call_php_date($format)
	// Calls the internal date() function as necessary.
	{
		return $this->udate ? date($format, $this->udate) : date($format);
	}

	function set_human($d)
	// Consume $d as M/D/YYYY H:MM:SS(am/pm).
	{
		$reformat = preg_replace("/([0-9]+)\/([0-9]+)\/([0-9]+) ([0-9]+):([0-9]+):([0-9]+)(a|p|am|pm)/",
				"$3 $1 $2 $4 $5 $6 $7", $d);

		$sub = explode(" ", $reformat);
		$am = stristr($sub[6], "a");

		if       ($am && $sub[3] == "12") $sub[3] = "00"; // midnight to 00
		else if (!$am && $sub[3] != "12") $sub[3] += 12;  // add 12 hours to pm

		if (strlen($sub[1]) < 2) $sub[1] = "0".$sub[1]; // add 0 to month
		if (strlen($sub[2]) < 2) $sub[2] = "0".$sub[2]; // add 0 to day
		if (strlen($sub[3]) < 2) $sub[3] = "0".$sub[3]; // add 0 to hour

		$this->k5date($sub[0]."-".$sub[1]."-".$sub[2]." ".$sub[3].":".$sub[4].":".$sub[5]);
	}
};

####################################################
##### Stylized CSS
####################################################

function parse_parens($str, $count = 0)
// Recursively applies a CSS style to text within parentheses.
{
	$len  = strlen($str);
	$lpar = strpos($str, "(");
	$rpar = strpos($str, ")");

	if ($rpar === false) return $str;
	else if ($lpar === false || $rpar < $lpar)
	{
		return ($count == 0) ? $str : substr($str, 0, $rpar).")</span>".parse_parens(substr($str, $rpar+1, $len-$rpar-1), $count-1);
	}
	else // $lpar < $rpar
	{
		return substr($str, 0, $lpar)."<span class='paren'>(".parse_parens(substr($str, $lpar+1, $len-$lpar-1), $count+1);
	}
}

##### End PHP code, (c) 2006 kaulana.com

?>