<?php
// show what there is to be known about one specific relation

// include file containing db connection data etc.
include 'settings.inc.php';

include 'getorderednodes.php';

// create db connection
$dsn = "mysql:host=$db_server;dbname=$db_name;charset=utf8";
$dbConn = new PDO($dsn, $db_user, $db_pw);

$pageTemplate = <<<HTML
<html>
<head></head>
<body>
<h1>Details of relation XXXXX (XXXXXXXXXX)</h1>
<h2>Tags and basic info</h2>
All tags of the relation:
<table>
<tr><th>Key</th><th>Value</th></tr>
<TAGTABLE>
</table>
Total length of the ways: <TOTALLENGTH><br> 
Number of components: <COMPONENTS><br>
<h2>Type of ways</h2>
<table>
<tr><th>Type</th><th>Total length (km)</th><th>Total length (%)</th><th>Number of ways</th></tr>
<HIGHWAYTABLE>
</table>
Detail for the tracks
<table>
<tr><th>Tracktype</th><th>Total length</th><th>Number of ways</th></tr>
<TRACKTABLE>
</table>

<h2>Surface</h2>
<table>
<tr><th>Tracktype</th><th>Total length</th><th>Number of ways</th></tr>
<SURFACETABLE>
</table>

<h2>Components</h2>
<table>
<tr><th>Component</th><th>Total length</th><th>Number of ways</th></tr>
<tr><td>%s</td><td>%1.1f</td><td>%d</td></tr>
</table>


</body>
</html>
HTML;

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

//  get the tags of the way
$sql = "SELECT `key`, `value` FROM `relationtags` WHERE `downloadid` = :downloadid AND `relationid` = :relationid;";
$stmt = $dbConn->prepare($sql);
$stmt->execute(['downloadid'=>$downloadid, 'relationid'=>$relationid]);
$tableRow = "<tr><td>%s</td><td>%s</td></tr>\r\n";
$tableData = '';
while($row = $stmt->fetch())
{
	$tableData .= sprintf($tableRow, $row['key'], $row['value']);
}
$pageTemplate = str_replace('<TAGTABLE>', $tableData, $pageTemplate);

// get precalculated values in relationdatacalculated
$sql = "SELECT `totallength`, `totalpointcount`, `components` FROM `relationdatacalculated` WHERE `downloadid` = :downloadid AND `relationid` = :relationid;";
$stmt = $dbConn->prepare($sql);
$stmt->execute(['downloadid'=>$downloadid, 'relationid'=>$relationid]);
$row = $stmt->fetch();
$totallength = $row['totallength'];
$totalpointcount = $row['totalpointcount'];
$pageTemplate = str_replace('<TOTALLENGTH>', sprintf(" %1.1f km", $totallength/1000), $pageTemplate);
$pageTemplate = str_replace('<COMPONENTS>', $row['components'], $pageTemplate);

// get the types of highway
$sql = "SELECT w.tag_highway, COUNT(*) waycount, SUM(length) length
			FROM relationmembers rm
			JOIN ways w
				ON rm.downloadid = w.downloadid AND rm.memberid = w.wayid
			WHERE rm.membertype = 'way' AND rm.downloadid = :downloadid1 AND w.downloadid = :downloadid2 
			GROUP BY w.tag_highway
			ORDER BY length DESC;";
$stmt = $dbConn->prepare($sql);
$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid]);
$tableRow = "<tr><td>%s</td><td>%1.1f</td><td>%1.1f %%</td><td>%d</td></tr>\r\n";
$tableData = '';
while($row = $stmt->fetch())
{
	$tableData .= sprintf($tableRow, $row['tag_highway'], $row['length']/1000, $row['length']/$totallength*100, $row['waycount']);
}
$pageTemplate = str_replace('<HIGHWAYTABLE>', $tableData, $pageTemplate);

// get the different tracktypes for tracks
$sql = "SELECT w.tag_tracktype, COUNT(*) waycount, SUM(length) length
			FROM relationmembers rm
			JOIN ways w
				ON rm.downloadid = w.downloadid AND rm.memberid = w.wayid
			WHERE rm.membertype = 'way' AND rm.downloadid = :downloadid1 AND w.downloadid = :downloadid2 AND w.tag_highway = 'track' 
			GROUP BY w.tag_tracktype
			ORDER BY tag_tracktype;";
$stmt = $dbConn->prepare($sql);
$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid]);
$tableRow = "<tr><td>%s</td><td>%1.1f</td><td>%d</td></tr>\r\n";
$tableData = '';
while($row = $stmt->fetch())
{
	$tableData .= sprintf($tableRow, $row['tag_tracktype'], $row['length']/1000, $row['waycount']);
}
$pageTemplate = str_replace('<TRACKTABLE>', $tableData, $pageTemplate);

// get the surface data
$sql = "SELECT w.tag_surface, w.tag_highway, w.tag_tracktype, COUNT(*) waycount, SUM(length) length
			FROM relationmembers rm
			JOIN ways w
				ON rm.downloadid = w.downloadid AND rm.memberid = w.wayid
			WHERE rm.membertype = 'way' AND rm.downloadid = :downloadid1 AND w.downloadid = :downloadid2
			GROUP BY w.tag_surface, w.tag_highway, w.tag_tracktype
			ORDER BY length DESC;";
$stmt = $dbConn->prepare($sql);
$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid]);
$surfaceData = $stmt->fetchAll();
$resultCount = count($resultData);
$unpavedList = ['unpaved', 'dirt', 'gravel', 'earth', 'ground', 'grass'];
$pavedList = ['paved', 'concrete', 'asphalt', 'sett', 'cobblestone'];
for($i = 0; $i < $resultCount; $i++)
{
	if(
		find($surfaceData[$i]['surface'], $unpavedList) 
		or ($surfaceData[$i]['highway'] == 'path' and !find($surfaceData[$i]['surface'], $pavedList))
		or ($surfaceData[$i]['highway'] == 'path' and !find($surfaceData[$i]['surface'], $pavedList) and $surfaceData[$i]['tracktype'] != 'grade1')
	)
	{
		$surfaceData['unpaved'] = 1;
	}
	else
	{
		$surfaceData['unpaved'] = 0;
	}
}
$tableRow = "<tr><td>%s</td><td>%1.1f</td><td>%d</td></tr>\r\n";
$tableData = '';


// get data by component
$directions = getWaysOrder($relationid, $downloadid, $dbConn);
// get lengths of the ways according to $idx (key in $directions)
$sql = "SELECT rm.idx, w.length
			FROM relationmembers rm
			JOIN ways w
				ON rm.downloadid = w.downloadid AND rm.relationid = w.relationid AND rm.memberid = w.wayid
			WHERE rm.downloadid = :downloadid1 AND w.downloadid = :downloadid2";
$stmt = $dbConn->prepare($sql);
$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid]);
$componentData = Array();
// initiate $componentData
for($i = 1; $i < max($directions['components']); $i++)
{
	$componentData[$i] = ['length'=>0, 'count'=>0];
}
// loop over all ways
while($row = $stmt->fetch())
{
	$componentData['length'][$directions['components'][$row['idx']]] += $row['length'];
	$componentData['count'][$directions['components'][$row['idx']]]++;
}
$tableRow = "<tr><td>%s</td><td>%1.1f</td><td>%d</td></tr>\r\n";
$tableData = '';
foreach($ComponentData as $componentId=>$data)
{
	$tableData .= sprintf($tableRow, $componentId, $data['length'], $data['count']);
}

// print the page
echo$pageTemplate;
?>
