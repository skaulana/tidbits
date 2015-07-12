<?php

##### K5INSTALL.PHP

##### Installs the kaulana.com weblog, revision five.

####################################################
##### Default OpenID User
####################################################

define('INSTALLATION_OPENID', '*** FILL ME IN ***');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head><title>k5 installation</title></head>

<body>
<h1>Hello!</h1>
<p>This script will help you install the k5 weblog on your system.
Here are the things you need to do first, to ensure a smooth installation:</p>
<ul>
<li>Copy all included files into their proper paths, as provided in the script distribution.</li>
<li>Edit the following files in the following places (only *'d items are are required):
<ul>
<li><i>k5install.php</i>* (this file) - fill in the OpenID you want to use to access the site.</li>
<li><i>k5base.php</i> - change the STYLESHEET and FAVICON constants to suit your needs, then insert
your layout headers and footers into the open() and close() functions of the k5html class.</li>
<li><i>k5.php</i> - also change the close_standard() function in a similar manner, if you wish.</li>
<li><i>k5.php</i>* - provide your MySQL database connection settings by filling in the blank K5DB_* values.</li>
<li><i>k5auth.php</i> - if you want to use different icons for OpenID, there are constants here as well.</li>
<li><i>admin.php</i> - specify a path to phpMyAdmin, if you have it.</li>
<li><i>writing.php</i> - specify how far back you want there to be archives (default is 2006).</li>
<li><i>writings_ping.php</i> - specify the main title of your site if you intend to use the pings.</li>
<li><i>rss.php</i> - you may wish to change the channel name or description.</li>
</ul>
</li>
<li>Ensure that your MySQL database is up and running as you specified it above.</li>
</ul>
<h1>Installation</h1>
<p>Now let's get down to business.</p>
<ul>
<li><i>Checking for the existence of class files...</i></li>
<?php

$error = false;

function not_found($file) { echo "<li><b>$file</b> was NOT found! Please replace the file.</li>\n"; return false; }
function validate($file)  { if (file_exists($file)) return true; return not_found($file);                         }

if (!validate("k5base.php"))   $error = true;
if (!validate("k5util.php"))   $error = true;
if (!validate("k5auth.php"))   $error = true;
if (!validate("k5login.php"))  $error = true;
if (!validate("k5search.php")) $error = true;
if (!validate("k5.php"))       $error = true;

if (!$error)
{
?>
<li><i>Initializing the database...</i></li>
<?php

require_once("k5.php");

$db = new k5dbschema(K5DB_HOST, K5DB_USER, K5DB_PASS, K5DB_DB);

if (!$db) exit("</ul><p>Couldn't access the database. Please change the constants as requested.</p></body></html>");

function initialize($table)
{
	list($n, $t, $e, $p) = k5masterschema::get_schema($table);
	global $db; $db->define_schema($table, $n, $t, $e, $p);
	$db->create_schema();
}

initialize(U_TABLE);
initialize(B_TABLE);
initialize(W_TABLE);
initialize(C_TABLE);
initialize(L_TABLE);

?>
<li><i>Adding OpenID <?php echo INSTALLATION_OPENID ?> to the system...</i></li>
<?php

if (INSTALLATION_OPENID == '*** FILL ME IN ***') exit("</ul><p>You need to fill in your OpenID in THIS file.</p></body></html>");

$exists = $db->get_by(U_TABLE, array("openid_url", INSTALLATION_OPENID));

if (sizeof($exists) == 0)
{
	$entry["openid_url"] = INSTALLATION_OPENID;
	$entry["add_user"] = 1;
	$entry["edit_user"] = 1;
	$entry["add_writing"] = 1;
	$entry["edit_writing"] = 1;
	$entry["add_comment"] = 1;
	$entry["edit_comment"] = 1;
	$entry["add_link"] = 1;
	$entry["edit_link"] = 1;

	$db->add_one(U_TABLE, $entry);
}
else
{
	echo "<li>Your OpenID already seems to be in the system. Skipping step.</li>\n";
}

echo "</ul><p>This installation script will attempt to delete itself. Click <a href='k5test.php'>here</a> to continue.</p>\n";
}
else echo "</ul><p>Please fix the problems before continuing.</p>\n";
?>
</body>
</html>

<?php

unlink(basename($_SERVER["PHP_SELF"]));

?>