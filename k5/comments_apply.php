<?php

##### COMMENTS_APPLY.PHP

##### Powers the kaulana.com weblog, revision five.
##### Include this page to handle the comment form and update the database as needed.

require_once('k5.php');

####################################################
##### Script Header
####################################################

/*    Assumptions:
 *
 *    A POST variable named "comments_apply" has been set in the browser post-back.
 */

$k5comments = new k5("comments and replies", C_TABLE);

$k5comments->db_open();
$k5comments->admin = new k5admin($k5comments->db);
$k5comments->authorize();

####################################################
##### Standardized Form Handler
####################################################

if ($_POST["form_action"] && isset($_POST["comments_apply"]))
{
	/*    First check for possible errors in the request.
	 */

	$action = clean_normal($_POST["form_action"]);

	if (!$k5comments->is_logged_in()) $error = "you are not logged in.";
	else if (!$k5comments->can_add_comment() && !isset($_POST["cid"])) $error = "you are not authorized to post comments to this site.";
	else if (!$k5comments->can_edit_comment() && isset($_POST["cid"]))
	{
		$counterentries = $k5comments->db->get_by(C_TABLE, array("cid", $_POST["cid"]));
		if ($counterentries[0]["openid_url"] != $k5comments->fetch_openid()) $error = "you are not authorized to edit posted comments on this site.";
	}

	/*    Handle errors if any, then proceed with appropriate action.
	 */

	if ($error) $k5comments->exit_standard_error($error);
	else
	{
		$form_action = $_POST["form_action"];
		unset($_POST["form_action"]); // remove the current form action

		if (stristr($action, "delete this comment")) // delete and possibly ban
		{
			$entry = $k5comments->db->gather_form();
			$k5comments->db->delete_one(C_TABLE, array("cid" => $entry["cid"]), "cid");

			if ($_POST["apply_ban"])
			{
				$ban_entry["uid"] = $_SESSION["uid"];
				$ban_entry["ip"] = $entry["ip"];
				$ban_entry["reason"] = "spam found on this site was traced back to your ip address. please, stop defacing personal web sites.";
				$ban_entry["dateof"] = ""; // so we get the current time

				$k5comments->db->add_one(B_TABLE, $ban_entry);
			}
		}
		else if (stristr($action, "remove or ban")) // confirmation prompt
		{
			$k5comments->go_to_main();

			echo "<h1>turn down the volume</h1>\n";
			echo "<p>you are about to remove the following comment. please keep in mind that doing so ";
			echo "is both permanent and irreversible. if you do not wish to remove the comment, please ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n<br />\n<br />\n";

			$entries = $k5comments->db->get_by(C_TABLE, array("cid", $_POST["cid"]));

			echo "<blockquote>\n<p>\n";
			echo $entries[0]["name"]." <span class='paren'>(".$entries[0]["link"].")</span>\n";
			echo "<br />\n<br />\n".$entries[0]["body"]."\n";
			echo "</p>\n</blockquote>\n<br />\n";

			echo "<form method=\"post\" action=\"\">\n<p>\n";
			echo "<input type=\"hidden\" name=\"comments_apply\" value=\"true\" />\n";
			echo "<input type=\"hidden\" name=\"cid\" value=\"".$entries[0]["cid"]."\" />\n";
			echo "<input type=\"checkbox\" name=\"apply_ban\" /> &nbsp; ";
			echo "also add this user's ip address <span class='paren'>(".$entries[0]["ip"].")</span> to the ";
			echo "<acronym title=\"users on the site banlist are prevented from viewing any pages on the site.\">banlist</acronym>\n";
			echo "<br />\n<br />\n<input type=\"submit\" name=\"form_action\" value=\"delete this comment\" />\n";
			echo "</p>\n</form>\n<br />\n";

			$k5comments->html->content_close();

			exit;
		}
		else if (stristr($action, "submit")) // add new or save changes
		{
			$entry = $k5comments->db->validate_form(array("name", "body"));

			if (!$entry) $k5comments->exit_standard_error("you must specify all the required fields (marked with a *) before continuing.");
			else
			{
				$entry["openid_url"] = $_SESSION["openid_url"];
				$entry["ip"] = $_SERVER["REMOTE_ADDR"];
				$entry["dateof"] = ""; // so we get the current time
				$entry["body"] = clean_singleline($entry["body"]); // force into one paragraph

				if ($entry["link"] && !stristr($entry["link"], "@") &&
					!stristr($entry["link"], "http://")) $entry["link"] = "http://".$entry["link"];

				if ($entry["cid"]) $k5comments->db->set_one(C_TABLE, $entry, "cid");
				else $k5comments->db->add_one(C_TABLE, $entry);
			}

			setcookie("name", $entry["name"], time()+60*60*24*30, '/'); // save name for 30 days
		}
		else $_POST["form_action"] = $form_action; // restore it since we did nothing
	}
}

##### End PHP code, (c) 2006 kaulana.com

?>