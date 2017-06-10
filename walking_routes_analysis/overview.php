<?php
// file that creates an overview table of the specified walking routes

// in het future $routetype and $location can be set with a get variable (to implement)

$routetype = 'GR';
$location = null;
if($location === null)
{
	$sql = "SELECT rl.id, rl.relationid, rl.ref, rl.name, lv.lastid, ld.endtime, rm.membercount, rm.uniquemembercount, rm.waycount, rm.relationcount, rm.nodecount, rm.hasrolecount, rdc.totallength, rdc.totalpointcount, rdc.components
				FROM routelist rl
				-- add the last successful download id 
				LEFT JOIN
				(
					SELECT relationid, MAX(id) lastid FROM downloads WHERE endtime IS NOT NULL GROUP BY relationid
				) lv -- lv stands for last version
				ON rl.relationid = lv.relationid
				-- add the time of last successful download
				LEFT JOIN
				(
					SELECT id, endtime FROM downloads
				) ld -- ld stands for last date
				ON lv.lastid = ld.id
				-- add data from table relationmembers
				LEFT JOIN 
				(
					SELECT
						downloadid,
						COUNT(*) membercount,
						COUNT(DISTINCT memberid) uniquemembercount,
						SUM(membertype = 'way') waycount,
						SUM(membertype = 'relation') relationcount,
						SUM(membertype = 'node') nodecount,
						SUM(role != '') hasrolecount
					FROM relationmembers
					GROUP BY downloadid
				) rm
				ON lv.lastid = rm.downloadid
				-- add the total length
				LEFT JOIN relationdatacalculated rdc
					ON lv.lastid = rdc.downloadid
				WHERE rl.type = 'GR'
				ORDER BY rl.id;";
}
else
{
	
}

$tableHTML = "<table><tr><th>GR</th><th>Name</th><th>Id of relation</th><th>Last download of relation</th><th>Length (km)</th><th># components</th><th># members</th><th># unique members</th><th>#ways</th><th>#relations</th><th>#nodes</th><th># members with role</th><th>Average # nodes per way</th><th>Average length per way</th></tr>\r\n{{ROWS}}\r\n</table>";
//$rowHTML = "<tr><th>{{GR}}</th><th>{{NAME}}</th><th>{{RELATIONID}}</th><th>{{DOWNLOAD}}</th><th>{{LENGTH}}</th><th>{{MEMBERCOUNT}}</th><th>{{UNIQUEMEMBERCOUNT}}</th><th>{{WAYCOUNT}}</th><th>{{RELATIONCOUNT}}</th><th>{{NODECOUNT}}</th><th>{{ROLECOUNT}}</th><th>{{NODESPERWAY}}</th><th>{{LENGTHPERWAY}}</th></tr>\r\n";
$rowHTML = "<tr><td><a href =\"viewdetails.php?relationid=%d\">%s</a></td><td>%s</td><td>%d</td><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%1.1f</td><td>%d</td></tr>\r\n";

// include file containing db connection data etc.
include 'settings.inc.php';
// create db connection
$dsn = "mysql:host=$db_server;dbname=$db_name;charset=utf8";
$dbConn = new PDO($dsn, $db_user, $db_pw);

// get data for all relations
$stmt = $dbConn->prepare($sql);
$stmt->execute();

$tableData = '';
// loop over all GR
while($row = $stmt->fetch())
{
	if($row['waycount'] > 0)
	{
		$avglength = $row['totallength'] / $row['waycount'];
		$avgpoints = $row['totalpointcount'] / $row['waycount'];
	}
	else
	{
		$avglength = 0;
		$avgpoints = 0;
	}
	$tableData .= sprintf(
		$rowHTML,
		$row['relationid'],
		$row['ref'],
		$row['name'],
		$row['relationid'],
		$row['endtime'],
		$row['totallength'] / 1000,
		$row['components'],
		$row['membercount'],
		$row['uniquemembercount'],
		$row['waycount'],
		$row['relationcount'],
		$row['nodecount'],
		$row['hasrolecount'],
		$avgpoints,
		$avglength
	);
}

?>
<html>
	<head></head>
	<body>
	<h1>Overview of relations</h1>
	Blabla
	<table>
		<tr><th>GR</th><th>Name</th><th>Id of relation</th><th>Last download of relation</th><th>Length (km)</th><th># components</th><th># members</th><th># unique members</th><th>#ways</th><th>#relations</th><th>#nodes</th><th># members with role</th><th>Average # nodes per way</th><th>Average length per way (m)</th></tr>
		<?php
			print($tableData);
		?>
	</table>
	</body>
</html>
