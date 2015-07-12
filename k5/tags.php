<?php

##### TAGS.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page generates supports the tag functionality for writings on the site.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$k5 = new k5("tagged and feathered", W_TABLE);
$k5->auto_initialize();

####################################################
##### Individual Tag Display
####################################################

if (isset($_GET["tag"])) // display all entries matching the given tag
{
	$tag = urldecode($_GET["tag"]);

	/*    Output RSS autodiscovery link.
	 */

	$rss_title = "the $tag feed";
	$rss_link = PATH_URL.(NICE_URL ? "/rss/tags/".urlencode($tag)."/" : "/rss.php?tag=".urlencode($tag));

	$k5->html->add_rss($rss_title, $rss_link);

	/*    Accept additional sorting options.
	 */

	$by = "dateof";
	$asc = "DESC";

	if ($_GET["by"] == "dateup")   { $by = "dateof"; $asc = "ASC";  }
	if ($_GET["by"] == "datedown") { $by = "dateof"; $asc = "DESC"; }
	if ($_GET["by"] == "title")    { $by = "title"; $asc = "ASC";   }

	/*    Gather all writings who match the tag.
	 */

	$entries = $k5->db->get_by(W_TABLE, "`tags` LIKE '%".mysql_real_escape_string($tag)."%' AND `public` = '1'", array("wid", "title", "tags"), $by, $asc);
	$k5search = new k5search($k5->db);

	$k = 0; for($i = 0; $i < sizeof($entries); $i++) // isolate tag uniqueness (i.e. web vs. weblog)
	{
		$current_tags = $k5search->get_keywords($entries[$i]["tags"]);
		$flag = false;
		
		for($j = 0; $j < sizeof($current_tags); $j++)
		{
			if ($current_tags[$j] == $tag) $flag = true;
		}

		if ($flag) $newentries[$k++] = $entries[$i];
	}

	$entries = $newentries;

	$k5->go_to_main();

	echo "<h1>tagged as $tag\n";
	$k5->html->icon_rss($rss_title, $rss_link);
	echo "</h1>\n";

	echo "<p>there ".(sizeof($entries) == 1 ? "is 1 writing " : "are ".sizeof($entries)." writings ");
	echo "sharing the $tag tag. <a href=\"".PATH_URL.(NICE_URL ? '/tags/' : '/tags.php')."\">";
	echo "click here</a> to return to the wall.</p>\n<br />\n";

	echo "<ul>\n";
	for($i = 0; $i < sizeof($entries); $i++)
	{
		echo "<li><a href=\"".$k5->w_table_wid_to_url($entries[$i]["wid"])."\" title=\"tags: ".clean_html($entries[$i]["tags"])."\">";
		echo $entries[$i]["title"]."</a></li>\n";
	}
	echo "</ul>\n<br />\n";

	/*    Provide additional sorting options.
	 */

	$k5->go_to_sidebar();

	echo "<h2>shuffle the deck</h2>\n<ul>\n";
	echo "<li><a href=\"".$_SERVER["SCRIPT_URL"]."?by=dateup\">sort by date, oldest first</a></li>\n";
	echo "<li><a href=\"".$_SERVER["SCRIPT_URL"]."?by=datedown\">sort by date, newest first</a></li>\n";
	echo "<li><a href=\"".$_SERVER["SCRIPT_URL"]."?by=title\">sort by title, alphabetically</a></li>\n";
	echo "</ul>\n<br />\n";

	for($i = 0; $i < sizeof($entries); $i++)
	{
		$current_tags = $k5search->get_keywords($entries[$i]["tags"]);

		for($j = 0; $j < sizeof($current_tags); $j++)
		{
			if ($tags[$current_tags[$j]]) $tags[$current_tags[$j]] += 1;
			else $tags[$current_tags[$j]] = 1;
		}
	}

	if ($tags) ksort($tags, SORT_STRING);

	/*    Show other tags used by these items.
	 */

	echo "<h2>also tagged as</h2>\n";

	echo "<p>\n";
	foreach($tags as $newtag => $count)
	{
		if ($tag != $newtag)
		{
			if ($count < 3) $class = "tag6";
			else if ($count < 7) $class = "tag5";
			else if ($count < 15) $class = "tag4";
			else if ($count < 30) $class = "tag3";
			else if ($count < 65) $class = "tag2";
			else $class = "tag1";

			$utag = urlencode($newtag);

			echo "<a class=\"$class\" href=\"".PATH_URL.(NICE_URL ? "/tags/$utag/" : "/tags.php?tag=$utag")."\" ";
			echo "title=\"$count of these writings\" rel=\"tag\">$newtag</a> / \n";
		}
	}
	echo "</p>\n<br />\n";
}

####################################################
##### Visually Weighted Tag Display
####################################################

else
{
	/*    Gather all tags from all writings.
	 */

	$entries = $k5->db->get_by(W_TABLE, "`tags` != '' AND `public` = '1'", "tags");
	$k5search = new k5search($k5->db);

	for($i = 0; $i < sizeof($entries); $i++)
	{
		$current_tags = $k5search->get_keywords($entries[$i]["tags"]);

		for($j = 0; $j < sizeof($current_tags); $j++)
		{
			if ($tags[$current_tags[$j]]) $tags[$current_tags[$j]] += 1;
			else $tags[$current_tags[$j]] = 1;
		}
	}

	if ($tags) ksort($tags, SORT_STRING);

	$k5->go_to_main();

	echo "<h1>the tag wall</h1>\n";
	echo "<p>if you'd rather browse by date, visit a year in ";
	echo "<a href=\"".PATH_URL.(NICE_URL ? '/writings/'.date("Y").'/' : '/writings.php?archives='.date("Y"))."\">";
	echo "the archives</a>.</p>\n<br />\n";

	echo "<p>\n";
	if ($tags) foreach($tags as $tag => $count)
	{
		if ($count < 3) $class = "tag6";
		else if ($count < 7) $class = "tag5";
		else if ($count < 15) $class = "tag4";
		else if ($count < 30) $class = "tag3";
		else if ($count < 65) $class = "tag2";
		else $class = "tag1";

		$utag = urlencode($tag);

		echo "<a class=\"$class\" href=\"".PATH_URL.(NICE_URL ? "/tags/$utag/" : "/tags.php?tag=$utag")."\" ";
		echo "title=\"".($count == 1 ? "1 writing" : "$count writings")."\" rel=\"tag\">$tag</a> / \n";
	}
	echo "</p>\n<br />\n";

	/*    Display most and least popular tags.
	 */

	if ($tags) arsort($tags, SORT_NUMERIC);

	$k5->go_to_sidebar();

	echo "<h2>top seven</h2>\n";

	echo "<ul>\n";
	$i = 0; while($tags && list($tag, $count) = each($tags))
	{
		if ($i == 7) break;
		$utag = urlencode($tag);

		echo "<li><a href=\"".PATH_URL.(NICE_URL ? "/tags/$utag/" : "/tags.php?tag=$utag")."\" ";
		echo "title=\"".($count == 1 ? "1 writing" : "$count writings")."\" rel=\"tag\">$tag</a></li>\n";
		$i++;
	}
	echo "</ul>\n<br />\n";

	if ($tags) { reset($tags); $tags = array_reverse($tags); }

	echo "<h2>bottom seven</h2>\n";

	echo "<ul>\n";
	$i = 0; while($tags && list($tag, $count) = each($tags))
	{
		if ($i == 7) break;
		$utag = urlencode($tag);

		echo "<li><a href=\"".PATH_URL.(NICE_URL ? "/tags/$utag/" : "/tags.php?tag=$utag")."\" ";
		echo "title=\"".($count == 1 ? "1 writing" : "$count writings")."\" rel=\"tag\">$tag</a></li>\n";
		$i++;
	}
	echo "</ul>\n<br />\n";
}

##### End PHP code, (c) 2006 kaulana.com

?>