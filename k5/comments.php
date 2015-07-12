<?php

##### COMMENTS.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page allows you to view and edit comments stored on the site.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$k5 = new k5("comments and replies", C_TABLE);
$k5->auto_initialize();

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
	else if (!$k5->can_edit_comment() && isset($_POST["cid"]))
	{
		$counterentries = $k5->db->get_by(C_TABLE, array("cid", $_POST["cid"]));
		if ($counterentries[0]["openid_url"] != $k5->fetch_openid()) $error = "you are not authorized to edit posted comments on this site.";
	}

	/*    Handle errors if any, then proceed with appropriate action.
	 */

	if ($error) $k5->exit_standard_error($error);
	else
	{
		// comments_apply handles save and delete, so all we need is the edit form

		if (stristr($action, "edit this comment"))
		{
			$k5->go_to_main();

			echo "<h1>let me rephrase that</h1>\n";
			echo "<p>make changes to your comment below. if you do not wish to save any changes, ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.</p>\n<br />\n";

			$entries = $k5->db->get_by(C_TABLE, array("cid", $_POST["cid"]));

			if ($k5->can_edit_comment())
			{
				$names = array("comments_apply", "cid", "openid_url", "name", "link", "body", "ip");
				$defaults = array("true", $entries[0]["cid"], $entries[0]["openid_url"], $entries[0]["name"], $entries[0]["link"], $entries[0]["body"], $entries[0]["ip"]);
				$notes = array("", "", "openid", "user's name", "user's link", "comment", "ip address");
				$states = array("hidden", "hidden", "", "", "", "", "");

				// writing identifier selection

				$writing_entries = $k5->db->get_by(W_TABLE, NULL, array("wid", "title"), "title", "ASC");

				$header = "<p>comment #".$entries[0]["cid"]." was made about the following writing:\n<select name=\"wid\">\n";

				$header .= "<option value=\"0\" ";
				if ($writing_entries[$i]["wid"] == "0") $header .= " selected=\"selected\"";
				$header .= ">-- not linked to any writing</option>\n";

				for($i = 0; $i < sizeof($writing_entries); $i++)
				{
					$header .= "<option value=\"".$writing_entries[$i]["wid"]."\"";
					if ($writing_entries[$i]["wid"] == $entries[0]["wid"]) $header .= " selected=\"selected\"";
					$header .= ">".$writing_entries[$i]["title"]."</option>\n";
				}

				$header .= "\n</select>\n</p>\n";
			}
			else
			{
				$names = array("comments_apply", "cid", "name", "link", "body");
				$defaults = array("true", $entries[0]["cid"], $entries[0]["name"], $entries[0]["link"], $entries[0]["body"]);
				$notes = array("", "", "your name*", "your <acronym title=\"url or e-mail, e-mails are never published\">link</acronym>", "thoughts*");
				$states = array("hidden", "hidden", "", "", "");

				$header = "";
			}

			$k5->db->generate_form($names, $defaults, $notes, $states, $header);

			echo "\n<br />\n"; exit;
		}
	}
}

####################################################
##### Public Application
####################################################

$rss_title = "fresh feedback feed";
$rss_link = PATH_URL.(NICE_URL ? '/rss/comments/' : '/rss.php?comments');
$k5->html->add_rss($rss_title, $rss_link);

$k5->go_to_main();

$page = isset($_GET["page"]) ? $_GET["page"] : "1";

/*    Calculate some constants related to page display.
 */

$pagesize = 5; $radius = 2;

$total = $k5->db->query("SELECT cid FROM `".C_TABLE."`");
$total = @mysql_num_rows($total);

$cur = ($page - 1) * $pagesize;
$max = ceil($total / $pagesize);

$open_radius  = $page - $radius < 2      ? 2      : $page - $radius;
$close_radius = $page + $radius > $max-1 ? $max-1 : $page + $radius;

$open_url  = PATH_URL.(NICE_URL ? '/comments/' : '/comments.php?page=');
$close_url = (NICE_URL ? '/' : '');

/*    Display appropriate links to page through all the comments.
 */

echo "<div style=\"float: right\">\n<p style=\"text-align: right\">\n";
echo ($page == 1) ? "page\n" : "<a href=\"$open_url".($page - 1)."$close_url\">previous</a> -- page\n";

echo ($page == 1) ? "1\n" : "<a href=\"$open_url"."1"."$close_url\">1</a>\n";
echo ($open_radius == 2) ? "" : "...\n";

for($i = $open_radius; $i <= $close_radius; $i++) echo ($page == $i) ? "$i\n" : "<a href=\"$open_url".$i."$close_url\">$i</a>\n";

echo ($close_radius == $max-1) ? "" : "...\n";
echo ($page == $max) ? "$max\n" : "<a href=\"$open_url".$max."$close_url\">$max</a>\n";

echo ($page == $max) ? "" : "-- <a href=\"$open_url".($page + 1)."$close_url\">next</a>\n";
echo "\n</p>\n</div>\n";

/*    Show the comments available on this page of the form.
 */

$comment_entries = $k5->db->get_by(C_TABLE, NULL, NULL, "dateof", "DESC", "$cur, $pagesize");
$send_form_to = "";

include('comments_show.php');
include('comments_add.php');

####################################################
##### Sidebar Content
####################################################

$k5->go_to_sidebar();

echo "<h2>fresh feedback\n";
$k5->html->icon_rss($rss_title, $rss_link);
echo "</h2>\n";

echo "<p><a href=\"$rss_link\">subscribe</a> to the opinions of others. do your part to stay in the loop.</p><br />\n";

##### End PHP code, (c) 2006 kaulana.com

?>