<?php

##### ABOUT.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page allows you to view and edit user information on the site.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$k5 = new k5("about this site", U_TABLE);
$k5->auto_initialize();

####################################################
##### Private Application
####################################################

if ($_POST["form_action"])
{
	/*    First check for possible errors in the request.
	 */

	$action = clean_normal($_POST["form_action"]);

	if (!$k5->is_logged_in()) $error = "you are not logged in.";
	else if (!$k5->is_site_user()) $error = "you are not authorized to use this script.";
	else if (!$k5->can_edit_user())
	{
		if (isset($_POST["uid"]) && $_POST["uid"] != $_SESSION["uid"]) $error = "you may only edit your own information.";
		else if (!$k5->can_add_writing()) $error = "you cannot add a personal biography if you cannot post writings.";
	}

	/*    Handle errors if any, then proceed with appropriate action.
	 */

	$k5->go_to_main();

	if ($error) $k5->exit_standard_error($error);
	else
	{
		if (stristr($action, "submit") || stristr($action, "confirm")) // save/delete changes
		{
			$k5->db->set_one(U_TABLE, $k5->db->gather_form(), "uid");
		}
		else if (stristr($action, "clear")) // confirm to clear bio
		{
			$uid = stristr($action, "my") ? $_SESSION["uid"] : $_POST["uid"];
			$entries = $k5->db->get_by(U_TABLE, array("uid", $uid), array("bio", "name"));

			echo "<h1>bio delete</h1>\n";
			echo "<p>you will be permanently and irreverisbly deleting the bio of ".$entries[0]["name"]." ";
			echo "from the site. scroll down and confirm your action at the bottom of the page.";
			echo "\n</p>\n<br />\n";

			echo "<blockquote>\n<p>\n";
			echo parse_parens($entries[0]["bio"])."\n";
			echo "</p>\n</blockquote>\n<br />\n";

			echo "<form method=\"post\" action=\"\">\n<p>\n";
			echo "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";
			echo "<input type=\"hidden\" name=\"bio\" value=\"\" />\n";
			echo "<input type=\"submit\" name=\"form_action\" value=\"confirm delete\" />\n";
			echo "</p>\n</form>\n<br />\n";

			exit;
		}
		else if (stristr($action, "edit")) // display edit form
		{
			$uid = stristr($action, "my") ? $_SESSION["uid"] : $_POST["uid"];
			$entries = $k5->db->get_by(U_TABLE, array("uid", $uid), array("bio"));

			echo "<h1>bio update</h1>\n";
			echo "<p>when you have finished your changes, click the submit button at the bottom ";
			echo "of the form. if you do not wish to make any changes, please ";
			echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n</p>\n<br />\n";

			$names = array("uid", "bio");
			$defaults = array($uid, $entries[0]["bio"]);
			$states = array("hidden", "");

			$header = "";
			$footer = "<p class='paren'>(please note that you need to provide your own html markup in ";
			$footer .= "your bio to ensure that it displays properly.)</p>\n";

			$k5->db->generate_form($names, $defaults, NULL, $states, $header, $footer);

			exit;
		}
		else // unrecognized action
		{
			echo "<h1>that's nonsense</h1>\n";
			echo "<p>your request was not understood. if you were directed to this page from ";
			echo "within the site, you may want to contact the webmaster.\n<br />\n<br />\n";
			echo "please <a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n</p>\n";

			exit;
		}
	}
}

####################################################
##### Public Application
####################################################

/*    Display bio information for each registered user who has one.
 */

$k5->go_to_main();

$entries = $k5->db->get_by(U_TABLE, NULL, array("uid", "name", "bio"), "uid", "ASC");

for($i = 0; $i < sizeof($entries); $i++)
{
	echo "<!-- bio for ".$entries[$i]["name"]." -->\n";
	echo parse_parens($entries[$i]["bio"])."\n";
	echo "<!-- end bio for ".$entries[$i]["name"]." -->\n";

	if ($entries[$i]["bio"]) echo "<br />\n";

	/*    Show administrative links for sufficiently privileged users.
         */

	if ($_SESSION["uid"] == $entries[$i]["uid"]) // self edit
	{
		echo "<form method=\"post\" action=\"\">\n<blockquote>\n<p>\n";
		echo "this is your bio information. use the buttons below if you would like to ";
		echo "change anything that you see.<br /><br />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"edit my bio\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"clear my bio\" />\n";
		echo "</p>\n</blockquote>\n</form>\n<br />\n";
	}
	else if ($k5->can_edit_user()) // master edit
	{
		echo "<form method=\"post\" action=\"\">\n<blockquote>\n<p>\n";
		echo "you are authorized to edit ".$entries[$i]["name"]."'s bio.<br /><br />\n";
		echo "<input type=\"hidden\" name=\"uid\" value=\"".$entries[$i]["uid"]."\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"edit this bio\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"clear this bio\" />\n";
		echo "</p>\n</blockquote>\n</form>\n<br />\n";
	}

}

####################################################
##### Static Content
####################################################

/*    Display more information about the k5 blogging system, including its OpenID capabilities.
 */

$k5->go_to_sidebar();

echo "<h2>openid enabled</h2>\n";

if ($k5->is_logged_in())
{
	echo "<p>\n";
	echo "thanks for using your <a href=\"http://www.openid.net/\">openid</a>. you should be able ";
	echo "to see your logged in status right above this message. feel free to <a href=\"?logout\">log out</a> ";
	echo "at any time.";
	echo "\n</p>\n<br />\n";

}
else
{
	echo "<p>\n";
	echo "this site is using <a href=\"http://www.openid.net/\">openid</a> to validate the identities ";
	echo "of my visitors. when you're ready, just use the little box below.";
	echo "\n</p>\n<br />\n";

	$k5->auth->generate_form();
}

echo "<h2>powered by k5</h2>\n";

echo "<p>\n";
echo "if you're already comfortable with <a href=\"http://www.php.net/\">php</a>, don't want to battle with ";
echo "the proprietary tags of other blogging software, and just need a few good classes to make your life ";
echo "a little easier, i offer you k5.\n";
echo "<br />\n<br />\n";
echo "<a href=\"http://s.kaulana.com/k5.zip\" class=\"imagelink\">";
echo "<img class=\"centered\" alt=\"download k5 now\" title=\"download k5 now\" src=\"images/k5.jpg\" /></a><br />\n";
echo "don't forget the <a href=\"http://validator.w3.org/check?uri=referer\">xhtml</a> and ";
echo "<a href=\"http://jigsaw.w3.org/css-validator/check/referer\">css</a>. ";
echo "valid for your pleasure and mine.";
echo "\n</p>\n<br />\n";

##### End PHP code, (c) 2006 kaulana.com

?>