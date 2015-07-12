<?php

##### K5AUTH.PHP

##### Powers the kaulana.com weblog, revision five.
##### Including this file provides OpenID-enabled authentication.

require_once('openid/consumer.php');
require_once('k5util.php');

####################################################
##### Definitions
####################################################

/*    This defines a processing URL which will forward cleanly to your script
 *    when performing OpenID authentication. This assumes you are using mod_rewrite
 *    and the provided .htaccess file.
 */

define('PROCESSING_URL', 'http://'.$_SERVER["HTTP_HOST"].PATH_URL.(NICE_URL ? '/login/' : '/k5login.php'));

/*    You may also want to change these images if you are in the mood for different icons.
 */

define('OPENID_IMAGE', PATH_URL.'/layout/openid.jpg');
define('LOGOUT_IMAGE', PATH_URL.'/layout/logout.jpg');

####################################################
##### OpenID Enabled Authentication
####################################################

/*    This code is based upon PHP OpenID 0.0.8.3, which is included in a separate directory.
 *    Use the below classes for dumb mode authentication.
 */

class k5auth extends ActionHandler
{
	static $ready;
	var $consumer;
	var $openid;
	var $message;

	function k5auth($start = true)
	// Initialize by starting a session (default behavior).
	{
		if (!self::$ready)
		{
			if ($start) session_start();

			self::$ready = $start;
		}
	}

	function authenticate($u = "")
	// Pseudonym for login(), but forces both a return URL and the default trust root.
	{
		return $this->login($u, "");
	}

	function login($return_url = "", $trust_root = "")
	// Attempt to establish the user's identity using OpenID. You can supply your own $trust_root
	// or else the entire domain will attempt to be included. Additionally, $return_url will instruct
	// the script where to go after a login attempt is made.
	//
	// In dumb mode, if we see openid_url in the request, we are just starting the transaction.
	// Else, we want to find openid.mode or openid_mode in order to finish the process.
	{
		if (!self::$ready) return false;

		if (isset($_GET["logout"])) $this->logout();

		if ($_SESSION["openid_url"]) // work is already done
		{
			$this->message = "Currently logged in.";
			return true;
		}

		$this->consumer = new k5consumer();
		$this->openid = isset($_GET["openid_url"]) ? $_GET["openid_url"] : $_POST["openid_url"];

		if (isset($_POST["openid.mode"]) || isset($_POST["openid_mode"])
			|| isset($_GET["openid.mode"]) || isset($_GET["openid_mode"])) // finish transaction
		{
			$consumer_url = isset($_GET["consumer_url"]) ? $_GET["consumer_url"] : $_POST["consumer_url"];
			$request = new ConsumerRequest($consumer_url, array_merge($_GET, $_POST), "GET");
			$response = $this->consumer->handle_response($request);

			$response->doAction($this); // control now goes to the overloaded functions
		}
		else if ($this->openid) // start transaction
		{
			$ret = $this->consumer->find_identity_info($this->openid);

			if (!$ret) // failure state - no server found
			{
				$this->message = "No OpenID server was found there.";
				return false;
			}

			list($consumer_id, $server_id, $server_url) = $ret;

			if (!$trust_root) $trust_root = "http://".$_SERVER["HTTP_HOST"];
			if (!$return_url) $return_url = $_SERVER["SCRIPT_URI"];

			// include a token for verification against a replay attack

			$token = get_token(); setcookie("openid_token", $token, time()+60, '/'); // one minute retrieval

			$return_url = oidUtil::append_args(PROCESSING_URL,
					array("openid_url" => $this->openid, "consumer_url" => $consumer_id,
						"openid_token" => $token, "openid_return" => $return_url));

			// now, redirect to wherever we need to handle the request at

			header("Location: ".$this->consumer->handle_request($server_id, $server_url, $return_url, $trust_root));
			exit;
		}
		else
		{
			$this->message = "No OpenID was provided.";
			return false;
		}
	}

	function logout()
	// Unestablish the user's identity.
	{
		if (!self::$ready) $this->open();
		$this->reset();
	}

	function generate_form($action = "")
	// Outputs a standardized form for receiving OpenID logins. Uses GET instead of POST.
	{
?>

<form method="get" action="<?php echo $action ?>">
<p>
<input type="text" name="openid_url" value="<?php echo $_GET["openid_url"] ?>" size="25" class="openid paren" />
<input type="submit" value="log in" class="paren" />
<acronym title="log in using your openid">?</acronym>
</p>
</form>
<br />

<?php
	}

	function generate_identity()
	// Outputs your current identity. Supply either image to have them used as part
	// of the identity display.
	{
		if ($_SESSION["openid_url"])
		{
			echo "<p>\n<a href=\"".$_SESSION["openid_url_href"]."\" class=\"paren\" ";
			echo "title=\"verified as ".$_SESSION["openid_url_base"]."\">";
			echo $_SESSION["openid_url"]."</a>\n";

			echo "<img alt=\"logged in via openid\" title=\"logged in via openid\" src=\"".OPENID_IMAGE."\" />\n";
			echo "<a href=\"?logout\" class=\"imagelink\"><img alt=\"click here to log out\" title=\"click here to log out\" src=\"".LOGOUT_IMAGE."\" /></a>\n";
			echo "</p>";
		}
		else if ($_SESSION["openid_message"])
		{
			echo "<p>".$_SESSION["openid_message"]." <span class='paren'>(Try again?)</span></p>\n";
		}
		else if ($this->message == "No OpenID server was found there.")
		{
			echo "<p>No OpenID server was found there.";

			if (!strstr($this->openid, "www.")) echo " <span class='paren'>(Try adding the www?)</span>";
			else echo " <span class='paren'>(Try again?)</span>";

			echo "</p>\n";
		}
		else // you have no identity yet
		{
			echo "<!-- you are not logged in via openid -->";
		}

		if (isset($_SESSION["openid_message"])) unset($_SESSION["openid_message"]);
	}

	function open()
	// Start a session.
	{
		if (self::$ready)
		{
			trigger_error("Session already established.", E_USER_WARNING);
			return false;
		}

		session_start(); self::$ready = true;
	}

	function close()
	// End a session (retain session data).
	{
		if (!self::$ready)
		{
			trigger_error("Session not established.", E_USER_WARNING);
			return false;
		}

		session_commit(); self::$ready = false;
	}

	function reset()
	// Completely end the session (delete session data).
	{
		if (!self::$ready)
		{
			trigger_error("Session not established.", E_USER_WARNING);
			return false;
		}

		if (isset($_COOKIE[session_name()])) // kill the session cookie
		{
			setcookie(session_name(), "", time()-42000, "/");
		}

		$_SESSION = ""; session_destroy(); self::$ready = false;
	}

	function doValidLogin($login)
	// Identity verified - establish a session with the details.
	{
		if (!self::$ready) $this->open();

		$canonical_openid = isset($_GET["consumer_url"]) ? $_GET["consumer_url"] : $_POST["consumer_url"];

		$_SESSION["openid_url"]      = $this->openid;     // as provided by the user
		$_SESSION["openid_url_href"] = $canonical_openid; // properly resolved for an <a> tag
		$_SESSION["openid_url_base"] = $login->identity;  // true openid (no delegation)

		$this->message = "Your OpenID was successfully verified.";
		return true;
	}

	function doInvalidLogin()
	// Identity not verified due to a bad login.
	{
		$this->message = "Your OpenID could not be verified, possibly due to a timeout.";
		return false;
	}

	function doUserCancelled()
	// Identity not verified due to a user cancellation.
	{
		$this->message = "You cancelled the verification.";
		return false;
	}

	function doErrorFromServer($msg)
	// Identity not verified due to a server error.
	{
		$this->message = "The server had a problem ($msg).";
		return false;
	}

	function doCheckAuthRequired($server_url, $return_url, $post_data)
	// Finish the required check as a dumb consumer.
	{
		if (!$this->openid) $this->openid = isset($_GET["openid_url"]) ? $_GET["openid_url"] : $_POST["openid_url"];

		$response = $this->consumer->check_auth($server_url, $return_url, $post_data, $this->openid);
		$response->doAction($this);
	}
};

class k5consumer extends OpenIDConsumer
{
	function verify_return_to($url)
	// Check for server redirection with the return URL.
	{
		$sub = parse_url($url);

		if (!isset($sub["port"])) $sub["port"] = ($sub["scheme"] == "https" ? 443 : 80);

		$token = isset($_GET["openid_token"]) ? $_GET["openid_token"] : $_POST["openid_token"];

		return ($sub["host"] == $_SERVER["SERVER_NAME"] && $sub["port"] == $_SERVER["SERVER_PORT"]
				&& isset($_COOKIE["openid_token"]) && $_COOKIE["openid_token"] == $token);
	}
};

##### End PHP code, (c) 2006 kaulana.com

?>