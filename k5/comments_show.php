<?php

##### COMMENTS_SHOW.PHP

##### Powers the kaulana.com weblog, revision five.
##### Including this page will display the provided comments in the correct format.

require_once('k5.php');

####################################################
##### Script Header
####################################################

/*    Assumptions:
 *
 *    A k5 object named $k5 has already been declared and initialized.
 *
 *    $comment_entries contains an array of associative arrays, in the format of the C_TABLE schema.
 *    The easiest way to generate this is by a call to $k5->db->get_all(C_TABLE).
 *
 *    $send_form_to (if set) will direct the edit forms to a specified location.
 *
 *    $wid (if set) will disable writing interlinking.
 */

define('REPLY_IMAGE', PATH_URL.'/layout/reply.jpg');

####################################################
##### Standardized Display
####################################################

if (sizeof($comment_entries) > 0)
{
	echo "<h1>comments and replies</h1>\n";
	echo "<p>you can <a href=\"#comments_new\">skip directly</a> to the comments form if you already ";
	echo "know what you want to say.</p>\n<br />\n";
}

for($i = 0; $i < sizeof($comment_entries); $i++)
{
	echo "<!-- comment #".$comment_entries[$i]["cid"]." -->\n<p>";
	if ($comment_entries[$i]["link"] && !stristr($comment_entries[$i]["link"], "@"))
	{
		echo "<a href=\"".$comment_entries[$i]["link"]."\" rel=\"nofollow\">".$comment_entries[$i]["name"]."</a>";
	}
	else if ($k5->can_edit_comment() && stristr($comment_entries[$i]["link"], "@"))
	{
		echo "<a href=\"mailto:".$comment_entries[$i]["link"]."\">".$comment_entries[$i]["name"]."</a>";
	}
	else
	{
		echo $comment_entries[$i]["name"];
	}

	echo "</p>\n<br />\n";

	/*    Truncate the length of comments if necessary.
	 */

	$length_limit = 400;

	echo "<blockquote>\n";

	if (strlen($comment_entries[$i]["body"]) <= $length_limit) // normal display
	{
		echo "<p>".parse_parens($comment_entries[$i]["body"])."</p>\n";
	}
	else // truncated display
	{
		echo "<div id='truncated_$i' style=\"width: 100%; display: block\"><p>".substr($comment_entries[$i]["body"], 0, $length_limit)."...";
		echo " (<a onclick=\"togglediv('truncated_$i'); togglediv('full_length_$i');\">more</a>)</p></div>\n";
		echo "<div id='full_length_$i' style=\"width: 100%; display: none\"><p>".parse_parens($comment_entries[$i]["body"]);
		echo " (<a onclick=\"togglediv('truncated_$i'); togglediv('full_length_$i');\">less</a>)</p></div>\n";
	}

	/*    Display edit and delete buttons for the appropriate users.
	 */

	if ($k5->is_logged_in() && ($k5->can_edit_comment() || $k5->fetch_openid() == $comment_entries[$i]["openid_url"]))
	{
		echo "<table><tr><td>";

		echo "\n<form method=\"post\" action=\"$send_form_to\">\n<p>\n<br />\n";
		echo "<input type=\"hidden\" name=\"cid\" value=\"".$comment_entries[$i]["cid"]."\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"edit this comment\" />\n";
		echo "</p>\n</form>";

		echo "</td><td>";

		echo "\n<form method=\"post\" action=\"$send_form_to\">\n<p>\n<br />\n";
		echo "<input type=\"hidden\" name=\"comments_apply\" value=\"true\" />\n";
		echo "<input type=\"hidden\" name=\"cid\" value=\"".$comment_entries[$i]["cid"]."\" />\n";
		if ($k5->can_edit_comment()) echo "<input type=\"submit\" name=\"form_action\" value=\"remove or ban this comment\" />\n";
		else echo "<input type=\"submit\" name=\"form_action\" value=\"delete this comment\" />\n";
		echo "</p>\n</form>";

		echo "</td></tr></table>\n";
	}

	$d = new k5date($comment_entries[$i]["dateof"]);

	echo "\n<p class='fine'>\n<br />\n".strtolower($d->get_human())."\n";

	if (!isset($wid) && $comment_entries[$i]["wid"] != "0")
	{
		echo "<a href=\"".$k5->w_table_wid_to_url($comment_entries[$i]["wid"])."\" class=\"imagelink\">";
		echo "<img style=\"vertical-align: middle\" alt=\"read the writing this was written in response to\" title=\"read the writing this was written in response to\" src=\"".REPLY_IMAGE."\" /></a>\n";
	}

	echo "</p>\n</blockquote>\n<br />\n";
}

##### End PHP code, (c) 2006 kaulana.com

?>