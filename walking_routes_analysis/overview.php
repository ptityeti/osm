<?php
// file that creates an overview table of the specified walking routes

// in het future $routetype and $location can be set with a get variable (to implement)

// template for the page
$pageTemplate = <<<HTML
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=windows-1252">
		<title>Overview of GR routes</title>
		<style type="text/css">
			body{font-family:Arial;}
			
			.headericon{width:30px;}
			
			.osmicon{width:20px;}
			
			.textcolumn{width:100px;}
			
			.tinycolumn{width:50px;}
			
			table{text-align:right;}
			
			th{text-align:center; font-weigth:bold;}
			
			td:first-child{text-align:left}
		</style>
		<link rel="stylesheet" href="../style.css" type="text/css">
	</head>
	<body>
		<h1>Overview of GR routes</h1>
		<p>
			Belgium has a rather dense network of GR hiking trails. In Flanders, those are maintained by <a href="http://www.groteroutepaden.be">Grote Routepaden vzw</a>, while in Wallonia the organisation <a href="http://www.grsentiers.org">GR Sentiers asbl</a> manages them. In <a href="http://www.openstreetmap.org">OpenStreetMap</a> (OSM) many hiking routes and consequently most of the GR hiking trail are present. The table below provides an overview of the routes as they are present in OSM. It provides a mix of technical checks and information relevant for a hiker. Each line links to a more detailed page about the route.
		</p>
		<table><tbody>
			<tr><th rowspan="2" class="namecolumn">GR</th><th rowspan="2" class="textcolumn"><img class="headericon" src="length_icon_white.png"></th><th colspan="4">Number of members</th><th rowspan="2" class="tinycolumn"><img class="headericon" src="puzzel_icon_white.png"></th><th rowspan="2" class="tinycolumn"><img class="headericon" src="calendar_icon_white.png"></th><th rowspan="2" class="textcolumn">Unpaved</th></tr>
			<tr><th>Total</th><th><img class="osmicon" src="node.svg"></th><th><img class="osmicon" src="way.svg"></th><th><img class="osmicon" src="relation.svg"></th></tr>
<<TABLEHTML>>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=3121667">GR5 FL</a><td>235 km</td><td>664</td><td>0</td><td>664</td><td>0</td><td>1</td><td>5</td><td>23.7%</td></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=3121668">GR5 WAL</a><td>145 km</td><td>1036</td><td>1</td><td>1035</td><td>0</td><td>1</td><td>5</td><td>45.6%</td></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=13658">GR5A N</a></td><td>237 km</td><td>327</td><td>0</td><td>327</td><td>0</td><td>2</td><td>5</td><td>75.9%</td></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=2629186">GR5A S</a></td><td>309 km</td><td>5</td><td>0</td><td>0</td><td>5</td><td>1</td><td>20</td><td>12.0%</td></tr>
		</tbody></table>
		<h2>OSM data representation</h2>
		<p>The OSM data model maps hiking routes as relations. A relation is a set containing other objects, in this case <em>ways</em>. A <em>way</em> is just a line representing a road, path or something else. A hiking route is thus just a set containing all the roads and paths where the route passes. The members in the relation are ordered. There are also container relations: relations containing other relations. In the context of hiking routes you often have a main route and then some variants, shortcuts and approach routes. Each of those different routes would then be represented using a separate relation. All those different relations would then be grouped into one container relation. The table contains one line for each GR related relation.</p>
		<h2>Explanation of the columns</h2>
		<p>
			<ul>
				<li><strong>GR</strong>: just a short name indicating which GR the line is about. This links to a page with further details about the relation.</li>
				<li><strong>Length</strong> (represented by a ruler symbol): the total length of all the ways contained in the relation. This length is calculated using the Haversine formula.</li>
				<li><strong>Number of members</strong>: the total number of members is shown, followed by the number of nodes, ways and relations contained in the relation. For a normal relation, all members are supposed to be ways and if there are any nodes or relations that number will be shown in red. For container relations we assume that all members are relations and if there are nodes or ways their number is shown in red.</li>
				<li><strong>Number of components</strong> (represented by a puzzle symbol): ideally we can walk from one end to the other starting with the first way of the relation, then the second way and continuing until the last way in the relation. The number shown is the number of connected components where you can walk through in this way. Ideally this number should be one. If it is higher, it will be in red. For container relations it will be zero. If the number is higher than one this indicates one of the following: ways missing in the relation, (parts of) ways superfluous in the relation or a relation containing multiple variants.</li>
				<li><strong>Days since last update</strong> (represented by a calendar icon): indicates how old the data are. The different relations are downloaded regularly from the OSM server and stored locally for the analysis.</li>
				<li><strong>Percentage unpaved</strong>: the percentage of the relation that is unpaved. This is calculated as a percentage of length. In order to obtain whether a way is paved the tags <em>highway</em>, <em>surface</em> and <em>tracktype</em> are used. If the tag <em>surface</em> is set and has one of the values <em>unpaved</em>, <em>dirt</em>, <em>gravel</em>, <em>earth</em>, <em>ground</em>, <em>grass</em>, <em>compacted</em>, <em>wood</em>, <em>sand</em> or <em>fine_gravel</em>, the way is considered to be unpaved. If this tag has one of the values <em>paved</em>, <em>concrete</em>, <em>asphalt</em>, <em>sett</em>, <em>cobblestone</em> or <em>paving_stones</em>, it is considered to be paved. If the <em>surface</em> tag has another value or is not set, the <em>highway</em> tag is considered. If the <em>highway</em> tag has the value <em>path</em> the way is considered to be unpaved. If this tag has the value <em>track</em> and the tag <em>tracktype</em> has a value distinct from <em>grade1</em> or is not set, the way is considered to be unpaved. In all other cases the way is considered to be paved. If you find a place where this algorithm gives an incorrect value, the best course of action is to add a <em>surface</em> tag to the way in OSM. Or to correct this value if it would be already set.</li>
			</ul>
		</p>
	</body>
</html>
HTML;

$routetype = 'GR';
$location = null;
if($location === null)
{
	$sql = "SELECT rl.id, rl.relationid, rl.ref, lv.lastid, ld.endtime, rm.membercount, rm.uniquemembercount, rm.waycount, rm.relationcount, rm.nodecount, rm.hasrolecount, rdc.totallength, rdc.totalpointcount, rdc.components
				FROM routelist rl
				-- add the last successful download id 
				LEFT JOIN
				-- filter to get only the last version
				(
					SELECT relationid, MAX(id) lastid FROM downloads WHERE endtime IS NOT NULL GROUP BY relationid
				) lv -- lv stands for last version
				ON rl.relationid = lv.relationid
				-- add the time of last successful download
				LEFT JOIN
				-- join with download data (especially end time of download data)
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

$rowHTML = "\t\t\t<tr><td><a href=\"viewdetails.php?relationid=%d\">%s</a><td>%d km</td><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%1.1f%%</td></tr>";

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
//	if($row['waycount'] > 0)
//	{
//		$avglength = $row['totallength'] / $row['waycount'];
//		$avgpoints = $row['totalpointcount'] / $row['waycount'];
//	}
//	else
//	{
//		$avglength = 0;
//		$avgpoints = 0;
//	}
	// get time since last updage
	$now = new DateTime();
	$ageOfData = $now->diff(DateTime($lastdownload))->format("%a");
	// set $percUnpaved
	if($row['totallength'] > 0)
	{
		$percUnpaved = $row['length_unpaved'] / $row['totallength'] * 100;
	}
	else
	{
		$percUnpaved = 0;
	}
	$tableData .= sprintf(
		$rowHTML,
		$row['relationid'],
		$row['ref'],
		$row['totallength'] / 1000,
		$row['membercount'],
		$row['nodecount'],
		$row['waycount'],
		$row['relationcount'],
		$row['components'],
		$ageOfData,
		$percUnpaved
	);
}

$pageTemplate = str_replace('<<TABLEHTML>>', $tableHTML, $pageTemplate);
print($pageTemplate);

?>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=windows-1252">
		<title>Overview of GR routes</title>
		<style type="text/css">
			body{font-family:Arial;}
			
			.headericon{width:30px;}
			
			.osmicon{width:20px;}
			
			.textcolumn{width:100px;}
			
			.tinycolumn{width:50px;}
			
			table{text-align:right;}
			
			th{text-align:center; font-weigth:bold;}
			
			td:first-child{text-align:left}
		</style>
		<link rel="stylesheet" href="../style.css" type="text/css">
	</head>
	<body>
		<h1>Overview of GR routes</h1>
		<p>
			Belgium has a rather dense network of GR hiking trails. In Flanders, those are maintained by <a href="http://www.groteroutepaden.be">Grote Routepaden vzw</a>, while in Wallonia the organisation <a href="http://www.grsentiers.org">GR Sentiers asbl</a> manages them. In <a href="http://www.openstreetmap.org">OpenStreetMap</a> (OSM) many hiking routes and consequently most of the GR hiking trail are present. The table below provides an overview of the routes as they are present in OSM. It provides a mix of technical checks and information relevant for a hiker. Each line links to a more detailed page about the route.
		</p>
		<table><tbody>
			<tr><th rowspan="2" class="namecolumn">GR</th><th rowspan="2" class="textcolumn"><img class="headericon" src="length_icon_white.png"></th><th colspan="4">Number of members</th><th rowspan="2" class="tinycolumn"><img class="headericon" src="puzzel_icon_white.png"></th><th rowspan="2" class="tinycolumn"><img class="headericon" src="calendar_icon_white.png"></th><th rowspan="2" class="textcolumn">Unpaved</th></tr>
			<tr><th>Total</th><th><img class="osmicon" src="node.svg"></th><th><img class="osmicon" src="way.svg"></th><th><img class="osmicon" src="relation.svg"></th></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=3121667">GR5 FL</a><td>235 km</td><td>664</td><td>0</td><td>664</td><td>0</td><td>1</td><td>5</td><td>23.7%</td></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=3121668">GR5 WAL</a><td>145 km</td><td>1036</td><td>1</td><td>1035</td><td>0</td><td>1</td><td>5</td><td>45.6%</td></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=13658">GR5A N</a></td><td>237 km</td><td>327</td><td>0</td><td>327</td><td>0</td><td>2</td><td>5</td><td>75.9%</td></tr>
			<tr><td><a href="http://horizonloper.be/osm/walking_routes/viewdetails.php?relationid=2629186">GR5A S</a></td><td>309 km</td><td>5</td><td>0</td><td>0</td><td>5</td><td>1</td><td>20</td><td>12.0%</td></tr>
		</tbody></table>
		<h2>OSM data representation</h2>
		<p>The OSM data model maps hiking routes as relations. A relation is a set containing other objects, in this case <em>ways</em>. A <em>way</em> is just a line representing a road, path or something else. A hiking route is thus just a set containing all the roads and paths where the route passes. The members in the relation are ordered. There are also container relations: relations containing other relations. In the context of hiking routes you often have a main route and then some variants, shortcuts and approach routes. Each of those different routes would then be represented using a separate relation. All those different relations would then be grouped into one container relation. The table contains one line for each GR related relation.</p>
		<h2>Explanation of the columns</h2>
		<p>
			<ul>
				<li><strong>GR</strong>: just a short name indicating which GR the line is about. This links to a page with further details about the relation.</li>
				<li><strong>Length</strong> (represented by a ruler symbol): the total length of all the ways contained in the relation. This length is calculated using the Haversine formula.</li>
				<li><strong>Number of members</strong>: the total number of members is shown, followed by the number of nodes, ways and relations contained in the relation. For a normal relation, all members are supposed to be ways and if there are any nodes or relations that number will be shown in red. For container relations we assume that all members are relations and if there are nodes or ways their number is shown in red.</li>
				<li><strong>Number of components</strong> (represented by a puzzle symbol): ideally we can walk from one end to the other starting with the first way of the relation, then the second way and continuing until the last way in the relation. The number shown is the number of connected components where you can walk through in this way. Ideally this number should be one. If it is higher, it will be in red. For container relations it will be zero. If the number is higher than one this indicates one of the following: ways missing in the relation, (parts of) ways superfluous in the relation or a relation containing multiple variants.</li>
				<li><strong>Days since last update</strong> (represented by a calendar icon): indicates how old the data are. The different relations are downloaded regularly from the OSM server and stored locally for the analysis.</li>
				<li><strong>Percentage unpaved</strong>: the percentage of the relation that is unpaved. This is calculated as a percentage of length. In order to obtain whether a way is paved the tags <em>highway</em>, <em>surface</em> and <em>tracktype</em> are used. If the tag <em>surface</em> is set and has one of the values <em>unpaved</em>, <em>dirt</em>, <em>gravel</em>, <em>earth</em>, <em>ground</em>, <em>grass</em>, <em>compacted</em>, <em>wood</em>, <em>sand</em> or <em>fine_gravel</em>, the way is considered to be unpaved. If this tag has one of the values <em>paved</em>, <em>concrete</em>, <em>asphalt</em>, <em>sett</em>, <em>cobblestone</em> or <em>paving_stones</em>, it is considered to be paved. If the <em>surface</em> tag has another value or is not set, the <em>highway</em> tag is considered. If the <em>highway</em> tag has the value <em>path</em> the way is considered to be unpaved. If this tag has the value <em>track</em> and the tag <em>tracktype</em> has a value distinct from <em>grade1</em> or is not set, the way is considered to be unpaved. In all other cases the way is considered to be paved. If you find a place where this algorithm gives an incorrect value, the best course of action is to add a <em>surface</em> tag to the way in OSM. Or to correct this value if it would be already set.</li>
			</ul>
		</p>
	</body>
</html>


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
