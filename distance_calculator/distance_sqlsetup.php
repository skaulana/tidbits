<?php

##### DISTANCE_SQLSETUP.PHP

##### Bootstrap a SQL database with latitudes/longitudes for DISTANCE.PHP.

$csv = fopen("countries.csv", "r") or die("Couldn't find the CSV bootstrap.");

$db = mysql_connect("localhost", "user", "pass") or die(mysql_error());

mysql_query("DROP DATABASE IF EXISTS sample_latlng_db") or die (mysql_error());
mysql_query("CREATE DATABASE sample_latlng_db") or die (mysql_error());
mysql_select_db("sample_latlng_db") or die(mysql_error());
mysql_query("CREATE TABLE locations (id VARCHAR(4) NOT NULL, lat FLOAT, lng FLOAT, PRIMARY KEY (id))") or die (mysql_error());

while (!feof($csv))
{
    $coords = fgetcsv($csv); // parses each file line as CSV
    if ($coords)
    {
        mysql_query("INSERT INTO locations VALUES ('"
            . $coords[0] . "', "
            . $coords[1] . ", "
            . $coords[2] . ")") or die (mysql_error());
    }
}

mysql_close($db) or die(mysql_error());

echo "Bootstrap of sample_latlng_db & locations table completed.";

##### End PHP code.

?>