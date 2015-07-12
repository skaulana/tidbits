<?php

##### K5LOGIN.PHP

##### Powers the kaulana.com weblog, revision five.
##### This file finishes an OpenID login. Do not include this file.

require_once('k5auth.php');

####################################################
##### Authentication Finish
####################################################

$auth = new k5auth(true);
$status = $auth->authenticate();

$_SESSION["openid_message"] = $auth->message;

header("Location: ".(isset($_GET["openid_return"]) ? $_GET["openid_return"] : $_POST["openid_return"]));

##### End PHP code, (c) 2006 kaulana.com

?>