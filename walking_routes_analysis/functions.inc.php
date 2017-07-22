<?php
// function to get id of last download for specific $relationid
getLastDownloadId = function($relationid)
{
	$sql = "SELECT MAX(id) FROM downloads WHERE endtime IS NOT NULL AND relationid = :relationid";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['relationid'=>$relationid]);
	$result = $stmt->fetch();
	$downloadid = $result[0];
	return($downloadid);
}
?>
