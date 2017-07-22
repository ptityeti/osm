<?php
// script that returns a GPX file
// input is a relationid that should be present in the db
// GPX file will contain one track with a track segment for each component

// include file containing db connection data etc.
include 'settings.inc.php';
include 'getorderednodes.php';

// create db connection
$dsn = "mysql:host=$db_server;dbname=$db_name;charset=utf8";
$dbConn = new PDO($dsn, $db_user, $db_pw);

$relationid = (int) $_GET['relationid'];
if(isset($_GET['downloadid']))
{
	$downloadid = (int) $_GET['downloadid'];
}
else // get the last downloaded version
{
	$sql = "SELECT MAX(id) FROM downloads WHERE endtime IS NOT NULL AND relationid = :relationid";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['relationid'=>$relationid]);
	$result = $stmt->fetch();
	$downloadid = $result[0];
} 

$gpxtemplate = <<<GPX
<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
<gpx version="1.1"
    creator="Kleine Yeti GPX generator"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="http://www.topografix.com/GPX/1/1"
    xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
<metadata>
<name>Walking route GPS track</name>
<desc>GPX file created based on OpenStreetMap data</desc>
</metadata>
<trk>
<src>OpenStreetMap</src>
<TRACKSEGMENTS>
</trk>
</gpx>
GPX;

$orderedNodes = getOrderedNodes($relationid, $downloadid, $dbConn);
foreach($orderedNodes as $trackSegment)
{
	$outputPoints = "<trkseg>\r\n";
	foreach($trackSegment as $trackPoint)
	{
		$orderedNodes .= '<trkpt lat="'. $trackPoint['lat'] . '" lon="' . $trackPoint['lon'] . '"></trkpt>' . "\r\n";
	}
	$outputPoints = "</trkseg>\r\n";
}

print(str_replace('<TRACKSEGMENTS>', $outputPoints, $gpxTemplate));
?>
