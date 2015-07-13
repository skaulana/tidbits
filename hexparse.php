<html><head><title>BlockRAM Initializer</title>
</head><body>

<?php

if (isset($_POST["to_convert"]))
{
	$str = trim(stripslashes($_POST["to_convert"]));
	$str = str_replace("\n", "", $str);
	$str = str_replace(" ", "", $str);
	$str = str_replace("\r", "", $str);
	$str = strtoupper($str);

	$res = trim(stripslashes($_POST["resolution"]));

	$i = 0; $k = 0;
	for($j = 0; $j < strlen($str) ; $j++)
	{
		if ($i == 64) { $i = 0; $k++; }
		$arr[$k][$i] = substr($str, $j, 1);
		$i++;
	}

	if ($res == "8") $div = 2;
	if ($res == "16") $div = 4;
	if ($res == "32") $div = 8;

	$k = 0; $l = 0; $z = 0;
	for($i = 0; $i < sizeof($arr); $i++)
	{
		if ($k == 0) echo "RAMB4_S$res BlockRam".$l."(<br>\n.CLK(Clock), .RST(1'b0), .WE(1'b0), .EN(1'b1), .ADDR(Address), .DI($res'b0), .DO(DOut[".$l."])<br>\n);<br>\n";
		echo "defparam BlockRam".$l.".INIT_".hexer($k)." = 256'h";

		$thestr = "";
		for($j = 0; $j < sizeof($arr[$i]) / $div; $j++)	for($m = $div - 1; $m >= 0; $m--) $thestr = $arr[$i][($div*$j)+$m].$thestr;
		$z = $j;
		for($j = sizeof($arr[$i]) / $div; $j < 64 / $div; $j++) for($m = $div - 1; $m >= 0; $m--) $thestr = "0$thestr";
		echo "$thestr;<br>\n";
		$k++; if ($k == 16) { $k = 0; $l++; echo "<br>\n"; }
	}

	for($n = $k; $n < 16; $n++) echo "defparam BlockRam".$l.".INIT_".hexer($n)." = 256'h0000000000000000000000000000000000000000000000000000000000000000;<br>\n";

	echo "<br>\n// Done at $res-bit address $z in BlockRam".($l)."<br><br>\n";
	if ($l > 159) echo "<b>// WARNING: BlockRAM limit is <u>160</u> in a XCV200E!</b><br><br>\n";
}

function hexer($i) {
if ($i == 0) return "00"; if ($i == 1) return "01"; if ($i == 2) return "02";
if ($i == 3) return "03"; if ($i == 4) return "04"; if ($i == 5) return "05";
if ($i == 6) return "06"; if ($i == 7) return "07"; if ($i == 8) return "08";
if ($i == 9) return "09"; if ($i == 10) return "0A"; if ($i == 11) return "0B";
if ($i == 12) return "0C"; if ($i == 13) return "0D"; if ($i == 14) return "0E";
if ($i == 15) return "0F"; return "XX";
}

?>

<br><br>
Paste me some hex below:<br><br>
<form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
<textarea name="to_convert" rows=30 cols=60><?php echo trim(stripslashes($_POST["to_convert"])) ?></textarea><br><br>Byte-reversal compensation modality selection:<br><br>
<input type="radio" name="resolution" value="8" checked> 8-bit<br>
<input type="radio" name="resolution" value="16"> 16-bit<br>
<input type="radio" name="resolution" value="32"> 32-bit (untested)<br><br>
<input type="submit" value="Eat me"><br>
</form><br><br>
</body></html>
