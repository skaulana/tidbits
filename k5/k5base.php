<?php

##### K5BASE.PHP

##### Powers the kaulana.com weblog, revision five.
##### Including this file defines all base classes necessary for page generation.

require_once('k5util.php');

####################################################
##### Definitions
####################################################

/*    You'll probably want to change the stylesheet and icon logic
 *    to better suit your own needs.
 */

define('STYLESHEET', PATH_URL.'/layout/style.css');
define('FAVICON'   , PATH_URL.'/layout/favicon.ico');
define('FEED_IMAGE', PATH_URL.'/layout/feed.jpg');

####################################################
##### Basic XHTML Layout Generation
####################################################

/*    Overwrite these functions as you see fit. They are essentially placeholders for
 *    your own layout generators, though you won't need to change anything if you write
 *    your own CSS files and use them in the style of kaulana.com.
 */

class k5html
{
	static $defined_head;
	static $defined_foot;
	static $defined_ob;

	var $page_title;
	var $page_links;
	var $page_scripts;
	var $state;

	function k5html($ob = true, $t = "", $callback = "close")
	// Supply argument to disable output buffering if you wish.
	{
		self::$defined_head = false;
		self::$defined_foot = false;
		self::$defined_ob = $ob;

		$this->state = "";
		$this->page_title = clean_html($t);
		$this->page_links = "";
		$this->page_scripts = "";

		if (self::$defined_ob) @ob_start();
		register_shutdown_function(array($this, $callback));
	}

	function add_title($t)   { $this->page_title   = clean_html($t);   }
	function add_links($l)   { $this->page_links   = clean_normal($l); }
	function add_scripts($s) { $this->page_scripts = clean_normal($s); }

	function add_rss($title, $link) // add an autodiscovery link
	{
		$this->page_links .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"$title\" ";
		$this->page_links .= "href=\"http://".$_SERVER["HTTP_HOST"]."$link\" />\n";
	}

	function icon_rss($title, $link) // add an inline icon link
	{
		echo "<a href=\"$link\" class=\"imagelink\">\n";
		echo "<img alt=\"$title\" title=\"$title\" src=\"".FEED_IMAGE."\" />\n</a>\n";
	}

	function open()
	// Opening HTML.
	{
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"";
		echo " \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head profile="http://gmpg.org/xfn/11">
<title><?php echo $this->page_title ?></title>

<link rel="stylesheet" href="<?php echo STYLESHEET ?>" type="text/css" media="screen" />
<link rel="shortcut icon" href="<?php echo FAVICON ?>" type="image/x-icon" />
<?php echo $this->page_links ?>

<script type="text/javascript" src="<?php echo PATH_URL ?>/js/dom.js"></script>
<?php echo $this->page_scripts ?>
</head>

<body>
<div class="reference">
<?php
		self::$defined_head = true;
	}

	function close()
	// Closing HTML.
	{
		$this->content_close(); // if any
?>
</div>
<!-- end reference -->
</body>
</html>
<?php
		self::$defined_foot = true;
		if (self::$defined_ob) { @ob_end_flush(); self::$defined_ob = false; }
	}

	function content($pos)
	// Will generate HTML up to the specified content area.
	{
		if (self::$defined_foot)
		{
			trigger_error("HTML generation already complete.", E_USER_WARNING);
			return false;
		}

		if (!self::$defined_head) $this->open();

		$this->content_close(); // if any
		$this->content_open($pos);
	}

	function content_open($pos)
	// Opening HTML for a given content area.
	{
		$pos = clean_html($pos);
		echo "<div class=\"$pos\">\n";
		$this->state = $pos;
	}

	function content_close()
	// Closing HTML for the currently open content area (if any).
	{
		if ($this->state)
		{
			echo "\n</div>\n";
			echo "<!-- end ".$this->state." -->\n\n";
			$this->state = "";
		}
	}
};

####################################################
##### Database Access
####################################################

/*    These are wrappers for MySQL transactions. If using the default
 *    scripts for k5, you should not need to modify this class.
 */

class k5db
{
	static $handle;

	var $server;
	var $user;
	var $pass;

	function k5db($s, $u, $p, $database = "")
	// Gathers connection information and connects right away if requested.
	{
		$this->server = $s;
		$this->user = $u;
		$this->pass = $p;

		if ($database) $this->open($database);
	}

	function open($database)
	// Connect to the database using the provided parameters.
	{
		if (self::$handle) return true; // already connected

		self::$handle = @mysql_connect($this->server, $this->user, $this->pass);
		$selectdb = @mysql_select_db($database, self::$handle);

		if (!self::$handle || !$selectdb)
		{
			trigger_error("Unable to connect to database.", E_USER_WARNING);
			return false;
		}

		return true;
	}

	function is_connected() { return self::$handle; }

	function close()
	// Disconnect from the database.
	{
		if (!$this->is_connected())
		{
			trigger_error("Not connected to database.", E_USER_WARNING);
			return false;
		}

		@mysql_close(self::$handle);
		self::$handle = "";

		return true;
	}

	function dump($database, $output = false)
	// Dump the contents of the active database (either returned as a string or echoed).
	{
		if (!$this->is_connected())
		{
			trigger_error("Not connected to database.", E_USER_WARNING);
			return false;
		}

		$command = "mysqldump --host=".escapeshellarg($this->server).
				" --user=".escapeshellarg($this->user).
				" --password=".escapeshellarg($this->pass).
				" --opt ".escapeshellarg($database);

		if (!$output)
		{
			return exec($command);
		}
		else
		{
			header("Content-type: application/force-download");
			header("Content-Disposition: attachment; filename=k5.sql");
			return passthru($command);
		}
	}

	function restore($dumpfile)
	// Restore the database using the output of the dump() function.
	{
		return $this->query($dumpfile); // just execute it
	}

	function query($sql)
	// Perform the query.
	{
		if (!$this->is_connected())
		{
			trigger_error("Not connected to database.", E_USER_WARNING);
			return false;
		}

		$result = mysql_query($sql);

		if (!$result)
		{
			trigger_error("MySQL query failed: ".mysql_error().".", E_USER_WARNING);
			return false;
		}

		return $result;
	}

	function get_aggregate($sql, $a)
	// Fetch an array of database entries into memory.
	{
		$assoc = $a ? MYSQL_ASSOC : MYSQL_NUM; // prefer to MYSQL_BOTH
		$result = $this->query($sql);

		if (!$result) return false;

		for($i = 0; $i < mysql_num_rows($result); $i++)
		{
			$entries[$i] = mysql_fetch_array($result, $assoc);
		}

		return $entries;
	}

	function get_all($table, $selection = "*", $assoc = true)
	// Fetches all entries from a table into memory. You can specify $selection to return
	// only the columns you're interested in.
	{
		$sql = "SELECT $selection FROM `".mysql_real_escape_string($table)."`";
		return $this->get_aggregate($sql, $assoc);
	}

	function get_by($table, $id, $fields = "", $order = "", $by = "", $limit = "",
			$distinct = false, $assoc = true)
	// Fetches one or more entries from a table based on a single column comparison.
	//
	// Expected format of $id is array("colname", "colvalue") for implicit equality,
	// or array("colname", "operator", "colvalue") for a custom comparison. Alternatively,
	// you can provide $id as a single variable to have it parsed directly.
	//
	// $fields is optional (defaults to *) and allows for similar selection of columns, in a
	// format of array("col1", "col2", ...). $order should be a field for ordering, $by
	// either ASC or DESC. $limit will restrict the number of valid entries that are
	// returned; $distinct for distinct entries. $assoc specifies associativity.
	{
		if (!$fields) // no fields, assume * for all
		{
			$selection = "*";
		}
		else if (!is_array($fields)) // one field, select it
		{
			$selection = "`".mysql_real_escape_string($fields)."`";
		}
		else // parse each field in the array
		{
			$selection = "";

			foreach($fields as $key => $value)
			{
				$selection .= "`".mysql_real_escape_string($value)."`, ";
			}

			// -2 to remove the comma and space at the end
			$selection = substr($selection, 0, strlen($selection)-2);
		}

		if ($distinct) $selection = "DISTINCT $selection";

		if (!$id) // no where clause
		{
			$sql = "SELECT $selection FROM `".mysql_real_escape_string($table)."`";
		}
		else if (!is_array($id)) // parse directly - make sure you escape it yourself!
		{
			$sql = "SELECT $selection FROM `".mysql_real_escape_string($table)."` WHERE $id";
		}
		else if (sizeof($id) > 2) // operator provided
		{
			$sql = "SELECT $selection FROM `".mysql_real_escape_string($table)."` ".
				"WHERE `".mysql_real_escape_string($id[0]).
				"` ".mysql_real_escape_string($id[1]).
				" '".mysql_real_escape_string($id[2])."'";
		}
		else // no operator, assume = for equality
		{
			$sql = "SELECT $selection FROM `".mysql_real_escape_string($table)."` ".
				"WHERE `".mysql_real_escape_string($id[0]).
				"` = '".mysql_real_escape_string($id[1])."'";
		}

		if ($order && $by) $sql .= " ORDER BY `".mysql_real_escape_string($order)."` $by";
		if ($limit) $sql .= " LIMIT ".mysql_real_escape_string($limit);

		return $this->get_aggregate($sql, $assoc);
	}

	function set_one($table, $entry, $id, $newid = "")
	// Sets one or more entries in the table based on a single column comparison.
	// $entry should be an associative array of the columns mapped to values for this
	// particular row, and $id should be name of the column being checked for equality.
	// 
	// If $newid is set, the row's $id will be rewritten with the value provided.
	{
		$sql = "UPDATE `".mysql_real_escape_string($table)."` SET";

		foreach($entry as $key => $value)
		{
			if ($key != $id)
			{
				$sql .= " `".mysql_real_escape_string($key)."` = ";
				if ($value === "" || $value == "NULL") $sql .= "NULL,";
				else $sql .= "'".mysql_real_escape_string($value)."',";
			}
		}

		// -1 to remove the extra comma
		$sql = substr($sql, 0, strlen($sql)-1)." WHERE `".mysql_real_escape_string($id).
			"` = '".mysql_real_escape_string($newid ? $newid : $entry[$id])."'";

		return $this->query($sql);
	}

	function add_one($table, $entry)
	// Inserts a row into the specified table. $entry is as above.
	{
		$sql = "INSERT INTO `".mysql_real_escape_string($table)."` (";

		foreach($entry as $key => $value)
		{
			$sql .= " `".mysql_real_escape_string($key)."`,";
		}

		// -1 to remove the extra comma
		$sql = substr($sql, 0, strlen($sql)-1)." ) VALUES (";

		foreach($entry as $key => $value)
		{
			if ($value === "" || $value == "NULL") $sql .= " NULL,";
			else $sql .= " '".mysql_real_escape_string($value)."',";
		}

		// -1 to remove the extra comma
		$sql = substr($sql, 0, strlen($sql)-1)." )";

		return $this->query($sql);
	}

	function delete_one($table, $entry, $id)
	// Removes a row from the specified table. $entry should be array($id => "colvalue"), or as above.
	{
		return $this->query("DELETE FROM $table WHERE `".mysql_real_escape_string($id).
			"` = '".mysql_real_escape_string($entry[$id])."'");
	}

	function delete_clip($table, $id, $value)
	// Remove row from $table with an $id of $value, and push all others back.
	// DO NOT attempt to use this function with a non-numeric $id.
	{
		if (!$this->delete_one($table, array($id => $value), $id)) return false;

		$entries = $this->get_by($table, array($id, ">", $value), $id, $id, "ASC");

		if (!$result)
		{
			return false;
		}
		else
		{
			$etable = mysql_real_escape_string($table);
			$eid = mysql_real_escape_string($id);

			$i = $value; for($j = 0; $j < sizeof($entries); $j++)
			{
				$this->query("UPDATE `$etable` SET `$eid` = '$i' WHERE `$eid` = ".($i+1));
				$i++;
			}

			$newentries = $this->get_all($table, "`$eid`");

			$this->query("ALTER TABLE `$etable` AUTO_INCREMENT = ".(sizeof($newentries) + 1));
		}
	}

	function swap_by($table, $id, $id1, $id2)
	// Swaps entries in the given table, where $id specifies the name of the id
	// column and $id1 and $id2 are valid ids for swapping.
	{
		$entry1 = $this->get_by($table, array($id, $id1));
		$entry2 = $this->get_by($table, array($id, $id2));

		// since an aggregate is returned with one entry, index to [0]
		// then perform swap in database

		$this->set_one($table, $entry1[0], $id, $id2);
		$this->set_one($table, $entry2[0], $id, $id1);
	}

	function swap_insert($table, $id, $value, $btw1, $btw2)
	// Reorder entries by placing row with $id of $value between rows $btw1 and $btw2.
	// This gets the job done, but is a bit inefficient with all the swap calls.
	{
		if ($i == $btw1 || $i == $btw2) return false; // nothing to do!

		if ($btw2 > $btw1) { $t = $btw1; $btw1 = $btw2; $btw2 = $t; } // need $btw1 < $btw2

		if ($value < $btw1)      for ($j = $value; $j < $btw1 - 1; $j++) $this->swap_by($table, $id, $j, $j+1);
		else if ($value > $btw2) for ($j = $value; $j > $btw2 + 1; $j--) $this->swap_by($table, $id, $j, $j-1);
	}
};

####################################################
##### Database Access with HTML Form Generation
####################################################

/*    A subclass of the database access class that includes the schema of the table
 *    it is currently working with. Also generates HTML forms to edit these contents.
 */

class k5dbschema extends k5db
{
	var $defined;

	var $table;
	var $names;
	var $reversenames;
	var $types;
	var $extra;
	var $primary;

	function k5dbschema($server = "", $user = "", $pass = "", $database = "")
	// Use the parent constructor to initialize the database connection.
	// If no information is provided, you need to create a separate k5db entity
	// to open the database connection, or else you will experience problems.
	{
		$this->k5db($server, $user, $pass, $database);
		$this->defined = false;
	}

	function define_schema($table, $names, $types, $extra, $primary)
	// Creates a local definition of the schema being used.
	//
	// The expected format of $names, $types, and $extra is just as an array of
	// array("col1", "col2", ...) information - first the column names, then their
	// database types, and finally any additional info (default values, etc). Their
	// dimensions MUST be identical. $primary specifies the name of the primary key.
	{
		if (sizeof($names) != sizeof($types) || sizeof($types) != sizeof($extra) ||
			!is_array($names) || !$primary)
		{
			trigger_error("Invalid schema definition.", E_USER_WARNING);
			return false;
		}

		$this->table = mysql_real_escape_string($table);
		$this->primary = mysql_real_escape_string($primary);

		if ($this->names) $this->names = NULL;
		if ($this->types) $this->types = NULL;
		if ($this->extra) $this->extra = NULL;
		if ($this->reversenames) $this->reversenames = NULL;

		for($i = 0; $i < sizeof($names); $i++)
		{
			$this->names[$i] = mysql_real_escape_string($names[$i]);
			$this->reversenames[mysql_real_escape_string($names[$i])] = $i;
			$this->types[$i] = mysql_real_escape_string($types[$i]);
			$this->extra[$i] = $extra[$i];
		}

		$this->defined = true;
	}

	function create_schema()
	// Creates the specified table structure as defined.
	{
		if (!$this->defined)
		{
			trigger_error("Schema not defined.", E_USER_WARNING);
			return false;
		}

		$sql = "CREATE TABLE IF NOT EXISTS `".$this->table."` (";

		for($i = 0; $i < sizeof($this->names); $i++)
		{
			$sql .= "`".$this->names[$i]."` ".$this->types[$i]." ".$this->extra[$i].", ";
		}

		$sql .= "PRIMARY KEY (`".$this->primary."`))";

		return $this->query($sql);
	}

	function drop_schema()
	// Removes the specified table as defined.
	{
		if (!$this->defined)
		{
			trigger_error("Schema not defined.", E_USER_WARNING);
			return false;
		}

		return $this->query("DROP TABLE IF EXISTS `".$this->table."`");
	}

	function generate_form($names = "", $defaults = "", $notes = "", $states = "", $header = "", $footer = "", $action = "")
	// Output a standardized form for editing the schema. You can specify which columns are
	// included via $names. If left blank or set to &, all columns in the current schema will
	// be used. $defaults specifies the default values that will be placed for each input.
	// Using $notes will annotate each input with a specified value. Each of these three variables
	// is expected as an array("field1", "field2", ...).
	//
	// An item in $states may be either "hidden" or "disabled" to trigger the appropriate behavior
	// on the form element. $header and $footer allow you to insert your own form code into the form,
	// and $action specifies where the form will be directed upon submission.
	//
	// You can add an id to any form element by providing $names[$i] = array("name", "id"). Note that
	// this behavior is only provided for completeness - it is not at all required.
	{
		if (!$this->defined)
		{
			trigger_error("Schema not defined.", E_USER_WARNING);
			return false;
		}

		if (!is_array($names) && is_array($defaults))
		{
			trigger_error("Improper form requested.", E_USER_WARNING);
			return false;
		}

		if (!$names || $names == "*") $names = $this->names;
?>

<form method="post" action="<?php echo $action ?>">

<?php
		echo $header;
		if ($notes) echo "\n<table>\n";

		for($i = 0; $i < sizeof($names); $i++)
		{
			if (is_array($names[$i]))
			{
				$add_id = " id=\"".$names[$i][1]."\"";
				$names[$i] = $names[$i][0];
			}
			else
			{
				$add_id = "";
			}

			$default = $defaults ? (isset($defaults[$names[$i]]) ? clean_html($defaults[$names[$i]]) : clean_html($defaults[$i])) : "";
			$state = $states ? (isset($states[$names[$i]]) ? clean_normal($states[$names[$i]]) : clean_normal($states[$i])) : "";
			$cur = $this->reversenames[mysql_real_escape_string($names[$i])];

			$curname = $this->names[$cur];
			if (!$curname) $curname = clean_normal($names[$i]); // support user defined extras

			$curtype = $this->types[$cur];
			$curnote = ($state == "hidden") ? "" : $notes[$i];

			if (strstr($curtype, "(1)") || $curtype == "bit") $curnote = ""; // for checkbox...

			echo ($notes) ? "<tr><td style=\"padding: 3px\"><p>$curnote</p></td>\n<td style=\"padding: 3px\"><p>"
					: "<p>";

			if ($state == "hidden") // overwrite behavior with hidden type
			{
				echo "<input type=\"hidden\" name=\"$curname\"$add_id value=\"$default\" />";
			}
			else if (strstr($curtype, "(1)") || $curtype == "bit") // checkbox - switch the notes around
			{
				echo "<input type=\"checkbox\" name=\"$curname\"$add_id ".($default ? "checked=\"checked\" " : "");
				echo ($state == "disabled" ? "disabled=\"disabled\" " : "")."/>";
				if ($notes) echo " &nbsp; ".$notes[$i];
			}
			else if ($curtype != "tinytext" && strstr($curtype, "text")) // textarea
			{
				echo "<textarea name=\"$curname\"$add_id ";

				if ($curtype == "text") echo "rows=\"2\" cols=\"40\"";
				if ($curtype == "mediumtext") echo "rows=\"8\" cols=\"50\"";
				if ($curtype == "longtext") echo "rows=\"20\" cols=\"70\"";

				echo ($state == "disabled" ? " disabled=\"disabled\"" : "").">$default</textarea>";
			}
			else // text field (default)
			{
				echo "<input type=\"text\" name=\"$curname\"$add_id value=\"$default\" size=\"40\" ";
				echo ($state == "disabled" ? "disabled=\"disabled\" " : "")."/>";
			}

			echo ($notes) ? "</p></td></tr>\n" : "</p>\n";
		}

		if ($notes) echo "\n</table>\n";
		echo $footer;
?>

<p><br />
<input type="submit" name="form_action" value="submit form" />
<input type="reset" name="form_action" value="revert" />
</p>

</form>

<?php
	}

	function gather_form()
	// Retrieves $_POST data for the given schema and returns an associative array of
	// anything matching that schema.
	{
		if (!$this->defined)
		{
			trigger_error("Schema not defined.", E_USER_WARNING);
			return false;
		}

		for($i = 0; $i < sizeof($this->names); $i++)
		{
			if (isset($_POST[$this->names[$i]])) $entry[$this->names[$i]] = $_POST[$this->names[$i]];
		}

		return $entry;
	}

	function validate_form($required = "")
	// Verify that all required elements have been provided in a form submission. Set
	// $required to be array("col1", "col2", ...) for items you want verified. If one of
	// these is missing, this function returns false.
	{
		for($i = 0; $required && $i < sizeof($required); $i++)
		{
			if (!$_POST[$required[$i]]) return false;
		}
		
		return $this->gather_form();
	}

	function reuse_inputs()
	// Pass the return of this function into $defaults of generate_form() to repopulate the form
	// with the previous submission. You can also pass your own argument to dissociate the array.
	{
		$inputs = $this->gather_form();

		for($i = 0; $i < sizeof($this->names); $i++) // essentially array_values(), but with blanks
		{
			$entry[$i] = $inputs[$this->names[$i]];
		}

		return $entry;
	}
};

##### End PHP code, (c) 2006 kaulana.com

?>