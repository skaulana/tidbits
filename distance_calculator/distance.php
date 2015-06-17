<?php

##### DISTANCE.PHP

##### Given the latitude/longitude of a location and an SQL table full
##### of similar coordinates, calculate and display distances.

htmlOpen();

##### Process input if given.

##### Assuming MySQL, and that the fields "id", "lat", and "lng"
##### are specified for every element of the "locations" table.

if (isset($_POST["lat"]) && isset($_POST["lng"]))
{
	// Grab the user's data.

	$lat = trim(stripslashes($_POST["lat"]));
	$lng = trim(stripslashes($_POST["lng"]));

	// Connect to MySQL database.

	$db = mysql_connect("localhost", "user", "pass") or die(mysql_error());
	mysql_select_db("sample_latlng_db", $db) or die(mysql_error());

	$sql = "SELECT * FROM locations ORDER BY id ASC";
	$result = mysql_query($sql); $total = mysql_num_rows($result);

	// Run through the database and start displaying distances.
	// Stop if an error is encountered (and notify the user).

	$error = "";

	echo "<div id=\"instructions\">\n\n";

	for($i = 0; $i < $total && !$error; $i++)
	{
		$entry = mysql_fetch_array($result);
		$d = distance($lat, $lng, $entry["lat"], $entry["lng"]);

		if (!$d) $error = "Distance calculation failed. Did you enter your location in the right format?";
		else echo "Distance to ".$entry["id"].": $d miles<br>\n";
	}

	if ($error) echo $error;

	echo "</div><br>\n";

	// Close database connection.

	mysql_close($db) or die(mysql_error());

}

##### Ask user for latitude/longitude information.

?>

<div id="instructions">

Specify a latitude and longitude to calculate from.<br><br>

You may input your coordinates as <b>[deg] [min]</b> or <b>[deg]</b>, where 
[deg] represents degrees and [min] represents minutes (fractions of a degree).
Use negative degrees to specify west and south.<br><br>

<form method=POST action="<?php echo $_SERVER["PHP_SELF"] ?>">

<table width="80%" cellspacing="5" cellpadding="0" border="0">
<tr><td>Latitude:</td><td><input type="text" name="lat"></td></tr>
<tr><td>Longitude:</td><td><input type="text" name="lng"></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Calculate!"></td></tr>
</table>

</form>

</div>

<?php

htmlClose();

##### Math functions.

// Helper to calculate arclength from input.
//
function distance($lat1, $lng1, $lat2, $lng2)
{
	$a1 = parse_coord($lat1); $a2 = parse_coord($lat2);
	$b1 = parse_coord($lng1); $b2 = parse_coord($lng2);

	if ($a1 === false || $a2 === false
     || $b1 === false || $b2 === false) return false;

	return round(alen($a1, $b1, $a2, $b2), 2);
}

// Given the latitudes and longitudes purely in degrees,
// apply a distance formula. Answer given in miles.
//
function alen($a1, $b1, $a2, $b2)
{
	$r_earth_mi = 3963.1676;

	// First, convert degrees to radians for use with PHP math functions.

	$a1 = deg2rad($a1); $b1 = deg2rad($b1);
	$a2 = deg2rad($a2); $b2 = deg2rad($b2);

	// Now calculate arclength across the surface of the earth.

	return ($r_earth_mi * acos( cos($a1) * cos($b1) * cos($a2) * cos($b2)
        + cos($a1) * sin($b1) * cos($a2) * sin($b2) + sin($a1) * sin($a2) ));
}

// Convert a latitude or longtitude to pure degrees.
// Expected format is <deg> <min> or <deg>.
// Returns false if input was not specified correctly.
//
function parse_coord($location)
{
	// Parse and do some quick error checking.

	$coords = explode(" ", $location);

	if (sizeof($coords) == 0 || sizeof($coords) > 2) return false;
    
	$degree = $coords[0]; $minute = sizeof($coords) == 2 ? $coords[1] : "";

	if (!is_numeric($degree)) return false;
	if ($minute && !is_numeric($minute)) return false;

	// Add minutes into degrees, if specified.

	if ($minute) $degree += ($minute / 60);

	return $degree;
}

##### Layout functions.

// Begin generating HTML for layout. Call before output.
//
function htmlOpen()
{
	@ob_start();

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n\n";

	echo "<html lang=\"en\"><head>\n";
	echo "<title>Point-to-point distance calculator</title>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">\n\n";

	echo "<style>\n";
	echo "body { margin: 10px; background-color: #FFF; }\n";
	echo "#instructions { width: 500px; border: 1px solid #999; padding: 5px; font-family: Arial, sans-serif; color: #333; font-size: 14px; line-height: 18px; text-align: left; background-color: #EEA; }\n";
	echo "</style>\n\n";

	echo "</head>\n\n";

	echo "<body><div align=\"center\">\n\n";
}

// Stop generating HTML for layout. Call after output.
//
function htmlClose()
{
	echo "</div></body>\n\n";
	echo "</html>";

	@ob_end_flush();
}

##### End PHP code.

?>