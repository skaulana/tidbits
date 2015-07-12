<?php

##### RSS.PHP

##### Powers the kaulana.com weblog, revision five.
##### This page generates an RSS feed for various parts of the site.

require_once('k5.php');

####################################################
##### Script Header
####################################################

$k5 = new k5(); $k5->db_open();

####################################################
##### Supported Site Channels
####################################################

$limit = 10;

if (isset($_GET["writings"]))
{
	$entries = $k5->db->get_by(W_TABLE, array("public", "1"), NULL, "dateof", "DESC", $limit);
	$d = new k5date($entries[0]["dateof"]);

	$title = "mostly fresh";
	$description = "the latest updates.";
	$date = $d->get_rfc2822();

	for($i = 0; $i < sizeof($entries); $i++)
	{
		$d->k5date($entries[$i]["dateof"]);
		$body = explode("\n", $entries[$i]["body"]);

		$feed_items[$i]["title"] = $entries[$i]["title"];
		$feed_items[$i]["link"] = "http://".$_SERVER["HTTP_HOST"].$k5->w_table_wid_to_url($entries[$i]["wid"]);
		$feed_items[$i]["description"] = trim($body[0]);
		$feed_items[$i]["date"] = $d->get_rfc2822();
	}
}
else if (isset($_GET["comments"]))
{
	$entries = $k5->db->get_by(C_TABLE, NULL, NULL, "dateof", "DESC", $limit);
	$d = new k5date($entries[0]["dateof"]);

	$title = "fresh feedback";
	$description = "the latest commentary.";
	$date = $d->get_rfc2822();

	for($i = 0; $i < sizeof($entries); $i++)
	{
		$d->k5date($entries[$i]["dateof"]);
		$body = explode("\n", $entries[$i]["body"]);

		$feed_items[$i]["title"] = $entries[$i]["name"];
		$feed_items[$i]["link"] = "http://".$_SERVER["HTTP_HOST"].PATH_URL.(NICE_URL ? '/comments/' : '/comments.php');
		$feed_items[$i]["description"] = trim($body[0]);
		$feed_items[$i]["date"] = $d->get_rfc2822();
	}
}
else if (isset($_GET["tag"]))
{
	$tag = urldecode($_GET["tag"]);

	$entries = $k5->db->get_by(W_TABLE, "`tags` LIKE '%".mysql_real_escape_string($tag)."%' AND `public` = '1'", NULL, "dateof", "DESC", $limit);
	$d = new k5date($entries[0]["dateof"]);

	$title = "tagged as $tag";
	$description = "the latest updates, tagged as $tag.";
	$date = $d->get_rfc2822();

	for($i = 0; $i < sizeof($entries); $i++)
	{
		$d->k5date($entries[$i]["dateof"]);
		$body = explode("\n", $entries[$i]["body"]);

		$feed_items[$i]["title"] = $entries[$i]["title"];
		$feed_items[$i]["link"] = "http://".$_SERVER["HTTP_HOST"].$k5->w_table_wid_to_url($entries[$i]["wid"]);
		$feed_items[$i]["description"] = trim($body[0]);
		$feed_items[$i]["date"] = $d->get_rfc2822();
	}
}
else
{
	$d = new k5date();

	$title = "unknown feed";
	$description = "you have chosen an invalid feed.";
	$date = $d->get_rfc2822();
}

####################################################
##### RSS Feed Output
####################################################

if (stristr($_SERVER["HTTP_USER_AGENT"], "Mozilla")) // browser handling fix
{
	header("Content-type: application/xml");
}
else
{
	header("Content-type: application/rss+xml");		
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<rss version=\"2.0\">\n";
echo "<channel>\n";

echo "	<title>$title</title>\n";
echo "	<link>http://".$_SERVER["HTTP_HOST"].PATH_URL."/</link>\n";
echo "	<description>$description</description>\n";
echo "	<language>en-us</language>\n";
echo "	<lastBuildDate>$date</lastBuildDate>\n";
echo "	<ttl>60</ttl>\n\n";

for($i = 0; $i < sizeof($feed_items); $i++)
{
	echo "	<item>\n";
	echo "		<title><![CDATA[{$feed_items[$i]["title"]}]]></title>\n";
	echo "		<link>{$feed_items[$i]["link"]}</link>\n";
	echo "		<description><![CDATA[{$feed_items[$i]["description"]}]]></description>\n";
	echo "		<pubDate>{$feed_items[$i]["date"]}</pubDate>\n";
	echo "	</item>\n";
}

echo "</channel>\n";
echo "</rss>\n";

##### End PHP code, (c) 2006 kaulana.com

?>