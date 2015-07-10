<?php

##### Online chess education and demonstration tool.
##### Comments: s {atsign} kaulana.com

htmlStart();

if (isset($_POST["changeboard"])) changeBoardFile(trim(stripslashes(basename($_POST["changeboard"]))));

if (isset($_GET["action"]) && $_GET["action"] == "move")
{
	$board = getBoard(getBoardFile(""));
	$i1 = strpos("abcdefgh", substr($_POST["square1"], 0, 1));
	$j1 = 8 - substr($_POST["square1"], 1, 1);

	$i2 = strpos("abcdefgh", substr($_POST["square2"], 0, 1));
	$j2 = 8 - substr($_POST["square2"], 1, 1);

	if ($board[$j1][$i1] != "")
	{
		$board[$j2][$i2] = $board[$j1][$i1];
		$board[$j1][$i1] = "";
	}

	$fh = fopen(getBoardFile(""), "w"); flock($fh, LOCK_EX);
	for($i = 0; $i < 8; $i++)
	{
		for($j = 0; $j < 8; $j++)
		{
			if ($board[$i][$j] == "") fputs($fh, "-");
			else fputs($fh, $board[$i][$j]);
		}
		fputs($fh, "\n");
	}
	flock($fh, LOCK_UN); fclose($fh);
}

if (isset($_GET["action"]) && $_GET["action"] == "change")
{
	$text = "abcdefgh"; $newarr = "";
	for($i = 0; $i < 8; $i++)
	{
		for($j = 0; $j < 8; $j++)
		{
			$item = trim(stripslashes($_POST[substr($text, $j, 1).(8-$i)]));
			if ($item == "") $newarr[$i] .= "-";
			else $newarr[$i] .= $item;
		}
	}
	if ($_POST["saveascheck"]) $file = trim(stripslashes(basename($_POST["saveas"])));
	else $file = getBoardFile("");

	$fh = fopen($file, "w"); flock($fh, LOCK_EX);
	for($i = 0; $i < 8; $i++) fputs($fh, $newarr[$i]."\n");
	flock($fh, LOCK_UN); fclose($fh);

	if ($_POST["saveascheck"]) changeBoardFile($file);
}

if (isset($_GET["reset"])) $board = getBoard(getBoardFile("reset"));
else $board = getBoard(getBoardFile(""));
$colors = ""; $protectgraph = false;

if (isset($_POST["attacks"]))
{
	$i = translator($_POST["attacks"], "i");
	$j = translator($_POST["attacks"], "j");
	$attacks = rangeOfMovement($board, $i, $j);
	$attacks = metastr_replace("FRIEND", "", $attacks);
	$attacks = metastr_replace("ENEMY", "*", $attacks);

	$colors = metastr_replace("*", "#99cccc", $attacks);
	$colors = metastr_replace("%", "#ffff99", $colors);
}

if (isset($_POST["threats"]))
{
	$i = translator($_POST["threats"], "i");
	$j = translator($_POST["threats"], "j");

	for($count = 0; $count < 8; $count++) { for($k = 0; $k < 8; $k++) $newboard[$count][$k] = ""; }
	$newboard[$i][$j] = "%";

	for($count = 0; $count < 8; $count++)
	{
		for($k = 0; $k < 8; $k++)
		{
			$attacks = rangeOfMovement($board, $count, $k);
			if ($attacks[$i][$j] == "ENEMY") $newboard[$count][$k] = "*";
		}
	}
	$colors = metastr_replace("*", "#99cccc", $newboard);
	$colors = metastr_replace("%", "#ffff99", $colors);
}

if (isset($_GET["view"]))
{
	if ($_GET["view"] == "offwhite") $offcheck = "w";
	if ($_GET["view"] == "offblack") $offcheck = "b";

	if ($_GET["view"] == "prowhite") $protect = "w";
	if ($_GET["view"] == "problack") $protect = "b";
	
	if (isset($offcheck))
	{
		$cboard = colorize($board);
		for($i = 0; $i < 8; $i++) { for($j = 0; $j < 8; $j++) $dboard[$i][$j] = ""; }
		for($i = 0; $i < 8; $i++)
		{
			for($j = 0; $j < 8; $j++)
			{
				if ($cboard[$i][$j] == $offcheck)
				{
					$dboard = stackarray($dboard, rangeOfMovement($board, $i, $j));
				}
			}
		}
		for($i = 0; $i < 8; $i++)
		{
			for($j = 0; $j < 8; $j++)
			{
				if ($cboard[$i][$j] == $offcheck)
				{
					$dboard[$i][$j] = "%";
				}
			}
		}
		$dboard = metastr_replace("FRIEND", "", $dboard);
		$dboard = metastr_replace("ENEMY", "*", $dboard);

		$colors = metastr_replace("*", "#99cccc", $dboard);
		$colors = metastr_replace("%", "#ffff99", $colors);
	}

	if (isset($protect))
	{
		$cboard = colorize($board); $protectgraph = true;
		for($i = 0; $i < 8; $i++) { for($j = 0; $j < 8; $j++) $dboard[$i][$j] = 0; }
		for($i = 0; $i < 8; $i++)
		{
			for($j = 0; $j < 8; $j++)
			{
				$attacks = rangeOfMovement($board, $i, $j);
				for($count = 0; $count < 8; $count++)
				{
					for($k = 0; $k < 8; $k++)
					{
						if ($attacks[$count][$k] == "ENEMY" &&
						   (($cboard[$i][$j] == "b" && $protect == "w") ||
						    ($cboard[$i][$j] == "w" && $protect == "b")))
							$dboard[$count][$k]--;
						if ($attacks[$count][$k] == "FRIEND" && $cboard[$i][$j] == $protect)
							$dboard[$count][$k]++;
					}
				}
			}
		}
		for($i = 0; $i < 8; $i++)
		{
			for($j = 0; $j < 8; $j++)
			{
				if ($cboard[$i][$j] != $protect) $dboard[$i][$j] = "";
				else if ($dboard[$i][$j] < 0) $dboard[$i][$j] = "@";
				else if ($dboard[$i][$j] > 0) $dboard[$i][$j] = "*";
				else $dboard[$i][$j] = "%";
			}
		}
		$colors = metastr_replace("*", "#99cccc", $dboard);
		$colors = metastr_replace("%", "#ffff99", $colors);
		$colors = metastr_replace("@", "#cc9999", $colors);
	}
}

displayBoard($board, $colors);
htmlBridge();

echo "<b>pupil</b> .. (".httpget("", "reload page").")\n";

echo "<form method=POST action=\"".httppost("")."\">view attack lines for piece at .. ";
echo "<input type=text name=\"attacks\" size=2> <input type=submit value=\"show\"><br>\n";
echo "full offensive capabilities .. (".httpget("?view=offwhite", "white").") (".httpget("?view=offblack", "black").")\n";
echo "</form>\n";

echo "<form method=POST action=\"".httppost("")."\">view immediate threats on piece at .. ";
echo "<input type=text name=\"threats\" size=2> <input type=submit value=\"show\"><br>\n";
echo "protections graph .. (".httpget("?view=prowhite", "white").") (".httpget("?view=problack", "black").")\n";
echo "</form>\n";

if ($protectgraph)
{
	echo "protections graph  .. legend<br><br>\n";
	echo "<font color=\"#99cccc\">piece</font> has more protections than threats on it<br>\n";
	echo "<font color=\"#ffff99\">piece</font> has equal protections and threats (or none)<br>\n";
	echo "<font color=\"#cc9999\">piece</font> has more threats on it than protections<br><br>\n";
}

echo "<b>instructor</b> .. reading from ".getBoardFile("")." .. (".httpget("?reset=true", "reset board").")<br>\n";

echo "<form method=POST action=\"".httppost("")."\">change remote board .. ";
echo "<input type=text name=\"changeboard\" size=20>&nbsp;<input type=submit value=\"load\"></form>\n";

echo "<form method=POST action=\"".httppost("?action=move")."\">make a move .. from ";
echo "<input type=text name=\"square1\" size=2> to <input type=text name=\"square2\" size=2>";
echo "&nbsp;<input type=submit value=\"move piece\"></form>\n";

echo "<form method=POST action=\"".httppost("?action=change")."\">edit current board .. <br><br>\n";

for($i = 0; $i < 8; $i++)
{
	$text = "abcdefgh"; $k = 8 - $i;
	for($j = 0; $j < 8; $j++)
	{
		echo "<input type=text name=\"";
		echo substr($text, $j, 1)."$k\" value=\"";
		echo $board[$i][$j]."\" size=2>&nbsp;\n";
	}
	echo "<br>\n";
}

echo "<br>\n";
echo "save to new file instead .. <input type=checkbox name=\"saveascheck\">&nbsp;<input type=text name=\"saveas\" size=20>";
echo "<br><input type=submit value=\"edit and save board\"></form>\n";

htmlEnd();

##### Chess related and miscellaneous functions.

function translator($input, $item)
// Figure out what square on the board we are talking about.
{
	if ($item == "j")
	{
		$view = "abcdefgh";
		return strpos($view, strtolower(substr($input, 0, 1)));
	}
	else if ($item == "i")
	{
		return 8 - substr($input, 1, 1);
	}
}

function getBoardFile($input)
// Figure out what board we are meant to be using.
{
	if (!file_exists("control.txt") || $input == "reset")
	{
		copy("newboard.txt", "board.txt");
		changeBoardFile("board.txt");
	}
	$fh = fopen("control.txt", "r"); flock($fh, LOCK_SH);
	$file = trim(fgets($fh)); flock($fh, LOCK_UN); fclose($fh);
	return $file;
}

function changeBoardFile($file)
// Chnage board in control file to $file.
{
	if (file_exists($file))
	{
		$fh = fopen("control.txt", "w"); flock($fh, LOCK_EX);
		fputs($fh, $file); flock($fh, LOCK_UN); fclose($fh);
	}
	else echo "could not change to $file .. file does not exist!<br><br>\n";
}

function rangeOfMovement($board, $i, $j)
// Designate what spaces the given piece can occupy.
{
	for($k = 0; $k < 8; $k++) { for($ct = 0; $ct < 8; $ct++) $newboard[$k][$ct] = ""; }

	$piece = $board[$i][$j]; $newboard[$i][$j] = "%";
	$cboard = colorize($board);

	if ($cboard[$i][$j] == "b")
	{
		$board = rotate180($board);
		$cboard = rotate180($cboard);
		$newboard = rotate180($newboard);
		$i = 7 - $i; $j = 7 - $j;
	}

	if (strtolower($piece) == "p") // pawn
	{
		$newboard = stackarray($newboard, fillupleft($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, fillupright($board, $i, $j, "*", 1));
	}

	else if (strtolower($piece) == "b") // bishop
	{
		$newboard = stackarray($newboard, fillupleft($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillupright($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, filldownleft($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, filldownright($board, $i, $j, "*", 10));
	}
	else if (strtolower($piece) == "r") // rook
	{
		$newboard = stackarray($newboard, fillup($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, filldown($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillleft($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillright($board, $i, $j, "*", 10));
	}
	else if (strtolower($piece) == "q") // queen
	{
		$newboard = stackarray($newboard, fillupleft($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillupright($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, filldownleft($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, filldownright($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillup($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, filldown($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillleft($board, $i, $j, "*", 10));
		$newboard = stackarray($newboard, fillright($board, $i, $j, "*", 10));
	}
	else if (strtolower($piece) == "k") // king
	{
		$newboard = stackarray($newboard, fillupleft($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, fillupright($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, filldownleft($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, filldownright($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, fillup($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, filldown($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, fillleft($board, $i, $j, "*", 1));
		$newboard = stackarray($newboard, fillright($board, $i, $j, "*", 1));
	}
	else if (strtolower($piece) == "n") // knight
	{
		$newboard = stackarray($newboard, fillknight($board, $i, $j, "*"));
	}

	if ($cboard[$i][$j] == "b")
	{
		$board = rotate180($board);
		$cboard = rotate180($cboard);
		$newboard = rotate180($newboard);
		$i = 7 - $i; $j = 7 - $j;
	}

	return $newboard;
}

function stackarray($arr1, $arr2)
// Save $arr2's values onto empty spaces in $arr1.
{
	for($i = 0; $i < 8; $i++)
	{
		for($j = 0; $j < 8; $j++)
		{
			if ($arr1[$i][$j] == "") $arr1[$i][$j] = $arr2[$i][$j];
		}
	}
	return $arr1;
}

function fillupleft($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "up", "left");
}

function fillupright($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "up", "right");
}

function filldownleft($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "down", "left");
}

function filldownright($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "down", "right");
}

function fillup($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "up", "");
}

function filldown($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "down", "");
}

function fillleft($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "", "left");
}

function fillright($board, $i, $j, $filler, $steps)
// Specialized alias for filler(). See filler().
{
	return filler($board, $i, $j, $filler, $steps, "", "right");
}

function filler($board, $i, $j, $filler, $steps, $mp1, $mp2)
// Fill an array as specified, starting with element[$i][$j] and continuing until $steps or an obstruction.
// When an obstruction is reached, if it is the same color as element[$i][$j] it will not be filled, otherwise will.
{
	for($k = 0; $k < 8; $k++) { for($ct = 0; $ct < 8; $ct++) $newboard[$k][$ct] = ""; }
	$cboard = colorize($board); $color = $cboard[$i][$j];

	if ($mp1 == "up") { $add1 = -1; if ($i > 0) $condition1 = true; }
	else if ($mp1 == "down") { $add1 = 1; if ($i < 7) $condition1 = true; }
	else { $add1 = 0; $condition1 = true; }

	if ($mp2 == "left") { $add2 = -1; if ($j > 0) $condition2 = true; }
	else if ($mp2 == "right") { $add2 = 1; if ($j < 7) $condition2 = true; }
	else { $add2 = 0; $condition2 = true; }

	for ($count = 0; $count < $steps && $condition1 && $condition2; $count++)
	{
		$condition1 = false; $condition2 = false;
		$i += $add1; $j += $add2; $curr = $cboard[$i][$j];

		if (($curr == "b" && $color == "b") || ($curr == "w" && $color == "w"))
		{
			$newboard[$i][$j] = "FRIEND";
			return $newboard;
		}

		$newboard[$i][$j] = $filler;

		if (($curr == "b" && $color == "w") || ($curr == "w" && $color == "b"))
		{
			$newboard[$i][$j] = "ENEMY";
			return $newboard;
		}

		if ($mp1 == "up") { if ($i > 0) $condition1 = true; }
		else if ($mp1 == "down") { if ($i < 7) $condition1 = true; }
		else $condition1 = true;

		if ($mp2 == "left") { if ($j > 0) $condition2 = true; }
		else if ($mp2 == "right") { if ($j < 7) $condition2 = true; }
		else $condition2 = true;
	}
	return $newboard;
}

function fillknight($board, $i, $j, $filler)
// Assumes a knight is at element[$i][$j] and calculates attacks similar to filler().
{
	for($k = 0; $k < 8; $k++) { for($ct = 0; $ct < 8; $ct++) $newboard[$k][$ct] = ""; }
	$cboard = colorize($board); $color = $cboard[$i][$j];

	$move[0] = array($i-1, $j-2); $move[1] = array($i-2, $j-1);
	$move[2] = array($i-2, $j+1); $move[3] = array($i-1, $j+2);
	$move[4] = array($i+1, $j-2); $move[5] = array($i+2, $j-1);
	$move[6] = array($i+2, $j+1); $move[7] = array($i+1, $j+2);

	for($count = 0; $count < sizeof($move); $count++)
	{
		if ($move[$count][0] >= 0 && $move[$count][0] <= 7 && $move[$count][1] >= 0 && $move[$count][1] <= 7)
		{
			$curr = $cboard[$move[$count][0]][$move[$count][1]];
			if (($curr == "b" && $color == "b") || ($curr == "w" && $color == "w"))	$newboard[$move[$count][0]][$move[$count][0]] = "FRIEND";
			else if (($curr == "b" && $color == "w") || ($curr == "w" && $color == "b")) $newboard[$move[$count][0]][$move[$count][1]] = "ENEMY";
			else $newboard[$move[$count][0]][$move[$count][1]] = $filler;
		}
	}
	return $newboard;
}

function rotate90($arr)
// Rotate a two dimensional array 90 degrees clockwise.
{
	$newarr = "";
	for($i = 0; $i < sizeof($arr); $i++)
	{
		for($j = 0; $j < sizeof($arr[$i]); $j++)
		{
			$newarr[$i][$j] = $arr[7 - $j][$i];
		}
	}
	return $newarr;
}

function rotate180($arr)
// Rotate a two dimensional array 180 degrees around.
{
	$newarr = array("", "", "", "", "", "", "", "");
	for($i = 0; $i < 8; $i++)
	{
		$newarr[$i] = array("", "", "", "", "", "", "", "");
		for($j = 0; $j < 8; $j++)
		{
			$newarr[$i][$j] = $arr[7 - $i][7 - $j];
		}
	}
	return $newarr;
}

function colorize($board)
// Return a board with "w" or "b" for white or black.
{
	for($i = 0; $i < 8; $i++)
	{
		for($j = 0; $j < 8; $j++)
		{
			if ($board[$i][$j] != "")
			{
				if ($board[$i][$j] == strtolower($board[$i][$j])) $newboard[$i][$j] = "w";
				else $newboard[$i][$j] = "b";
			}
			else $newboard[$i][$j] = "";
		}
	}
	return $newboard;
}

function metastr_replace($from, $to, $arr)
// Replace strings in a two dimesional array.
{
	$newarr = "";
	for($i = 0; $i < sizeof($arr); $i++)
	{
		for($j = 0; $j < sizeof($arr[$i]); $j++)
		{
			$newarr[$i][$j] = str_replace($from, $to, $arr[$i][$j]);
		}
	}
	return $newarr;
}

##### HTML control.

function htmlStart()
// Start coding the page.
{
	echo "<html><head><title>chess strategy visualizer by kaulana.com</title>\n";
	echo "<style type=\"text/css\">\n";
    echo "img { border-width: 0; border-style: none }\n";
    echo "body { margin: 0px; background-color: #FFFFFF }\n";
    echo "input, textarea { background: #FFFFFF; border: 1 solid #666666 }\n";
    echo "p, li, td, body, input, textarea { font-family: Verdana, Arial, sans-serif; font-size: 11px; color: #333333 }\n";
    echo "a { color: #990000; text-decoration: none }\n";
    echo "a:hover { color: #CC3333; text-decoration: underline }\n";
    echo "a:active { color: #CC0000; text-decoration: none }\n";
    echo "</style></head>\n<body><table width=\"100%\" height=\"100%\" cellspacing=0 cellpadding=3 border=0>\n";
	echo "<tr><td width=450 valign=center>\n";
}

function htmlBridge()
// After the board, leave room for second column.
{
	echo "\n</td><td valign=top><br><br>\n";
}

function htmlEnd()
// End coding the page.
{
	echo "\n</td></tr></table>\n</body></html>";
}

function httpget($url, $text)
// Shorthand for self referencing the page with HTTP/GET.
{
	return "<a href=\"".$_SERVER["PHP_SELF"]."$url\">$text</a>";
}

function httppost($url)
// Shorthand for self referencing the page with HTTP/POST action.
{
	return $_SERVER["PHP_SELF"].$url;
}

function getBoard($file)
// Parses a text file into a two-dimensional board array.
{
	$fh = fopen($file, "r"); flock($fh, LOCK_SH);
	for($i = 0; $i < 8; $i++)
	{
		$line = trim(fgets($fh));
		for($j = 0; $j < 8; $j++)
		{
			$board[$i][$j] = substr($line, $j, 1);
			if (!stristr("prnbqk", $board[$i][$j])) $board[$i][$j] = "";
		}
	}
	flock($fh, LOCK_UN); fclose($fh);
	return $board;
}

function displayBoard($board, $colors)
// Display $board and format according to board style.
// Uses the background colors in $colors if given.
// Lowercase is white - uppercase black. P, R, N, B, Q, K, all others blank.
// Returns the board it gets in a two dimensional array.
{
	echo "<center><table width=420 height=420 cellspacing=0 cellpadding=0 border=0>\n";
	echo "<tr><td width=20 height=20>&nbsp;</td>\n";

	$alpha = "abcdefgh";
	for($i = 0; $i < 8; $i++) echo "<td width=50 height=20 valign=top><center>".substr($alpha, $i, 1)."</center></td>\n";
	echo "</tr><tr><td width=20 height=50 valign=center>8</td>\n";
	echo "<td rowspan=8 colspan=8 width=400 height=400>\n";

	echo "<center><table width=400 height=400 cellspacing=0 cellpadding=0 border=0 style=\"border: 1 solid #333333\">\n";
	for($i = 0; $i < 8; $i++)
	{
		echo "<tr>\n";
		for($j = 0; $j < 8; $j++)
		{
			if (($i % 2 == 0 && $j % 2 == 0) || ($i % 2 == 1 && $j % 2 == 1))
			$color = "#ffffff"; else $color = "#cccccc";
			if ($colors[$i][$j]) $color = $colors[$i][$j];

			echo "<td width=50 height=50 valign=center bgcolor=\"$color\"";
			echo "style=\"border-top: 1 solid #999999; border-right: 1 solid #999999\"><center>\n";

			$piece = $board[$i][$j]; $cboard = colorize($board);
			$prefix = $cboard[$i][$j];

			if ($prefix == "w") $full = "white";
			else { $full = "black"; $piece = strtolower($piece); }

			if ($piece == "p") $name = "pawn";
			else if ($piece == "r") $name = "rook";
			else if ($piece == "n") $name = "knight";
			else if ($piece == "b") $name = "bishop";
			else if ($piece == "q") $name = "queen";
			else if ($piece == "k") $name = "king";
			else $name = "";

			if ($name != "") echo "<img src=\"$prefix$name.gif\" alt=\"$full $name\">\n";
			else echo "<p>&nbsp;&nbsp;</p>\n";

			echo "</center></td>\n";
		}
		echo "</tr>\n";
	}
	echo "</table></center>\n";
	echo "</td></tr>\n";

	for($i = 7; $i > 0; $i--) echo "<tr><td width=20 height=50 valign=center>$i</td></tr>\n";
	echo "</table>\n";
}

##### End PHP code

?>
