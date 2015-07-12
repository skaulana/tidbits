<?php

##### COMMENTS_ADD.PHP

##### Powers the kaulana.com weblog, revision five.
##### Include this page to display a form for adding comments.

require_once('k5.php');

####################################################
##### Script Header
####################################################

/*    Assumptions:
 *
 *    $entries contains an array of associative arrays, in the format of the C_TABLE schema.
 *    The easiest way to generate this is by a call to $k5->db->get_all(C_TABLE).
 *
 *    $wid (if set) will link the new comment to a specific writing entry.
 */

$k5comments = new k5("comments and replies", C_TABLE);

$k5comments->db_open();
$k5comments->admin = new k5admin($k5comments->db);
$k5comments->authorize();

####################################################
##### Standardized Form
####################################################

echo "<h1><a id=\"comments_new\"></a>add your comment</h1>\n";

if (!$k5comments->is_logged_in()) // need to log in first
{
	echo "<p>you must first log in using your <a href=\"http://www.openid.net/\">openid</a> ";
	echo "before you can post a comment.</p>\n<br />\n";

	$k5comments->auth->generate_form(); echo "<br />\n";
}
else if (!$k5comments->can_add_comment()) // inadequate permissions
{
	echo "<p>you cannot use your <a href=\"".$_SESSION["openid_url_href"]."\">".$_SESSION["openid_url"]."</a> identity ";
	echo "to post a comment here. please <a href=\"?logout\">log out</a> and try a different ";
	echo "<a href=\"http://www.openid.net/\">openid</a>.</p>\n<br />\n";
}
else // generate the form, with comments_apply flag for COMMENTS_APPLY.PHP
{
	echo "<p>use the form below to leave a comment for this site.</p>\n<br />\n";

	$default_name = "";
	if (isset($_COOKIE["name"])) $default_name = $_COOKIE["name"];
	if (isset($_POST["name"])) $default_name = $_POST["name"];

	$names = array("comments_apply", "name", "link", "body");
	$defaults = array("true", $default_name, $_SESSION["openid_url_href"], "");
	$notes = array("", "your name*", "your <acronym title=\"url or e-mail, e-mails are never published\">link</acronym>", "thoughts*");
	$states = array("hidden", "", "", "");

	if (isset($wid)) // apply this comment to a specific writing
	{
		$i = sizeof($names);

		$names[$i] = "wid";
		$defaults[$i] = $wid;
		$notes[$i] = "";
		$states[$i] = "hidden";
	}

	$k5comments->db->generate_form($names, $defaults, $notes, $states);

	echo "\n<br />\n";
}

##### End PHP code, (c) 2006 kaulana.com

?>