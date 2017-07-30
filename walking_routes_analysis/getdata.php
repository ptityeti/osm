<?php
// This script gets the data from walking routes in Belgium from OSM and
// inserts them into a database.
// The objectives are two-fold:
// * Be able to work easily with the data (stats, creation of gpx files...)
// * Have some kind of a history, however rough it may be
// This is mainly targeted at GR routes and lwn in my  backyards
// Script by Wouter Hamelinck, available under MIT-license

// include file containing db connection data etc.
include 'settings.inc.php';

include 'getorderednodes.php';

// set the time-out to 10 minutes. Necessary for the bigger relations.
ini_set('default_socket_timeout', 600);

// define some functions
function getDataFromRelation($relationid, $dbConn)
// function that will find the members of relation with id $relationid
// and its way members and insert all that in the db. Requires open db
// connection in $dbConn
{
	// get members list in xml format
	$url = "http://overpass-api.de/api/interpreter?data=[out:xml];rel(%d);out;";
	$XMLDocument = file_get_contents(sprintf($url, $relationid));
	$relationMembers = new SimpleXMLElement($XMLDocument);
	
	// insert a new line in downloads
	$sql = "INSERT INTO `downloads`(`relationid`, `starttime`) VALUES (:relationid, NOW());";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['relationid'=>$relationid]);
	$downloadid = $dbConn->lastInsertId();
	
	// fill table relationmembers
	$sql = "INSERT INTO `relationmembers`(`downloadid`, `relationid`, `memberid`, `idx`, `membertype`, `role`) VALUES (:downloadid, :relationid, :memberid, :idx, :membertype, :role)";
	$stmt = $dbConn->prepare($sql);
	$idx = 1;
	foreach($relationMembers->relation->member as $member)
	{
		$memberid = $member['ref'];
		$membertype = $member['type'];
		$role = $member['role'];
		$stmt->execute(['downloadid' => $downloadid, 'relationid' => $relationid, 'memberid'=>$memberid, 'idx'=>$idx, 'membertype'=>$membertype, 'role'=>$role]);
		$idx++;
	}
	
	// fill table relationtags
	$sql = "INSERT INTO `relationtags`(`downloadid`, `relationid`, `key`, `value`) VALUES (:downloadid, :relationid, :key, :value)";
	$stmt = $dbConn->prepare($sql);
	foreach($relationMembers->relation->tag as $tag)
	{
		$key = $tag['k'];
		$value = $tag['v'];
		$stmt->execute(['downloadid' => $downloadid, 'relationid' => $relationid, 'key'=>$key, 'value'=>$value]);
	}
	
	// get the file with details of the ways
	$url = "http://overpass-api.de/api/interpreter?data=[out:xml];rel(%d);way(r);out%%20geom;";
	$XMLDocument = file_get_contents(sprintf($url, $relationid));
	$relationWays = new SimpleXMLElement($XMLDocument);
	
	// fill the table ways
	$sql = "INSERT INTO `ways`(`downloadid`, `wayid`, `tag_highway`, `tag_surface`, `tag_name`, `tag_tracktype`, `taglist`, `pointcount`) VALUES (:downloadid, :wayid, :tag_highway, :tag_surface, :tag_name, :tag_tracktype, :taglist, :pointcount)";
	$stmt = $dbConn->prepare($sql);
	$sql2 = "INSERT IGNORE INTO `pointsinway`(`downloadid`, `wayid`, `pointid`, `idx`, `lat`, `lng`) VALUES (:downloadid, :wayid, :pointid, :idx, :lat, :lng)";
	$stmt2 = $dbConn->prepare($sql2);
	// loop over the ways
	$pointcount = 0;
	foreach($relationWays->way as $way)
	{
		$wayid = $way['id'];
		$taglist = Array();
		$tag_highway = null;
		$tag_surface = null;
		$tag_name = null;
		$tag_tracktype = null;
		$idx = 1;
		// loop over the nodes
		foreach($way->nd as $node)
		{
			$pointid = $node['ref'];
			$lat = $node['lat'];
			$lng = $node['lon'];
			$stmt2->execute(['downloadid'=>$downloadid, 'wayid'=>$wayid, 'pointid'=>$pointid, 'idx'=>$idx, 'lat'=>$lat, 'lng'=>$lng]);
			$idx++;
		}
		// loop over the tags
		foreach($way->tag as $tag)
		{
			switch($tag['k'])
			{
				case 'highway':
					$tag_highway = $tag['v'];
					break;
				case 'surface':
					$tag_surface = $tag['v'];
					break;
				case 'name':
					$tag_name = $tag['v'];
					break;
				case 'tracktype':
					$tag_tracktype = $tag['v'];
					break;
				default:
					$newtag = $tag['k'] . ':' . $tag['v'];
					$taglist[] = $newtag;
					break;
			}
		}
		$taglist = implode(';', $taglist); // this is an issue if the key or value contains a semicolon. To be solved later.
		$pointcount = $idx-1;
		$stmt->execute(['downloadid'=>$downloadid, 'wayid'=>$wayid, 'pointcount'=>$pointcount, 'tag_highway'=>$tag_highway, 'tag_surface'=>$tag_surface, 'tag_name'=>$tag_name, 'tag_tracktype'=>$tag_tracktype, 'taglist'=>$taglist]);
	}
	
	// update the table ways setting the lengths
	$sql = "UPDATE ways w JOIN (SELECT p1.downloadid, p1.wayid, SUM(2*6371000*ASIN(SQRT(POW(SIN((p1.lat-p2.lat)*PI()/360),2)+COS(p1.lat*PI()/180)*COS(p2.lat*PI()/180)*POW(SIN((p1.lng-p2.lng)*PI()/360),2)))) d FROM pointsinway p1 JOIN pointsinway p2 ON p1.downloadid = p2.downloadid AND p1.wayid = p2.wayid AND p1.idx + 1 = p2.idx WHERE p1.downloadid = :downloadid1 AND p2.downloadid = :downloadid2 GROUP BY p1.downloadid, p1.wayid) t ON w.downloadid = t.downloadid AND w.wayid = t.wayid SET w.length = t.d;";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid]);
	// update the table ways setting the is_unpaved flag
	$sql = "UPDATE ways SET is_unpaved = 1 
				-- surface is set to unpaved value
				WHERE (tag_surface IN ('unpaved', 'dirt', 'gravel', 'earth', 'ground', 'grass', 'compacted', 'wood', 'sand', 'fine_gravel') 
						-- surface is not set to clear value and it is path or track not grade1
						OR (
							tag_surface NOT IN ('unpaved', 'dirt', 'gravel', 'earth', 'ground', 'grass', 'compacted', 'wood', 'sand', 'fine_gravel', 'paved', 'concrete', 'asphalt', 'sett', 'cobblestone', 'paving_stones') 
								AND 
							(tag_highway = 'path' OR (tag_highway = 'track' AND tag_tracktype <> 'grade1'))
						)
				) AND downloadid = :downloadid";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['downloadid' => $downloadid]);
	
	// insert a record in the table relationdatacalculated
	// get number of components
	if($pointcount > 1)
	{
		$directions = getWaysOrder($relationid, $downloadid, $dbConn);
		$components = max($directions['components']);
	}
	else
	{
		$components = 0;
	}
	$sql = "INSERT IGNORE INTO `relationdatacalculated`(`downloadid`,`relationid`,`totallength`,`totalpointcount`,`components`) 
				SELECT :downloadid3, :relationid, SUM(w.length) length, SUM(w.pointcount), :components
				FROM relationmembers rm
				JOIN ways w
					ON rm.downloadid = w.downloadid AND rm.memberid = w.wayid
				WHERE rm.downloadid = :downloadid1 AND w.downloadid = :downloadid2";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid, 'downloadid3'=>$downloadid, 'relationid'=>$relationid, 'components'=>$components]);
	
	//update the endtime in table downloads
	$sql = "UPDATE `downloads` SET `endtime`= NOW() WHERE `id` = :downloadid;";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['downloadid'=>$downloadid]);
}

// create db connection
$dsn = "mysql:host=$db_server;dbname=$db_name;charset=utf8";
$dbConn = new PDO($dsn, $db_user, $db_pw);

// get list of all interesting relations
$sql = "SELECT DISTINCT `relationid` FROM `routelist` WHERE `relationid` IS NOT NULL;";
$stmt = $dbConn->prepare($sql);
$stmt->execute();

// loop over all routes to get their information
while($row = $stmt->fetch())
{
	// check how old the last download of this relation is
	$sql_lastdownload = "SELECT MAX(endtime) lastdownload FROM downloads WHERE relationid = :relationid";
	$stmt_lastdownload = $dbConn->prepare($sql_lastdownload);
	$stmt_lastdownload->execute(['relationid' => $row['relationid']]);
	$lastdownloadresult = $stmt_lastdownload->fetch();
	$lastdownload = $lastdownloadresult[0];
	if(strlen($lastdownload) < 10) // not yet downloaded
	{
		$lastdownload = new DateTime("2000-01-01");
	}
	else
	{
		$lastdownload = new DateTime($lastdownload);
	}
	$now = new DateTime();
	// if it is more than a week old, get a new version
	if($now->diff($lastdownload)->format("%a") > 7)
	{
		getDataFromRelation($row['relationid'], $dbConn);
	}
}

?>
