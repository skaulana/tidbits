<?php

##### WRITINGS.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page controls the creation and display of of all writings on the site.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$k5 = new k5("", W_TABLE);
$k5->auto_initialize();

function year_spell($year)
// Returns the spelled out version of the year given in $year.
{
	$digits = array("", " one", " two", " three", " four", " five", " six", " seven", " eight", " nine", " ten");

	if ($year < 2000) return "nineteen ninety-nine";
	else return "two thousand".$digits[$year-2000];
}

define('TAG_IMAGE', PATH_URL.'/layout/tag.jpg');
define('FINAL_YEAR', 2006);

####################################################
##### Private Application
####################################################

/*    Handle any interaction with the comments form.
 */

include('comments_apply.php');

/*    Now deal with our own forms.
 */

if ($_POST["form_action"] && !isset($_POST["links"]))
{
	/*    First check for possible errors in the request.
	 */

	$action = clean_normal($_POST["form_action"]);

	if (!$k5->is_logged_in()) $error = "you are not logged in.";
	else if (!$k5->is_site_user()) $error = "you are not authorized to use this script.";
	else if (!$k5->can_add_writing() && stristr($action, "write something new")) $error = "you are not allowed to write for this site.";
	else if (!$k5->can_edit_writing())
	{
		$counterentries = $k5->db->get_by(W_TABLE, array("wid", $_POST["wid"]));
		if ($counterentries[0]["uid"] != $_SESSION["uid"]) $error = "you are not allowed to modify this writing.";
	}

	/*    Handle errors if any, then proceed with appropriate action.
	 */

	if ($error) { $k5->html->add_title("deaf ears"); $k5->go_to_main(); $k5->exit_standard_error($error); }
	else
	{
		if (stristr($action, "write something new")) // create a new writing
		{
			$k5->html->add_title("words to be crafted");
			$k5->go_to_main();

			echo "<h1>pen and paper</h1>\n";
			echo "<p>type away. if you decide you don't want to write anything, then please ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.</p>\n<br />\n";

			// body will get the word count id added to it

			$names = array("title", "body", "tags", "public", "commentable");
			$defaults = array("writing title goes here", "", "add tags here", true, true);
			$notes = array("", "", "", "anyone can see this entry", "enable comments on this entry");

			$header = "<p><input type=\"checkbox\" name=\"send_pings\" checked=\"checked\" /> &nbsp; ";
			$header .= "when finished, ping various weblog services about update</p>\n<br />\n";

			$k5->db->generate_form($names, $defaults, $notes, NULL, $header); echo "\n<br />\n";

			$k5->go_to_sidebar();

			echo "<h2>filing cabinet</h2>\n";
			echo "<p>for your reference, here is a list of all previously written titles.</p>\n<br />\n";

			$entries = $k5->db->get_by(W_TABLE, NULL, "title", "title", "ASC");

			echo "<ul>\n";
			for($i = 0; $i < sizeof($entries); $i++)
			{
				echo "<li>".$entries[$i]["title"]."</li>\n";
			}
			echo "</ul>\n<br />\n";

			exit;
		}
		else if (stristr($action, "edit this writing")) // general edit form
		{
			$k5->html->add_title("words to be reshaped");
			$k5->go_to_main();

			$entries = $k5->db->get_by(W_TABLE, array("wid", $_POST["wid"]));
			if (sizeof($entries) == 0) $k5->exit_standard_error("the specified writing was not found.");
			else $d = new k5date($entries[0]["dateof"]);

			echo "<h1>pen and paper</h1>\n";
			echo "<p>type away. if you decide you don't want to make any changes, then please ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.</p>\n<br />\n";

			$names = array("wid", "title", "body", "tags", "dateof", "public", "commentable");
			$defaults = array($entries[0]["wid"], $entries[0]["title"], $entries[0]["body"], $entries[0]["tags"], $d->get_human(), $entries[0]["public"], $entries[0]["commentable"]);
			$notes = array("", "", "", "", "", "anyone can see this entry", "enable comments on this entry");
			$states = array("hidden", "", "", "", "", "", "");

			$header = "<p><input type=\"checkbox\" name=\"send_pings\" /> &nbsp; ";
			$header .= "when finished, ping various weblog services about update</p>\n<br />\n";

			$k5->db->generate_form($names, $defaults, $notes, $states, $header); echo "\n<br />\n";

			$k5->go_to_sidebar();

			echo "<h2>filing cabinet</h2>\n";
			echo "<p>for your reference, here is a list of all previously written titles.</p>\n<br />\n";

			$entries = $k5->db->get_by(W_TABLE, NULL, "title", "title", "ASC");

			echo "<ul>\n";
			for($i = 0; $i < sizeof($entries); $i++)
			{
				echo "<li>".$entries[$i]["title"]."</li>\n";
			}
			echo "</ul>\n<br />\n";

			exit;
		}
		else if (stristr($action, "delete this writing")) // writing removal (confirmation)
		{
			$k5->html->add_title("words to be unwritten");
			$k5->go_to_main();

			echo "<h1>turn down the volume</h1>\n";
			echo "<p>you are about to remove the following writing. please keep in mind that doing so ";
			echo "is both permanent and irreversible. additionally, any comments that reference this ";
			echo "writing will be orphaned.\n<br />\n<br />\nif you do not wish to remove the writing, ";
			echo "please <a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n<br />\n<br />\n";			

			$entries = $k5->db->get_by(W_TABLE, array("wid", $_POST["wid"]));
			if (sizeof($entries) == 0) $k5->exit_standard_error("the specified writing was not found.");
			else $d = new k5date($entries[0]["dateof"]);

			echo "<h1>".$entries[0]["title"]."</h1>\n";
			echo "<p>posted ".$d->get_human()."</p>\n<br />\n";

			echo "<p>\n";
			echo parse_parens(str_replace("\n", "\n<br />", $entries[0]["body"]));
			echo "\n</p><br />\n";

			echo "<form method=\"post\" action=\"\">\n<p style=\"text-align: center\">\n";
			echo "<input type=\"hidden\" name=\"wid\" value=\"".$entries[0]["wid"]."\" />\n";
			echo "<input type=\"submit\" name=\"form_action\" value=\"confirm deletion of this writing\" />\n";
			echo "\n</p>\n</form>\n<br />\n";

			exit;
		}
		else if (stristr($action, "confirm deletion")) // writing removal
		{
			$wid = $_POST["wid"];

			$k5->db->delete_one(W_TABLE, array("wid" => $wid), "wid");

			$comment_entries = $k5->db->get_by(C_TABLE, array("wid", $wid));

			for($i = 0; $i < sizeof($comment_entries); $i++)
			{
				$entry["cid"] = $comment_entries[0]["cid"];
				$entry["wid"] = "0";

				$k5->db->set_one(C_TABLE, $entry, "cid");
			}

			header("Location: http://".$_SERVER["HTTP_HOST"].PATH_URL.(NICE_URL ? "/writings/" : "/writings.php"));
			exit;
		}
		else if (stristr($action, "submit")) // general save handler
		{
			$entry = $k5->db->validate_form(array("title", "body"));

			if (!$entry)
			{
	 			$k5->html->add_title("words not yet finished");
				$k5->go_to_main();
				$k5->exit_standard_error("you must specify all the required fields (marked with a *) before continuing.");
			}
			else
			{
				$entry["public"] = isset($entry["public"]) && $entry["public"] ? "1" : "0";
				$entry["commentable"] = isset($entry["commentable"]) && $entry["commentable"] ? "1" : "0";
				$entry["uid"] = $_SESSION["uid"];
			}

			if ($entry["wid"]) // update existing
			{
				$d = new k5date(); $d->set_human($entry["dateof"]); $entry["dateof"] = $d->get_sql();
				$k5->db->set_one(W_TABLE, $entry, "wid");
			}
			else // create new
			{
				$entry["dateof"] = ""; // so we get the current time
				$k5->db->add_one(W_TABLE, $entry);
			}
		}
	}
}

####################################################
##### Public Application
####################################################

$rss_title = "fresh entries feed";
$rss_link = PATH_URL.(NICE_URL ? '/rss/writings/' : '/rss.php?writings');

$k5->html->add_rss($rss_title, $rss_link);

if (isset($_GET["archives"]))
{
	$year = $_GET["archives"];

	/*    Display archives for the given year.
	 */

	$k5->html->add_title(year_spell($year));
	$k5->go_to_main();

	if (!is_numeric($year) || $year < FINAL_YEAR || $year > date("Y")) $k5->standard_error("you provided an invalid archive year.");
	else
	{
		$prev = $year != FINAL_YEAR; $next = $year != date("Y");

		$open_url  = PATH_URL.(NICE_URL ? '/writings/' : '/writings.php?archives=');
		$close_url = (NICE_URL ? '/' : '');

		echo "<div style=\"float: right\">\n<p style=\"text-align: right\">\n";
		if ($prev) echo "<a href=\"$open_url".($year-1)."$close_url\">previous</a>";
		if ($prev && $next) echo " -- ";
		if ($next) echo "<a href=\"$open_url".($year+1)."$close_url\">next</a>";
		echo "\n</p>\n</div>\n";

		echo "<h1>".year_spell($year)."</h1>";
		echo "<p>if you'd rather browse by topic, try ";
		echo "<a href=\"".PATH_URL.(NICE_URL ? '/tags/' : '/tags.php')."\">the tag wall</a>.</p>\n<br />\n";

		/*    Separate the year's entries per month.
		 */

		$entries = $k5->db->get_by(W_TABLE, "DATE_FORMAT(`dateof`, '%Y') = '".mysql_real_escape_string($year)."' AND `public` = '1'", array("wid", "title", "dateof"), "dateof", "DESC");
		$curmonth = "";

		for($i = 0; $i < sizeof($entries); $i++)
		{
			$d = new k5date($entries[$i]["dateof"]);

			if ($d->get_month() != $curmonth)
			{
				if ($curmonth) echo "<br />\n";
				echo "<h1>".strtolower($d->get_month())."</h1>\n";
			}

			echo "<p><a href=\"".$k5->w_table_wid_to_url($entries[$i]["wid"])."\">";
			echo $entries[$i]["title"]."</a></p>\n";

			$curmonth = $d->get_month();
		}
		echo "<br />\n";
	}
}

else
{
	$wid = $k5->w_table_wid_gather_url();
	$entries = $k5->db->get_by(W_TABLE, array("wid", $wid));

	if (sizeof($entries) != 1) // get most recent writing
	{
		$entries = $k5->db->get_by(W_TABLE, array("public", "1"), NULL, "dateof", "DESC", "1");

		if (!$entries) // sample data
		{
			$dd = new k5date();
			$entries[0] = array("wid" => "0", "uid" => "1", "title" => "default entry",
				"body" => "welcome to k5! this placeholder message will disappear once you post your first entry. to do this, click the \"write something new\" button.",
				"dateof" => $dd->get_sql(), "tags" => "", "public" => "1", "commentable" => "0");
		}
		else
		{
			$wid = $entries[0]["wid"]; $show_announcements = true;
		}
	}
	else if (!$entries[0]["public"] && !$k5->can_edit_writing() && $entries[0]["uid"] != $_SESSION["uid"])
	{
		$k5->html->add_title("thundering silence"); $k5->go_to_main();
		$k5->exit_standard_error("the writing you are trying to view is not accessible to the public.");
	}

	$d = new k5date($entries[0]["dateof"]);

	/*    Calculate forward and backward display links.
	 */

	$prev_entries = $k5->db->get_by(W_TABLE, "`dateof` < '".mysql_real_escape_string($entries[0]["dateof"])."' AND `public` = '1'", NULL, "dateof", "DESC", "1");
	$next_entries = $k5->db->get_by(W_TABLE, "`dateof` > '".mysql_real_escape_string($entries[0]["dateof"])."' AND `public` = '1'", NULL, "dateof", "ASC", "1");

	$prev = sizeof($prev_entries) != 0; $next = sizeof($next_entries) != 0;

	/*    Display currently selected writing.
	 */

	$k5->html->add_title($entries[0]["title"]);
	$k5->go_to_main();

	/*    Apply pings script if requested.
	 */

	if (isset($_POST["send_pings"]) && $_POST["send_pings"])
	{
		include('writings_ping.php');
	}

	/*    Display any available announcements.
	 */

	if ($show_announcements)
	{
		$user_entries = $k5->db->get_by(U_TABLE, array("announce", "!=", ""), array("announce", "name"));

		for($i = 0; $i < sizeof($user_entries); $i++)
		{
			echo "<blockquote>\n<p>\n";
			echo $user_entries[0]["announce"]." <span class='paren'>(-- ".$user_entries[0]["name"];
			echo ")</span>\n</p>\n</blockquote>\n<br />\n";
		}
	}

	echo "<div style=\"float: right\">\n<p style=\"text-align: right\">\n";
	if ($prev) echo "<a href=\"".$k5->w_table_wid_to_url($prev_entries[0]["wid"])."\">previous</a>";
	if ($prev && $next) echo " -- ";
	if ($next) echo "<a href=\"".$k5->w_table_wid_to_url($next_entries[0]["wid"])."\">next</a>";
	echo "\n</p>\n</div>\n";

	echo "<h1 title=\"posted ".$d->get_human()."\">".strtolower($d->get_spell())."</h1>\n";

	if ($k5->can_add_writing() || $k5->can_edit_writing() || $entries[0]["uid"] == $_SESSION["uid"]) // can edit
	{
		echo "<form method=\"post\" action=\"\">\n<p>\n";
		echo "<input type=\"hidden\" name=\"wid\" value=\"$wid\" />\n";

		if ($k5->can_add_writing())
		{
			echo "<input type=\"submit\" name=\"form_action\" value=\"write something new\" />\n";
		}
		if ($k5->can_edit_writing() || $entries[0]["uid"] == $_SESSION["uid"])
		{
			echo "<input type=\"submit\" name=\"form_action\" value=\"edit this writing\" />\n";
			echo "<input type=\"submit\" name=\"form_action\" value=\"delete this writing\" />\n";
			echo "<input type=\"submit\" name=\"send_pings\" value=\"send pings\" class=\"paren\" />\n";
		}
		echo "\n</p>\n</form>\n<br />\n";
	}

	/*    Actual writing display.
	 */

	echo "<!-- writing #".$entries[0]["wid"]." -->\n<p>";
	$body = $entries[0]["body"];

	if (isset($_GET["highlight"])) // include any highlighting from search results
	{
		$k5search = new k5search($k5->db);
		$highlights = $k5search->get_keywords(urldecode($_GET["highlight"]));

		for($i = 0; $i < sizeof($highlights); $i++)
		{
			$highlights[$i] = stripslashes($highlights[$i]);
			$body = str_replace($highlights[$i], "<span class='highlight'>".$highlights[$i]."</span>", $body);
		}
	}

	echo parse_parens(str_replace("\n", "\n<br />", $body));
	echo "\n</p><br />\n";

	echo "<div style=\"float: right\">\n<p style=\"text-align: right\">\n";
	if ($prev) echo "<a href=\"".$k5->w_table_wid_to_url($prev_entries[0]["wid"])."\">previous</a>";
	if ($prev && $next) echo " -- ";
	if ($next) echo "<a href=\"".$k5->w_table_wid_to_url($next_entries[0]["wid"])."\">next</a>";
	echo "\n</p>\n</div>\n";

	/*    Add the author's name.
	 */

	$user_entries = $k5->db->get_by(U_TABLE, array("uid", $entries[0]["uid"]));

	echo parse_parens("<p>(-- ".(sizeof($user_entries) != 0 ? $user_entries[0]["name"] : "anonymous").")</p>\n<br />\n");

	$open_url = PATH_URL.(NICE_URL ? '/tags/' : '/tags.php?tag=');
	$close_url = (NICE_URL ? '/' : '');

	/*    Add tags if present.
	 */

	if ($entries[0]["tags"])
	{
		$k5search = new k5search($k5->db);
		$tags = $k5search->get_keywords($entries[0]["tags"]);

		echo "<p><a href=\"".PATH_URL.(NICE_URL ? '/tags/' : '/tags.php')."\" class=\"imagelink\">";
		echo "<img alt=\"view all available tags\" title=\"view all available tags\" style=\"vertical-align: middle\" src=\"".TAG_IMAGE."\" />";
		echo "</a> tags:\n";

		for($i = 0; $i < sizeof($tags); $i++)
		{
			echo "<a href=\"$open_url".urlencode($tags[$i])."$close_url\" rel=\"tag\">".$tags[$i]."</a>";
			echo ($i == sizeof($tags) - 1 ? "\n" : ", \n");
		}

		echo "</p><br />\n";
	}

	/*    Add comment functionality for the selected writing.
	 */

	$comment_entries = $k5->db->get_by(C_TABLE, array("wid", $wid), NULL, "dateof", "DESC");
	$send_form_to = PATH_URL.(NICE_URL ? '/comments/' : '/comments.php');

	include('comments_show.php');
	if ($entries[0]["commentable"]) include('comments_add.php');
}

####################################################
##### Sidebar Content
####################################################

$k5->go_to_sidebar();

/*    Add keyword search functionality.
 */

$k5search = new k5search($k5->db);
$k5search->generate_form();
$keywords = $k5search->process_form();

if ($keywords) // display search results
{
	$search_limit = 25;

	if (stristr($keywords, "*"))
	{
		$keywords = str_replace("*", "", $keywords);
		$search_limit *= 10;
	}

	$k5search->set_parameters(W_TABLE, "tags", array("wid", "title"), "dateof", "DESC", $search_limit);

	$search_entries = $k5search->find_multicolumn($keywords, array("title", "body"));

	if (sizeof($search_entries) == 0)
	{
		echo "<h2>losers weepers</h2>\n";
		echo "<p>i wasn't able to find anything you searched for. if you'd like to browse around on your own, ";
		echo "visit <a href=\"".PATH_URL.(NICE_URL ? '/tags/' : '/tags.php')."\">the tag wall</a> to get started.</p>\n<br />\n";
	}
	else
	{
		echo "<h2>finders keepers</h2>\n";

		if (sizeof($search_entries) == $search_limit) echo "<p>i'm showing you the first $search_limit results. you may want to refine your search a bit.</p>\n<br />\n";
		else echo "<p>here's what i found from your search.</p>\n<br />\n";

		echo "<ul>\n";
		for($i = 0; $i < sizeof($search_entries); $i++)
		{
			echo "<li>\n<a href=\"".$k5->w_table_wid_to_url($search_entries[$i]["wid"]);
			echo (NICE_URL ? "?" : "&")."highlight=".urlencode($keywords)."\">"; // add highlighting link
			echo $search_entries[$i]["title"]."</a><br />\n</li>\n";
		}
		echo "</ul>\n<br />\n";
	}
}

/*    Display recent entries.
 */

$recent_limit = 7;

$entries = $k5->db->get_by(W_TABLE, array("public", "1"), array("wid", "title"), "dateof", "DESC", $recent_limit);

echo "<h2>mostly fresh\n";
$k5->html->icon_rss($rss_title, $rss_link);
echo "</h2>\n";

echo "<ul>\n";
for($i = 0; $i < sizeof($entries); $i++)
{
	echo "<li>\n<a href=\"".$k5->w_table_wid_to_url($entries[$i]["wid"])."\">";
	echo $entries[$i]["title"]."</a><br />\n</li>\n";
}
echo "</ul>\n<br />\n";

/*    Display available years in the archives.
 */

echo "<h2><a id=\"writings_archives\"></a>by the years</h2>\n";

echo "<ul>\n";
for($i = date("Y"); $i >= FINAL_YEAR; $i--)
{
	echo "<li>\n<a href=\"".PATH_URL.(NICE_URL ? "/writings/$i/" : "/writings.php?archives=$i")."\">";
	echo year_spell($i)."</a><br />\n</li>\n\n";
}
echo "</ul>\n<br />\n";

/*    Include link content.
 */

include('links.php');

##### End PHP code, (c) 2006 kaulana.com

?>