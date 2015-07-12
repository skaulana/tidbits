<?php

##### LINKS.PHP

##### Powers the kaulana.com weblog, revision five.
##### Include this page to display link content on your sidebar.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$k5links = new k5("", L_TABLE);

$k5links->db_open();
$k5links->admin = new k5admin($k5links->db);
$k5links->authorize();

####################################################
##### Private Application
####################################################

if ($_POST["form_action"] && isset($_POST["links"]))
{
	/*    First check for possible errors in the request.
	 */

	$action = clean_normal($_POST["form_action"]);

	if (!$k5links->is_logged_in()) $error = "you are not logged in.";
	else if (!$k5links->is_site_user()) $error = "you are not authorized to use this script.";
	else if (!$k5links->can_add_link() && stristr($action, "new link")) $error = "you cannot add new links to the site.";
	else if (!$k5links->can_edit_link() && isset($_POST["lid"]))
	{
		$link_entries = $k5links->db->get_by(L_TABLE, array("lid", $_POST["lid"]));
		if ($link_entries[0]["uid"] != $_SESSION["uid"]) $error = "you cannot modify links that you did not create.";
		else $link_entries = "";
	}

	/*    Handle errors if any, then proceed with appropriate action.
	 */

	if ($error) $k5links->standard_sidebar_error($error);
	else
	{
		$xfn_footer = "<p class='paren'>(valid <a href=\"http://gmpg.org/xfn/11\">xfn</a> tags: ";
		$xfn_footer .= "<acronym title=\"someone you are a friend to; a compatriot, buddy, home(boy|girl) that you know\">friend</acronym>, ";
		$xfn_footer .= "<acronym title=\"someone who you have exchanged greetings with and not much (if any) more\">acquaintance</acronym>, ";
		$xfn_footer .= "<acronym title=\"someone you know how to get in touch with\">contact</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone you have actually met in person\">met</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone who you work with or works at the same organization as you\">co-worker</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone in the same field of study or activity\">colleague</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone you share a street address with\">co-resident</acronym>, ";
		$xfn_footer .= "<acronym title=\"someone who lives nearby, perhaps only at an adjacent street address or doorway\">neighbor</acronym>; ";
		$xfn_footer .= "<acronym title=\"a person's genetic offspring, or someone that a person has adopted and takes care of\">child</acronym>, ";
		$xfn_footer .= "<acronym title=\"a person's progenitor, or someone who has adopted and takes care (or took care) of you\">parent</acronym>, ";
		$xfn_footer .= "<acronym title=\"someone a person shares a parent with\">sibling</acronym>, ";
		$xfn_footer .= "<acronym title=\"someone you are married to\">spouse</acronym>, ";
		$xfn_footer .= "<acronym title=\"a relative, someone you consider part of your extended family\">kin</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone who brings you inspiration\">muse</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone you have a crush on\">crush</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone you are dating\">date</acronym>; ";
		$xfn_footer .= "<acronym title=\"someone with whom you are intimate and at least somewhat committed, possibly exclusively\">sweetheart</acronym>; ";
		$xfn_footer .= "<acronym title=\"a link to yourself at a different url; exclusive of all other xfn values\">me</acronym>.)</p>\n";

		if (stristr($action, "add link")) // form for creating a new link
		{
			echo "<h2>new departure</h2>\n";
			echo "<p>please fill in the details for the new link you wish to create. if you wish to return, please ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n</p>\n<br />\n";

			$names = array("links", "url", "name", "description", "xfn");
			$defaults = array("true", "http://", "", "", "");
			$notes = array("", "url*", "name*", "description", "xfn tags");
			$states = array("hidden", "", "", "", "");
			$header = "";

			$k5links->db->generate_form($names, $defaults, $notes, $states, $header, $xfn_footer);
			echo "\n<br />\n";
		}
		else if (stristr($action, "edit this link")) // form for editing an existing link
		{
			$link_entries = $k5links->db->get_by(L_TABLE, array("lid", $_POST["lid"]));

			echo "<h2>departure change</h2>\n";
			echo "<p>when you have finished your changes, click the submit button at the bottom ";
			echo "of the form. if you do not wish to make any changes, please ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n</p>\n<br />\n";

			$names = array("links", "lid", "uid", "url", "name", "description", "xfn");
			$notes = array("", "", "", "url*", "name*", "description", "xfn tags");
			$states = array("hidden", "hidden", "hidden", "", "", "", "");
			$header = "";

			$defaults[0] = "true";
			for($i = 1; $i < sizeof($names); $i++) $defaults[$i] = $link_entries[0][$names[$i]];

			$k5links->db->generate_form($names, $defaults, $notes, $states, $header, $xfn_footer);
			echo "\n<br />\n";
		}
		else if (stristr($action, "delete this link")) // quick delete
		{
			$k5links->db->delete_one(L_TABLE, $k5links->db->gather_form(), "lid");
		}
		else if (stristr($action, "submit") && (isset($_POST["url"]) || isset($_POST["name"]))) // general save handler
		{
			$entry = $k5links->db->validate_form(array("url", "name"));

			if (!$entry) $k5->standard_sidebar_error("you must specify all the required fields (marked with a *) before continuing.");
			else
			{
				$entry["uid"] = $_SESSION["uid"];
				$counterentries = $k5links->db->get_by(L_TABLE, array("url", $entry["url"]));

				// error message to be used in the event of a url collision

				$possible_error = "the new url you provided is already is use by <a href=\"".$counterentries[0]["url"]."\">".$counterentries[0]["name"]."</a>.";

				if (isset($_POST["lid"])) // edit an existing link
				{
					if (sizeof($counterentries) != 0 && $entry["lid"] != $counterentries[0]["lid"]) $k5links->standard_sidebar_error($possible_error);
					else $k5links->db->set_one(L_TABLE, $entry, "lid");
				}
				else // add a new link
				{
					if (sizeof($counterentries) != 0) $k5links->standard_sidebar_error($possible_error);
					else $k5links->db->add_one(L_TABLE, $entry);
				}
			}
		}
	}
}

####################################################
##### Public Application
####################################################

/*    Immediately display the full list of links, with all their information.
 */

echo "<h2>blogroll</h2>\n";
echo "<ul>\n";

$link_entries = $k5links->db->get_by(L_TABLE, NULL, NULL, "name", "ASC");

for($i = 0; $i < sizeof($link_entries); $i++)
{
	echo "<li>\n<a href=\"".$link_entries[$i]["url"]."\" title=\"".$link_entries[$i]["description"]."\"";
	if ($link_entries[$i]["xfn"]) echo " rel=\"".$link_entries[$i]["xfn"]."\"";
	echo ">".$link_entries[$i]["name"]."</a><br />\n</li>\n";
}

echo "</ul>\n<br />\n";

/*    For appropriately privileged users, allow further actions.
 */

if ($k5links->can_add_link() || $k5links->can_edit_link())
{
	echo "<h2>traffic controller</h2>\n";
	echo "<p>you may wish to perform the following actions on the above list of links. ";
	echo "please make your choice below.</p>\n<br />\n";

	$flag = false;

	for($i = 0; $i < sizeof($link_entries); $i++)
	{
		if ($k5links->can_edit_link() || $link_entries[$i]["uid"] == $_SESSION["uid"])
		{
			if (!$flag)
			{
				$flag = true;

				echo "<form method=\"post\" action=\"\">\n<p style=\"text-align: center\">\n";
				echo "<select name=\"lid\">\n";
			}

			echo "<option value=\"".$link_entries[$i]["lid"]."\">".$link_entries[$i]["name"]."</option>\n";
		}
	}
	if ($flag)
	{
		$flag = false;

		echo "</select>\n<br />\n<br />\n";
		echo "<input type=\"hidden\" name=\"links\" value=\"true\" />\n";
		if ($k5links->can_add_link()) echo "<input type=\"submit\" name=\"form_action\" value=\"add link\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"edit this link\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"delete this link\" />\n";
		echo "\n</p>\n</form>\n<br />\n";	
	}
	else if ($k5links->can_add_link())
	{
		echo "<form method=\"post\" action=\"\">\n<p style=\"text-align: center\">\n";
		echo "<input type=\"hidden\" name=\"links\" value=\"true\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"add link\" />\n";
		echo "\n</p>\n</form>\n<br />\n";
	}
}

##### End PHP code, (c) 2006 kaulana.com

?>