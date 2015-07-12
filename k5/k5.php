<?php

##### K5.PHP

##### Powers the kaulana.com weblog, revision five.
##### Including this file defines all site functionality in a concise set of classes.

require_once('k5base.php');
require_once('k5util.php');
require_once('k5auth.php');
require_once('k5search.php');

####################################################
##### Definitions
####################################################

/*    These constants will hold your database access information.
 *    Thus, you'll want to fill them in!
 */

define('K5DB_HOST', '*** FILL ME IN ***');
define('K5DB_USER', '*** FILL ME IN ***');
define('K5DB_PASS', '*** FILL ME IN ***');
define('K5DB_DB',   '*** FILL ME IN ***');

/*    These table names are used to store information in the database.
 *    Feel free to edit their names, so long as you don't change the constants.
 */

define('U_TABLE', 'k5_users');
define('B_TABLE', 'k5_banned');
define('W_TABLE', 'k5_writings');
define('C_TABLE', 'k5_comments');
define('L_TABLE', 'k5_links');

####################################################
##### Master Class
####################################################

/*    Using this class gives you most of the useful functionality of these scripts
 *    at your fingertips. Many examples for use are provided in the application
 *    scripts (any PHP files not starting with k5).
 */

class k5
{
	var $table;
	var $title;
	var $html;
	var $auth;
	var $admin;
	var $db;

	/*    Generic API constructor
	 *
	 *    Calling the below constructor in AUTO mode (pass in the string "AUTO" to $table) will
	 *    allow you to use the generic API, listed at the bottom of this class, with a minimum
	 *    of fuss. All you have to do is create a k5 object (the $table is not important if you
	 *    are not accessing the database). See k5test.php for sample usage.
	 */

	function k5($title = "", $table = "")
	// General constructor sets up everything necessary for each section
	// if the $auto parameter is set to true.
	{
		$this->table = $table;
		$this->title = $title;
		$this->html = NULL;
		$this->auth = NULL;
		$this->admin = NULL;
		$this->db = NULL;

		if ($this->table == "AUTO") $this->auto_start();
	}

	function auto_start()
	// Perform an initialization, then start HTML generation.
	{
		$this->auto_initialize();
		$this->go_to_main();
	}

	function auto_initialize()
	// Initialize the page automatically with the given parameters.
	{
		$this->initialize();
		$this->html_open();
		$this->html->html_openid($this->auth);
	}

	function initialize()
	// Only do the minimum necessary to authorize the current user.
	{
		$this->db_open();
		$this->check_ban();
		$this->authorize();
	}

	function db_open()
	// Open the database and ready the schema for the specified table (if any).
	{
		if (!$this->db)
		{
			if (!$this->table || $this->table == "AUTO")
			{
				$this->db = new k5db(K5DB_HOST, K5DB_USER, K5DB_PASS, K5DB_DB);
			}
			else
			{
				$this->db = new k5dbschema(K5DB_HOST, K5DB_USER, K5DB_PASS, K5DB_DB);
				list($names, $types, $extra, $primary) = k5masterschema::get_schema($this->table);
				$this->db->define_schema($this->table, $names, $types, $extra, $primary);
			}
		}
	}

	function check_ban()
	// Check the ban list against the current user.
	{
		if ($this->db)
		{
			$entries = $this->db->get_all(B_TABLE);
			for($i = 0; $i < sizeof($entries); $i++)
			{
				$ban = stristr($_SERVER["REMOTE_ADDR"], $entries[$i]["ip"]);

				if ($ban == $_SERVER["REMOTE_ADDR"]) // only match from start of string
				{
					$this->apply_ban($entries[$i]);
				}
			}
		}
	}

	function apply_ban($entry)
	// Force a premature exit.
	{
		$entries = $this->db->get_by(U_TABLE, array("uid", $entry["uid"]));

		$reason = $entry["reason"] ? $entry["reason"] : "your ip address has been logged on the site black list.";
		$by = sizeof($entries) > 0 && $entries[0]["name"] ? "by ".$entries[0]["name"]." " : "";

		$this->html = new k5html(true, "access denied");
		$this->html->content("main");

		echo "<h1>access denied</h1>\n\n";
		echo "<p>\nyour access to this site has been denied $by";
		echo "for the following reason:\n<br />\n<br />\n";
		echo "$reason\n<br />\n<br />\n";
		echo "if you feel that you have reached this message in error, ";
		echo "you are free to follow up with the webmaster of this site.";
		echo "\n</p>";

		exit;
	}

	function authorize()
	// Authenticate, then authorize the current user.
	{
		if (k5admin::$authorized) return true; // skip it - we're good
		if (!$this->db) return false;          // forget it - not ready yet

		if (!$this->auth) $this->auth = new k5auth();
		$this->auth->authenticate();

		if (!$this->admin) $this->admin = new k5admin($this->db);
		$this->admin->authorize();
	}

	function html_open()
	// Prepare for HTML output.
	{
		if (!$this->html) $this->html = new k5htmlstandard(true, $this->title);
	}

	/*    Generic API
	 *
	 *    Call the above constructor in AUTO mode, and you can use the following functions without
	 *    worrying about setting up the rest of the system. This is really all you need.
	 */

	function go_to_main()       { $this->html_open(); if ($this->html) $this->html->content("main");    }
	function go_to_sidebar()    { $this->html_open(); if ($this->html) $this->html->content("sidebar"); }
	function set_page_title($t) { $this->html_open(); $this->title = $t; $this->html->add_title($t);    }

	function fetch_openid()      { return $_SESSION["openid_url"]; }
	function make_openid_login() { $this->auth->generate_form();   }
	function is_logged_in()      { return $this->fetch_openid();   }
	function is_site_user()      { return $this->is_logged_in() && $_SESSION["uid"] != 0; }

	function can_add_user()     { return $this->admin->get_permission("add_user");     }
	function can_add_writing()  { return $this->admin->get_permission("add_writing");  }
	function can_add_comment()  { return $this->admin->get_permission("add_comment");  }
	function can_add_link()     { return $this->admin->get_permission("add_link");     }
	function can_edit_user()    { return $this->admin->get_permission("edit_user");    }
	function can_edit_writing() { return $this->admin->get_permission("edit_writing"); }
	function can_edit_comment() { return $this->admin->get_permission("edit_comment"); }
	function can_edit_link()    { return $this->admin->get_permission("edit_link");    }

	/*    Additional API functions
	 *
	 *    It's a good idea to browse through some sample code before using these functions.
	 */

	function standard_error($error)
	// Generate a standard error page for the main script.
	{
		$this->html_open();
		if ($this->html->state != "main") $this->html->content("main");

		echo "<h1>you're out of luck</h1>\n";
		echo "<p>your request was denied. <span class='atten'>$error</span>\n<br />\n<br />\n";
		echo "please <a href=\"#\" onclick=\"history.go(-1); return false\">go back</a> and correct the problem before continuing on.</p>\n";
		echo "<br />\n";

		$this->html->content_close();
	}

	function exit_standard_error($error)
	// Generate a standard error page, then halt the script.
	{
		$this->standard_error($error);
		exit;
	}

	function standard_sidebar_error($error)
	// Generate an error message for sidebar scripts.
	{
		echo "<h2>you're out of luck</h2>\n";
		echo "<p>your request was denied. <span class='atten'>$error</span>\n<br />\n<br />\n";
		echo "please <a href=\"#\" onclick=\"history.go(-1); return false\">go back</a> and correct the problem before continuing on.</p>\n";
	}

	function w_table_wid_to_url($wid)
	// Use to generate nice URLs for entries into the W_TABLE.
	{
		$entries = $this->db->get_by(W_TABLE, array("wid", $wid), array("dateof", "title"));

		if (sizeof($entries) == 0) return PATH_URL.(NICE_URL ? '/writings/' : '/writings.php'); // not found
		$d = new k5date($entries[0]["dateof"]);

		if (NICE_URL)
		{
			$url = PATH_URL.'/writings/'.$d->get_yyyy().'/'.$d->get_mm().'/'.$d->get_dd().'/';
			$url .= clean_for_url($entries[0]["title"]).'/';
			return $url;
		}
		else
		{
			return PATH_URL.'/writings.php?wid='.$entries[0]["wid"];
		}
	}

	function w_table_wid_gather_url()
	// Performs the inverse of the above function, inferring from $_GET parameters.
	{
		if (NICE_URL)
		{
			if (!$this->db) return "0"; // can't check the database yet

			$date = $_GET["yyyy"]."-".$_GET["mm"]."-".$_GET["dd"];
			$title = explode("-", clean_normal($_GET["title"]));

			$sql = "SELECT `wid` FROM `".W_TABLE."` WHERE `dateof` LIKE '$date%'";
			for($i = 0; $i < sizeof($title); $i++) $sql .= " AND `title` LIKE '%".mysql_real_escape_string($title[$i])."%'";

			$entries = $this->db->get_aggregate($sql, true);

			return (sizeof($entries) == 1) ? $entries[0]["wid"] : "0";
		}
		else
		{
			return isset($_GET["wid"]) && is_numeric($_GET["wid"]) ? $_GET["wid"] : "0";
		}
	}
};

####################################################
##### HTML Extension
####################################################

/*    Overwrites the general HTML closer to add standardized features to the layout.
 *    You can overwrite the close_standard() function to achieve a similar effect.
 */

class k5htmlstandard extends k5html
{
	var $auth;

	function k5htmlstandard($ob = true, $title = "")
	{
		$this->k5html($ob, $title, "close_standard");
		$this->auth = NULL;
	}

	function html_openid($a) { $this->auth = $a; }

	function close_standard()
	// Add the header links and OpenID information if available.
	{
		if (self::$defined_foot) return; // prevent extra closer generations

		$this->content("navi"); // add link bar

		echo "<p>\n";

		$w_current = stristr($_SERVER["SCRIPT_URI"], "writings") || stristr($_SERVER["SCRIPT_URI"], "tags") ? " current" : "";
		$c_current = stristr($_SERVER["SCRIPT_URI"], "comments") ? " current" : "";
		$u_current = stristr($_SERVER["SCRIPT_URI"], "about")    ? " current" : "";
		$a_current = stristr($_SERVER["SCRIPT_URI"], "admin")    ? " current" : "";

		if (isset($_SESSION["uid"]) && $_SESSION["uid"] != 0) // extra administration
		{
			echo "<a class=\"special navi$a_current\" href=\"".PATH_URL.(NICE_URL ? '/admin/' : '/admin.php')."\">admin</a> &nbsp; ";
		}

		echo "<a class=\"navi$w_current\" href=\"".PATH_URL.(NICE_URL ? '/writings/' : '/writings.php')."\">writings</a> &nbsp; ";
		echo "<a class=\"navi$c_current\" href=\"".PATH_URL.(NICE_URL ? '/comments/' : '/comments.php')."\">comments</a> &nbsp; ";
		echo "<a class=\"navi$u_current\" href=\"".PATH_URL.(NICE_URL ? '/about/'    : '/about.php')."\">about</a> &nbsp; ";

		echo "\n</p>";

		if ($this->auth) // add openid information
		{
			$this->content("openid");
			$this->auth->generate_identity();
		}

		$this->close(); // call general closer
	}
};

####################################################
##### Authorization Extension
####################################################

/*    Allows you to validate user permissions after OpenID authentication.
 *    To be useful, you should connect to the database before using this class.
 */

class k5admin
{
	var $database;
	static $authorized;

	function k5admin($d) { $this->database = $d; }

	function authorize()
	// Attempt to recognize a user. Returns false if authentication has not
	// yet taken place, and true if it has. A list of permissions will also
	// be populated inside of the current session if the return is true.
	{
		if (self::$authorized)        return true;  // already done this round
		if (isset($_SESSION["uid"]))                // already done last round
		{
			self::$authorized = true; return true;

			// there is a subtle feature with this clause - if another user changes
			// your permission levels, they won't take effect until you close and restart
			// your session - this is by design to prevent "admin wars"
		}

		if (!k5db::$handle)           return false; // no database connection
		if (!k5auth::$ready)          return false; // no session information
		if (!$_SESSION["openid_url"]) return false; // no openid authentication

		$entries = $this->database->get_by(U_TABLE, array("openid_url", $_SESSION["openid_url"]));

		if (sizeof($entries) == 0) // you are not recognized
		{
			$_SESSION["name"]         = "guest";
			$_SESSION["uid"]          = 0;
			$_SESSION["add_user"]     = 0;
			$_SESSION["edit_user"]    = 0;
			$_SESSION["add_writing"]  = 0;
			$_SESSION["edit_writing"] = 0;
			$_SESSION["add_comment"]  = 1; // can only comment
			$_SESSION["edit_comment"] = 0;
			$_SESSION["add_link"]     = 0;
			$_SESSION["edit_link"]    = 0;
		}
		else // take the first match, copy credentials
		{
			$_SESSION["name"]         = $entries[0]["name"];
			$_SESSION["uid"]          = $entries[0]["uid"];
			$_SESSION["add_user"]     = $entries[0]["add_user"];
			$_SESSION["edit_user"]    = $entries[0]["edit_user"];
			$_SESSION["add_writing"]  = $entries[0]["add_writing"];
			$_SESSION["edit_writing"] = $entries[0]["edit_writing"];
			$_SESSION["add_comment"]  = $entries[0]["add_comment"];
			$_SESSION["edit_comment"] = $entries[0]["edit_comment"];
			$_SESSION["add_link"]     = $entries[0]["add_link"];
			$_SESSION["edit_link"]    = $entries[0]["edit_link"];
		}

		self::$authorized = true;
		return true;
	}

	function get_permission($str)
	// Check the active session for the specified user permissions.
	{
		return (self::$authorized) ? $_SESSION[$str] : false;
	}
};

####################################################
##### Master Schema Reference
####################################################

/*    All table definitions are in the static class below. Feel free to add
 *    additional columns, but don't delete or rename any of the existing ones.
 */

class k5masterschema
{
	public static function get_schema($table)
	// Pass the four arguments in the returned array off to define_schema().
	{
		switch ($table)
		{
			case U_TABLE: return array(self::$u_names, self::$u_types, self::$u_extra, self::$u_primary);
			case B_TABLE: return array(self::$b_names, self::$b_types, self::$b_extra, self::$b_primary);
			case W_TABLE: return array(self::$w_names, self::$w_types, self::$w_extra, self::$w_primary);
			case C_TABLE: return array(self::$c_names, self::$c_types, self::$c_extra, self::$c_primary);
			case L_TABLE: return array(self::$l_names, self::$l_types, self::$l_extra, self::$l_primary);
			default: return array();
		}
	}

	public static function get_form_data($table)
	// Pass the two arguments in the return array off to generate_form() after schema definition.
	// You still need to supply your own default values, though, when calling said function.
	{
		switch ($table)
		{
			case U_TABLE: return array(self::$u_names, self::$u_bit_notes);
			case B_TABLE: return array(self::$b_names, self::$b_bit_notes);
			case W_TABLE: return array(self::$w_names, self::$w_bit_notes);
			case C_TABLE: return array(self::$c_names, self::$c_bit_notes);
			case L_TABLE: return array(self::$l_names, self::$l_bit_notes);
			default: return array();
		}
	}

	public static $u_names = array("uid", "openid_url", "name", "announce", "scratchpad", "add_user", "edit_user", "add_writing", "edit_writing", "add_comment", "edit_comment", "add_link", "edit_link", "bio");
	public static $u_types = array("int", "tinytext", "tinytext", "text", "mediumtext", "varchar(1)", "varchar(1)", "varchar(1)", "varchar(1)", "varchar(1)", "varchar(1)", "varchar(1)", "varchar(1)", "mediumtext");
	public static $u_extra = array("NOT NULL auto_increment", "NOT NULL", "NOT NULL", "", "", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0", "");
	public static $u_bit_notes = array("", "", "", "", "", "allow this person to create new users", "allow this person to edit existing users (overwrites all)", "allow this person to post writings", "allow this person to edit or delete any stored writings", "allow this person to add comments to a writing", "allow this person to edit or delete any comments", "allow this person to add site links", "allow this person to edit or delete site links", "");
	public static $u_primary = "uid";

	public static $b_names = array("bid", "uid", "ip", "reason", "dateof");
	public static $b_types = array("int", "int", "tinytext", "text", "timestamp");
	public static $b_extra = array("NOT NULL auto_increment", "NOT NULL DEFAULT 0", "NOT NULL", "NOT NULL", "NOT NULL");
	public static $b_bit_notes = array("", "", "", "", "");
	public static $b_primary = "bid";

	public static $w_names = array("wid", "uid", "title", "body", "tags", "dateof", "public", "commentable");
	public static $w_types = array("int", "int", "tinytext", "longtext", "text", "timestamp", "varchar(1)", "varchar(1)");
	public static $w_extra = array("NOT NULL auto_increment", "NOT NULL DEFAULT 0", "", "", "", "NOT NULL", "NOT NULL DEFAULT 0", "NOT NULL DEFAULT 0");
	public static $w_bit_notes = array("", "", "", "", "", "", "", "allow the public to view this entry", "allow the public to comment on this entry");
	public static $w_primary = "wid";

	public static $c_names = array("cid", "wid", "openid_url", "name", "link", "body", "dateof", "ip");
	public static $c_types = array("int", "int", "tinytext", "tinytext", "tinytext", "mediumtext", "timestamp", "tinytext");
	public static $c_extra = array("NOT NULL auto_increment", "NOT NULL DEFAULT 0", "", "NOT NULL", "", "NOT NULL", "NOT NULL", "");
	public static $c_bit_notes = array("", "", "", "", "", "", "", "");
	public static $c_primary = "cid";

	public static $l_names = array("lid", "uid", "url", "name", "description", "xfn");
	public static $l_types = array("int", "int", "tinytext", "tinytext", "tinytext", "tinytext");
	public static $l_extra = array("NOT NULL auto_increment", "NOT NULL DEFAULT 0", "NOT NULL", "NOT NULL", "", "");
	public static $l_bit_notes = array("", "", "", "", "", "");
	public static $l_primary = "lid";
};

##### End PHP code, (c) 2006 kaulana.com

?>