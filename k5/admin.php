<?php

##### ADMIN.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page serves as a dashboard for administrative access to the site.

require_once('k5.php');

####################################################
##### Script Header
####################################################

define('PHPMYADMIN_URL', '*** CHANGE THIS TO YOUR PHPMYADMIN PATH, IF YOU HAVE ONE ***');

$k5 = new k5("administration", U_TABLE);
$k5->initialize();

####################################################
##### Administrative Functions
####################################################

if ($k5->is_site_user())
{
	if ($_POST["form_action"] || $_POST["form_function"])
	{
		/*    First, check for use of the specialized functions.
		 */

		$action = clean_normal($_POST["form_action"]);

		if (stristr($action, "phpinfo"))
		{
			if ($k5->can_edit_user()) { phpinfo(); exit; }
			else { $error = "only global administrators may call phpinfo() from this script."; }
		}
		else if (stristr($action, "dump") && stristr($action, "mysql"))
		{
			if ($k5->can_edit_user()) { $k5->db->dump(K5DB_DB, true); exit; }
			else { $error = "only global administrators may dump the database contents."; }
		}

		$k5->auto_initialize();

		/*    Check for possible errors in the request.
		 */

		if (!$k5->can_add_user() && stristr($action, "new user")) $error = "you are not allowed to create new site users.";
		else if ($k5->can_edit_user() && stristr($action, "delete my account"))
		{
			$superadmins = $k5->db->get_by(U_TABLE, array("edit_user", "1"), array("uid"));
			if (sizeof($superadmins) <= 1)
			{
				$error = "there must always be at least one global administrator at any given time.";
				$error .= " therefore, you may not delete your account.";
			}
		}
		else if (!$k5->can_edit_user())
		{
			if (stristr($action, "edit existing users")) $error = "you are not allowed to edit the information of other users on this site.";
			else if (isset($_POST["uid"]) && $_POST["uid"] != $_SESSION["uid"]) $error = "you may only edit your own information.";
			else if (stristr($action, "reset the banlist")) $error = "only global administrators may erase the banlist.";
		}
		else if (!$k5->can_edit_comment() && isset($_POST["ip"])) $error = "you may not alter this part of the banlist.";
		else if (!$k5->can_add_writing())
		{
			if (isset($_POST["announce"])) $error = "you cannot post announcements if you cannot post writings.";
			else if (isset($_POST["scratchpad"])) $error = "you cannot use the scratchpad if you cannot post writings.";
		}
		else if (!$k5->can_add_comment())
		{
			if (!$k5->can_add_user() && isset($_POST["openid_url"])) $error = "you're not allowed to change your openid association to this account.";
			else if (stristr($action, "delete my account")) $error = "you're not allowed to delete your account.";
		}

		/*    Handle errors if any, then proceed with appropriate action.
		 */

		if ($error) $k5->exit_standard_error($error);
		else
		{			
			if (stristr($action, "new user")) // set permissions for new user
			{
				if (stristr($action, "register")) // try to accept this new user, but carefully check for errors first
				{
					$entry = $k5->db->validate_form(array("openid_url"));

					if (!$entry) $k5->exit_standard_error("you must specify all the required fields (marked with a *) before continuing.");
					else
					{
						list($names, $permissions) = k5masterschema::get_form_data(U_TABLE);
						for($i = 0; $i < sizeof($names); $i++)
						{
							if ($permissions[$i] && $entry[$names[$i]] && !$k5->admin->get_permission($names[$i]))
								$k5->exit_standard_error("you can't ".$permissions[$i]." because you can't either!");
							else if ($permissions[$i] && $entry[$names[$i]]) $entry[$names[$i]] = "1";
						}

						$counterentries = $k5->db->get_by(U_TABLE, array("openid_url", $entry["openid_url"]), "name");
						if (sizeof($counterentries) != 0) $k5->exit_standard_error("the openid you specified is already in use by ".$counterentries[0]["name"].".");

						if ($k5->can_edit_user() && $entry["edit_user"]) // overwrites all
						{
							for($i = 0; $i < sizeof($names); $i++) if ($permissions[$i]) $entry[$names[$i]] = "1";
						}

						$k5->db->add_one(U_TABLE, $entry);
						$k5->go_to_main();

						echo "<h1>".$_POST["name"]." is all set</h1>\n";
						echo "<p>you have successfully added ".$_POST["name"]." into the system. you can now ask this person ";
						echo "to log in using the openid you specified, <span class='atten'>".$_POST["openid_url"]."</span>. ";
						echo "he or she must type it exactly as you have provided it here.\n<br />\n<br />\n";
						echo "<a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.</p>\n";

						exit;
					}
				}

				$k5->go_to_main();

				echo "<h1>fresh meat</h1>\n";
				echo "<p>please fill in the details for the new user that you wish to create. you can ";
				echo "only give this person as many permissions as you currently have. if you wish to return, ";
				echo "please <a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.</p>\n<br />\n";

				list($names, $permissions) = k5masterschema::get_form_data(U_TABLE);

				echo "<form method=\"post\" action=\"\">\n<table>\n";
				echo "<tr><td style=\"padding: 3px\"><p>user's name</p></td>";
				echo "<td style=\"padding: 3px\"><p><input type=\"text\" name=\"name\" value=\"\" size=\"40\" /></p>";
				echo "</td></tr>\n<tr><td style=\"padding: 3px\"><p>user's openid*</p></td>";
				echo "<td style=\"padding: 3px\"><p><input type=\"text\" name=\"openid_url\" value=\"\" size=\"40\" /></p>";
				echo "</td></tr>\n<tr><td colspan=\"2\" style=\"padding: 3px\">\n";

				echo "<table>\n";
				echo "<col /><col class=\"highlight\" /><col />\n";
				echo "<tr><td style=\"padding: 3px\"><p>you</p></td>\n";
				echo "<td style=\"padding: 3px\"><p>new</p></td>\n";
				echo "<td style=\"padding: 3px\"><p>permission</p></td></tr>\n";

				// only iterate over the permissions now - and do it with style

				for($i = 0; $i < sizeof($names); $i++)
				{
					if ($permissions[$i])
					{
						$perm = $names[$i];
						$can = $k5->admin->get_permission($perm);

						echo "<tr class=\"hoverlight\"><td style=\"padding: 3px; text-align: center\">\n<p>\n";
						echo "<input type=\"checkbox\" name=\"my_$perm\" disabled=\"disabled\" ".($can ? "checked=\"checked\" " : "")."/>\n";
						echo "</p>\n</td><td style=\"padding: 3px; text-align: center\">\n<p>\n";
						echo "<input type=\"checkbox\" name=\"$perm\" ".($can ? ($perm == "add_comment" ? "checked=\"checked\" " : "") : "disabled=\"disabled\" ")."/>\n";
						echo "</p>\n</td><td style=\"padding: 3px\">\n<p>\n".$permissions[$i];
						echo "\n</p>\n</td></tr>\n";
					}
				}

				echo "\n</table>\n<br />\n<p>\n";

				echo "<input type=\"submit\" name=\"form_action\" value=\"register this new user\" />\n";

				echo "\n</p>\n</td></tr>\n</table>\n</form>\n<br />\n";
				exit;
			}
			else if (stristr($action, "edit existing users")) // can edit the details of all users
			{
				$k5->go_to_main();

				echo "<h1>all the king's men</h1>\n";
				echo "<p>everyone's information is listed below. note that you are given full liberty here, ";
				echo "so be careful with your changes. removing someone's openid and submitting will remove their account.";
				echo "\n<br />\n<br />\nif you'd rather not edit anything, then please <a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> ";
				echo "to continue.</p>\n<br />\n";

				$entries = $k5->db->get_all(U_TABLE);
				list($names, $permissions) = k5masterschema::get_form_data(U_TABLE);

				for($i = 0; $i < sizeof($names); $i++)
				{
					if (!$permissions[$i]) $permissions[$i] = $names[$i];
					$states[$i] = $names[$i] == "uid" ? "hidden" : "";
				}

				for($i = 0; $i < sizeof($entries); $i++)
				{
					echo "<h2>".$entries[$i]["name"]."'s profile</h2>\n";
					echo "<p>user identifier (uid) is ".$entries[$i]["uid"].".</p>\n<br />\n";

					$k5->db->generate_form($names, $entries[$i], $permissions, $states);
					echo "<br />\n";
				}

				exit;
			}
			else if (stristr($action, "delete my account")) // erase record of this user
			{
				$k5->db->delete_one(U_TABLE, array("uid" => $_SESSION["uid"]), "uid");

				// need to update any affected entries in the other tables

				$k5->db->set_one(B_TABLE, array("uid" => "0"), "uid", $_SESSION["uid"]);
				$k5->db->set_one(W_TABLE, array("uid" => "0"), "uid", $_SESSION["uid"]);
				$k5->db->set_one(L_TABLE, array("uid" => "0"), "uid", $_SESSION["uid"]);

				$k5->go_to_main();

				echo "<h1>goodbye, ".$_SESSION["name"]."</h1>\n";
				echo "<p>your account has been deleted, and you have been automatically logged out ";
				echo "of the system. <a href=\"".PATH_URL."/\">click here</a> to continue.</p>\n";

				$k5->auth->logout(); exit;
			}
			else if (isset($_POST["ip"]) || stristr($action, "banlist")) // manage banned ips
			{
				// switch schema to banlist table

				list($n, $t, $e, $p) = k5masterschema::get_schema(B_TABLE);
				$k5->db->define_schema(B_TABLE, $n, $t, $e, $p);

				if (isset($_POST["ip"]))
				{
					$entry = $k5->db->gather_form();
					$entry["uid"] = $_SESSION["uid"];
					$entry["dateof"] = ""; // so we get the current time

					if (isset($_POST["bid"]))
					{
						if (stristr($action, "unban")) $k5->db->delete_one(B_TABLE, $entry, "bid");
						else $k5->db->set_one(B_TABLE, $entry, "bid");
					}
					else $k5->db->add_one(B_TABLE, $entry);
				}
				else if (stristr($action, "reset the banlist")) // remove it all
				{
					$k5->db->drop_schema();
					$k5->db->create_schema();
				}

				$k5->go_to_main();

				echo "<h1>uninvited guests</h1>\n";
				echo "<p>this list shows all of the ips that have been restricted from viewing your ";
				echo "site. when you are finished, please <a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> ";
				echo "to continue.</p>\n<br />\n";

				if (stristr($action, "sort"))
				{
					if (stristr($action, "date")) { $sort = "dateof"; $order = "DESC"; }
					else if (stristr($action, "ip")) { $sort = "ip"; $order = "ASC"; }
				}
				else
				{
					$sort = "bid"; $order = "ASC";
				}

				$entries = $k5->db->get_by(B_TABLE, NULL, NULL, $sort, $order);
				$d = new k5date();

				echo "<table>\n";
				for($i = 0; $i < sizeof($entries); $i++) // privilegd folks can edit too
				{
					$d->k5date($entries[$i]["dateof"]);

					$trclass = ($i % 10 == 0) ? "highlight" : "hoverlight";

					echo "<tr class=\"$trclass\"><td style=\"padding: 3px\">\n";
					echo "<form method=\"post\" action=\"\"><p>\n";

					echo "<input type=\"hidden\" name=\"bid\" value=\"".$entries[$i]["bid"]."\" />\n";
					echo "<input type=\"hidden\" name=\"uid\" value=\"".$entries[$i]["uid"]."\" />\n";

					if ($k5->can_edit_user() || ($k5->can_edit_comment() &&
							$entries[$i]["uid"] == $_SESSION["uid"])) $disabled = "";
					else $disabled = " disabled=\"disabled\"";

					echo "<input type=\"text\" name=\"ip\" value=\"".$entries[$i]["ip"]."\" size=\"20\"$disabled />\n";
					echo "<input type=\"text\" name=\"reason\" value=\"".$entries[$i]["reason"]."\" size=\"30\"$disabled />\n";
					echo "<acronym title=\"ban #".$entries[$i]["bid"]." last modified ".$d->get_human()."\">?</acronym>\n";
					
					if ($disabled)
					{
						echo "&nbsp;\n";
					}
					else
					{
						echo "<input type=\"submit\" name=\"form_action\" value=\"save\" />\n";
						echo "<input type=\"submit\" name=\"form_action\" value=\"unban\" />\n";
					}

					echo "\n</p></form>\n</td></tr>\n";
				}
				echo "</table>\n<br />\n";

				echo "<form method=\"post\" action=\"\">\n<p style=\"text-align: center\">\n";
				echo "<input type=\"submit\" name=\"form_action\" value=\"sort the banlist by date\" />\n";
				echo "<input type=\"submit\" name=\"form_action\" value=\"sort the banlist by ip\" />\n";
				echo "</p>\n</form>\n<br />\n";

				if ($k5->can_edit_comment()) // allow additions to the list
				{
					// switch schema so we can use the autogenerated form

					list($n, $t, $e, $p) = k5masterschema::get_schema(B_TABLE);
					$k5->db->define_schema(B_TABLE, $n, $t, $e, $p);

					echo "<h1>ban someone</h1>\n";
					echo "<p>you can restrict someone's access to the site based on their ip address.\n</p>\n<br />\n";

					$names = array("ip", "reason");
					$defaults = array("", "spam found on this site was traced back to your ip address. please, stop defacing personal web sites.");
					$notes = array("ip to ban", "reason for ban");
					$states = array("", "");

					$header = "";
					$footer = "<p class='paren'>(you can also ban a subclass of ip addresses by specifying an incomplete address ";
					$footer .= "ending in a dot, e.g. 192.168. or 127.0.0. as the ip. be careful with this feature.)</p>\n";

					$k5->db->generate_form($names, $defaults, $notes, $states, $header, $footer);

					echo "\n<br />\n";
				}

				if ($k5->can_edit_user()) // allow master reset
				{
					echo "<h1>free for all</h1>\n";

					echo "<form method=\"post\" action=\"\">\n<p>\n";
					echo "you may also clear the entire banlist, if you wish. please keep in mind that doing so ";
					echo "is both permanent and irreversible.\n<br />\n<br />\n";
					echo "<input type=\"submit\" name=\"form_action\" value=\"reset the banlist\" />\n";
					echo "</p>\n</form>\n<br />\n";
				}

				$k5->go_to_sidebar();

				echo "<h2>quick analysis</h2>\n";

				echo "<ul>\n";
				echo "<li>there are ".sizeof($entries)." total entries in the ban list.</li>\n";

				$flag = false; for($i = 0; $i < sizeof($entries)-1; $i++) // search for repeats
				{
					for($j = $i+1; $j < sizeof($entries); $j++)
					{
						if(strpos($entries[$i]["ip"], $entries[$j]["ip"]) === 0 ||
							strpos($entries[$j]["ip"], $entries[$i]["ip"]) === 0)
						{
							echo "<li>ips ".$entries[$i]["ip"]." <span class='paren'>(#".$entries[$i]["bid"].")</span> and ";
							echo $entries[$j]["ip"]." <span class='paren'>(#".$entries[$j]["bid"].")</span> share a common root.</li>\n";
							$flag = true;
						}
					}
				}

				if (!$flag) echo "<li>no redundancy was detected.</li>\n";
				echo "</ul>\n";

				exit;
			}
			else if (stristr($action, "submit")) // general save handler
			{
				if (isset($_POST["uid"])) // changing someone else's info
				{
					$entry = $k5->db->gather_form();

					list($names, $permissions) = k5masterschema::get_form_data(U_TABLE);
					for($i = 0; $i < sizeof($names); $i++) // set all permissions explicitly
					{
						if ($permissions[$i]) $entry[$names[$i]] = isset($entry[$names[$i]])
								&& $entry[$names[$i]] ? "1" : "0";
					}

					if ($entry["openid_url"]) $k5->db->set_one(U_TABLE, $entry, "uid");
					else $k5->db->delete_one(U_TABLE, array("uid" => $entry["uid"]), "uid");
				}
				else if (isset($_POST["name"])) // changing your own info
				{
					$entry = $k5->db->gather_form();
					$entry["uid"] = $_SESSION["uid"];

					$k5->db->set_one(U_TABLE, $entry, "uid");
					if ($_POST["name"] != $_SESSION["name"]) $_SESSION["name"] = $_POST["name"];
					if ($_POST["openid_url"] != $_SESSION["openid_url"])
					{
						$k5->go_to_main();

						echo "<h1>your openid was changed</h1>\n";
						echo "<p>your changes were saved, ".$_POST["name"].". however, because you modified your ";
						echo "<a href=\"http://www.openid.net/\">openid</a>, you have been logged out of the system. ";
						echo "you will need to log in again using your new openid.\n</p>\n<br />\n";

						$_GET["openid_url"] = $_POST["openid_url"];

						$k5->auth->logout();
						$k5->auth->generate_form();

						exit;
					}
				}
				else if (isset($_POST["announce"]) || isset($_POST["scratchpad"]))
				{
					$entry = $k5->db->gather_form();
					$entry["uid"] = $_SESSION["uid"];

					$k5->db->set_one(U_TABLE, $entry, "uid");
				}
			}
			else // unrecognized action
			{
				$k5->go_to_main();

				echo "<h1>that's nonsense</h1>\n";
				echo "<p>your request was not understood. if you were directed to this page from ";
				echo "within the site, you may want to contact the webmaster.\n<br />\n<br />\n";
				echo "please <a href=\"".$_SERVER["SCRIPT_URL"]."\">click here</a> to continue.\n</p>\n";

				exit;
			}
		}
	}

	/*    Generic administrative console.
	 */

	$k5->auto_start();

	$entries = $k5->db->get_by(U_TABLE, array("uid", $_SESSION["uid"]));

	echo "<h1>hello, ".$entries[0]["name"]."</h1>\n";

	echo "<p>\nwelcome back to the administrative dashboard for your site.\n";
	echo "you may choose an action to perform below.\n</p>\n<br />\n\n";

	echo "<h1>administrative actions</h1>\n";
	echo "<p>\nusing the functionality here may take you outside of the site.\n</p>\n<br />\n";

	echo "<form method=\"post\" action=\"\">\n<p style=\"text-align: center\">\n";
	echo "<input type=\"submit\" name=\"form_action\" value=\"call phpinfo()\" ".(!$k5->can_edit_user() ? "disabled=\"disabled\" ": "")."/>\n";
	echo "<input type=\"button\" value=\"dreamhost panel\" onclick=\"window.location='https://panel.dreamhost.com/'\" />\n";
	echo "<br />\n";
	echo "<input type=\"submit\" name=\"form_action\" value=\"dump the mysql database\" ".(!$k5->can_edit_user() ? "disabled=\"disabled\" ": "")."/>\n";
	echo "<input type=\"button\" value=\"access phpmyadmin\" onclick=\"window.location='".PHPMYADMIN_URL."'\" />\n";
	echo "<br />\n";
	echo "<input type=\"submit\" name=\"form_action\" value=\"manage banlist\" />\n";
	echo "<input type=\"button\" value=\"log out your openid\" onclick=\"window.location='?logout'\" />\n";
	echo "</p>\n</form>\n<br />\n";

	if ($k5->can_add_user())
	{
		echo "<h1>user actions</h1>";
		echo "<p>\nyou also have the following administrative rights available to you.</p>\n<br />\n";

		echo "<form method=\"post\" action=\"\">\n<p style=\"text-align: center\">\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"create a new user\" />\n";
		echo "<input type=\"submit\" name=\"form_action\" value=\"edit existing users\" ".(!$k5->can_edit_user() ? "disabled=\"disabled\" ": "")."/>\n";
		echo "</p>\n</form>\n<br />\n";
	}

	echo "<h1>update my account</h1>\n";
	echo "<p>\nyou can use the form below to change your name ";

	if ($k5->can_add_comment()) // add the option to change your openid
	{
		echo "or the <a href=\"http://www.openid.net/\">openid</a> that is associated with your account. ";
		echo "be <span class='atten'>absolutely</span> sure that you own any new openid you specify, or ";
		echo "else you will lose access to your account!</p>\n<br />\n";
	}
	else // if you can't add comments, which EVEN GUESTS can do, then don't allow to change openid
	{
		echo "as it is displayed here.</p>\n<br />\n";
	}

	$names = array("name", "openid_url");
	$defaults = array($entries[0]["name"], $entries[0]["openid_url"]);
	$notes = array("my name", "my openid");
	$states = array("", $k5->can_add_comment() ? "" : "hidden");

	$k5->db->generate_form($names, $defaults, $notes, $states); echo "\n<br />\n";

	echo "<h1>remove my account</h1>\n";

	echo "<form method=\"post\" action=\"\">\n<p>\n";
	echo "you may also delete your account, if you wish. please keep in mind that doing so ";
	echo "is both permanent and irreversible.\n<br />\n<br />\n";
	echo "<input type=\"submit\" name=\"form_action\" value=\"delete my account\" />\n";
	echo "</p>\n</form>\n<br />\n";

	/*    Sidebar informational display.
	 */

	$k5->go_to_sidebar();

	if ($k5->can_add_writing()) // for writers only
	{
		echo "<h2>tell the world</h2>\n";
		echo "<p>use this space to post a public announcement on the front page.</p>\n<br />\n";

		$names = array("announce");
		$defaults = array($entries[0]["announce"]);

		$k5->db->generate_form($names, $defaults); echo "\n<br />\n";

		echo "<h2>keep it to yourself</h2>\n";
		echo "<p>use this space if you ever need to jot something down. what you write here won't ";
		echo "be displayed anywhere else on the site.</p>\n<br />\n";

		$names = array("scratchpad");
		$defaults = array($entries[0]["scratchpad"]);
		$states = array("");

		$k5->db->generate_form($names, $defaults); echo "\n<br />\n";
	}

	echo "<h2>my story</h2>\n";
	echo "<p>if you can post writings on this site, you are also welcome to ";
	echo "<a href=\"".PATH_URL.(NICE_URL ? '/about/' : '/about.php')."\">post a bio</a> of yourself ";
	echo "on the about page.</p>\n<br />\n";
}

####################################################
##### Administrative Access Denied
####################################################

else
{
	$k5->auto_start();

	echo "<h1>sorry, wrong number</h1>\n";
	echo "<p>\nyou don't have the privileges necessary to access this part of the site.\n";
	echo "<br />\n<br />\n";

	if (!$k5->is_logged_in())
	{
		echo "if you are a registered member of this site, please note that you cannot ";
		echo "be authenticated without logging in using your ";
		echo "<a href=\"http://www.openid.net/\">openid</a> first. you may do so below.\n";
		echo "</p>\n<br />\n";
		
		$k5->auth->generate_form();
	}
	else
	{
		echo "you are welcome to <a href=\"?logout\">log out</a> and try again.\n";
		echo "</p>\n";
	}

	exit;
}

##### End PHP code, (c) 2006 kaulana.com

?>