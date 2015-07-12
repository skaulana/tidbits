<?php

##### ERROR.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page will direct users in the event of an error.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$error = clean_normal($_GET["type"]);

switch ($error)
{
	case "400": $head = "english, please"; $message = "your request was malformed, so i didn't understand what you said."; break;
	case "401": $head = "forget it"; $message = "i couldn't authenticate you, so i can't let you in."; break;
	case "403": $head = "no, thank you"; $message = "you're not allowed to be here, so i can't let you in."; break;
	case "404": $head = "you missed"; $message = "i couldn't find what you were looking for."; break;
	case "500": $head = "kill me"; $message = "something's messed up in here. don't worry, it's not your fault."; break;
	   default: $head = "i have no idea"; $message = "there's an error, somehow, somewhere, but i don't know exactly what it is."; break;
}

$k5 = new k5($head);
$k5->auto_initialize();

####################################################
##### Error Display
####################################################

$k5->go_to_main();

echo "<h1>$head</h1>\n";

echo "<p>$message\n<br />\n<br />\n";
echo "okay, now that you sunk my battleship, what's next? feel free to ";
echo "<a href=\"".PATH_URL.(NICE_URL ? '/comments/' : '/comments.php')."\">complain</a>, but when ";
echo "you're ready, you can <a href=\"".PATH_URL."/\">start over</a>.\n</p>\n";

$k5->go_to_sidebar();

echo "<h1 style=\"font-size: 96px; padding-top: 40px; text-align: center\">$error</h1>\n";

##### End PHP code, (c) 2006 kaulana.com

?>