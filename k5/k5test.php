<?php

##### K5TEST.PHP

##### Demo of the extensible kaulana.com weblog, revision five.
##### Execute this script to see the text below.

require_once("k5.php");

##### The line, require_once("k5.php"), is really ALL you need to get started. Create a new
##### k5 object as in the next line of code, except that you can change the title from its current
##### value of "Automatic test" to whatever you want.

$k5 = new k5("Automatic test", "AUTO");

##### By writing that one line, you're now ready to start writing your main page content.
##### PHP will take a quick breather (with the ? > tag), and resume further down the page where
##### you see the < ?php tag.

?>

<h1>Hello</h1>

<p>
If this page loaded without errors, your site installation is running correctly!
You can use the system as it is, preloaded, or define your own functionality. Jump right in
and open the source code for this file! It's fully commented and easy to understand.
</p>
<br />

<h1>The Basics</h1>

<p>
Open up the source code to this demonstration script and you'll see lots of text, and not a whole
lot of code. That's the point. The k5 scripts are designed to take the busy work out of developing
your own customized weblog, so there's a rich infrastructure that the whole system is built upon.
<br />
<br />
All of the power in this system is available to you by including one file, k5.php. After that, the
sky's the limit. Call k5() in full AUTO mode and you'll see just how little code YOU need to write
in order to start developing your own smart PHP scripts. Follow the model of the other application
scripts, and you can do just about anything that other blogging software can do, too.
<br />
<br />
So what's in it for you? How about OpenID, for starters? Scroll back up and read the sidebar!
<br />
<br />
Yes, there are cleaner pieces of weblog software out there. But do you want to depend on their
tagging system to achieve a certain look? If you're more comfortable writing your own PHP, and
just need a few classes to help you get started, maybe this can point you in the right direction.
</p>

<?php

##### Welcome back to the PHP side of things. Now we're going to put some content on the sidebar
##### that demonstrates our OpenID functionality. First, we'll make the appropriate call to advance
##### the page layout to where the sidebar would normally reside.

$k5->go_to_sidebar();

##### Let's take another quick break for some text.

?>

<h2>A Better Login System</h2>

<p>
Don't you think OpenID is a system whose time has come? Well, now you can easily access that
functionality in k5, since it's all been built in from the start. Try your hand at the login box
below and see what happens!
</p>
<br />

<?php

##### Now we'll use the generic API in k5.php to create a login form for OpenID. Remember, all these
##### functions are easily found in the k5 class in k5.php!

$k5->make_openid_login();

##### Want to make content available only for people who are logged in? It's a great way to stop
##### spammers and trolls. We'll do that very briefly, below.

if ($k5->is_logged_in())
{
?>
<h2>See, wasn't that easy?</h2>

<p>
Now I know that you're logged in as <?php echo $k5->fetch_openid() ?>. Personalize the site easily
with this functionality, and keep spammers away at the same time! Huzzah!
</p>

<?php
}

##### And that's it for this demo! Once you understand what you see here, and are ready to familiarize
##### yourself with the rest of the system, you can go ahead and have a look inside of the other application
##### scripts (files whose names do not start with k5).

##### End PHP code, (c) 2006 kaulana.com

?>