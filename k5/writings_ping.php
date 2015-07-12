<?php

##### WRITINGS_PING.PHP

##### Powers the kaulana.com weblog, revision five.
##### Including this page will execute a series of preprogrammed pings.

require_once('xmlrpc/xmlrpc.php');
require_once('k5.php');

####################################################
##### Script Header
####################################################

/*    Assumptions:
 *
 *    A k5 object named $k5 has already been declared and initialized.
 *
 *    The following ping services use APIs accurate as of July 27, 2006.
 */

$site_name = "*** CHANGE THIS TO YOUR SITE NAME ***";
$site_url = "http://".$_SERVER["HTTP_HOST"].PATH_URL."/";
$site_rss = "http://".$_SERVER["HTTP_HOST"].PATH_URL.(NICE_URL ? '/rss/writings/' : '/rss.php?writings');
$site_url_change = "http://".$_SERVER["HTTP_HOST"].PATH_URL.(NICE_URL ? '/writings/' : '/writings.php');

$site_name = XMLRPC_prepare($site_name);
$site_url = XMLRPC_prepare($site_url);
$site_rss = XMLRPC_prepare($site_rss);
$site_url_change = XMLRPC_prepare($site_url_change);

/*    General macro for the XML-RPC request.
 */

function do_xml_rpc($service_url, $service_name, $host, $path, $call, $params = "")
{
	list($xmlrpc_ok, $xmlrpc_return) = XMLRPC_request($host, $path, $call, $params);

	echo "<li><a href=\"$service_url\">$service_name</a>: ";
	echo $xmlrpc_ok ? "ping succeeded." : "ping failed! <span class='paren'>(";
	if (!$xmlrpc_ok) print_r($xmlrpc_return);
	echo $xmlrpc_ok ? "" : ")</span>";
	echo "</li>\n";
}

if (k5html::$defined_ob) @ob_end_flush();

echo "<blockquote>\n";
echo "<h1>sending pings</h1>\n";
echo "<ul>\n";

####################################################
##### Weblogs.com
####################################################

$xmlrpc_host = "rpc.weblogs.com";
$xmlrpc_path = "/RPC2";
$xmlrpc_call = "weblogUpdates.extendedPing";
$xmlrpc_params = array($site_name, $site_url, $site_url_change, $site_rss);

do_xml_rpc("http://www.weblogs.com/", "weblogs.com", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### blo.gs
####################################################

$xmlrpc_host = "ping.blo.gs";
$xmlrpc_path = "/";
$xmlrpc_call = "weblogUpdates.extendedPing";
$xmlrpc_params = array($site_name, $site_url, $site_url_change, $site_rss);

do_xml_rpc("http://blo.gs/", "blo.gs", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### My Yahoo!
####################################################

$xmlrpc_host = "api.my.yahoo.com";
$xmlrpc_path = "/RPC2";
$xmlrpc_call = "weblogUpdates.ping";
$xmlrpc_params = array($site_name, $site_url);

do_xml_rpc("http://my.yahoo.com/", "my yahoo!", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### Technorati
####################################################

$xmlrpc_host = "rpc.technorati.com";
$xmlrpc_path = "/rpc/ping";
$xmlrpc_call = "weblogUpdates.ping";
$xmlrpc_params = array($site_name, $site_url);

do_xml_rpc("http://www.technorati.com/", "technorati", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### FeedBurner
####################################################

$xmlrpc_host = "ping.feedburner.com";
$xmlrpc_path = "/";
$xmlrpc_call = "weblogUpdates.ping";
$xmlrpc_params = array($site_name, $site_rss);

do_xml_rpc("http://feedburner.com/", "feedburner", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### Feedster
####################################################

$xmlrpc_host = "api.feedster.com";
$xmlrpc_path = "/";
$xmlrpc_call = "weblogUpdates.ping";
$xmlrpc_params = array($site_name, $site_rss);

do_xml_rpc("http://www.feedster.com/", "feedster", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### Syndic8
####################################################

$xmlrpc_host = "www.syndic8.com";
$xmlrpc_path = "/xmlrpc.php";
$xmlrpc_call = "weblogUpdates.extendedPing";
$xmlrpc_params = array($site_name, $site_url, $site_url_change, $site_rss);

do_xml_rpc("http://www.syndic8.com/", "syndic8", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### Blogrolling
####################################################

$xmlrpc_host = "rpc.blogrolling.com";
$xmlrpc_path = "/pinger/";
$xmlrpc_call = "weblogUpdates.ping";
$xmlrpc_params = array($site_name, $site_url);

do_xml_rpc("http://www.blogrolling.com/", "blogrolling", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### PubSub
####################################################

$xmlrpc_host = "xping.pubsub.com";
$xmlrpc_path = "/ping";
$xmlrpc_call = "weblogUpdates.ping";
$xmlrpc_params = array($site_name, $site_rss);

do_xml_rpc("http://www.pubsub.com/", "pubsub", $xmlrpc_host, $xmlrpc_path, $xmlrpc_call, $xmlrpc_params);

####################################################
##### Script Footer
####################################################

echo "</ul>\n</blockquote>\n<br />\n";

if (k5html::$defined_ob) @ob_start();

##### End PHP code, (c) 2006 kaulana.com

?>