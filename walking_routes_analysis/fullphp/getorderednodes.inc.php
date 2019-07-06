<?php
// file to get connectedness of the routes

// function takes as input a version number and a relationid
// returns the nodes of the relation in an array ordered by how they should be connected
// and with the coordinates
// example output (2 components with three and two nodes respectively)
// Array(
//   Array(
//     Array('id'=>6433, 'lat'=>51.4556, 'lng'=>3.654),
//     Array('id'=>62633, 'lat'=>51.32556, 'lng'=>3.9876),
//     Array('id'=>66783, 'lat'=>51.8765, 'lng'=>3.85436)
//   ),
//   Array(
//     Array('id'=>8684, 'lat'=>51.234, 'lng'=>3.8765),
//     Array('id'=>9876, 'lat'=>51.6543, 'lng'=>3.14908)
//   )
// )
function getOrderedNodes($relationid, $downloadid, $dbConn)
{
	// get the ways of the relation with first and last node
	$sql = "SELECT d.id, rm.relationid, p.wayid, rm.idx way_idx, p.pointid, p.idx point_idx, p.lat, p.lng FROM
				downloads d
				JOIN relationmembers rm
					ON d.id = rm.downloadid AND d.relationid = rm.relationid
				JOIN pointsinway p
					ON d.id = p.downloadid AND rm.memberid = p.wayid
				WHERE d.id = :downloadid1 AND rm.downloadid = :downloadid2 AND p.downloadid = :downloadid3
				ORDER BY rm.idx, p.idx;";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid, 'downloadid3'=>$downloadid]);
	
	// create an array that says if ways should be passed forward or backward
	$directions = getWaysOrder($relationid, $downloadid, $dbConn);
	// loop over the points and put them in the right order
	$previousComponent = 0; // to keep track of a change in component
	$presentComponent = 0;
	$previousIdx = 0;
	$wayIsBackward = 0;
	$wayIsStartNewComponent = 1;
	$returnArray = Array();
	$wayArray = Array();
	while($point = $stmt->fetch())
	{
		if($point['way_idx'] != $previousIdx) // start a new way, we should add the previous one to $returnArray
		{
			if($wayIsBackward) // if backward, revert
			{
				$wayArray = array_reverse($wayArray);
			}
			if(!$wayIsStartNewComponent) // if same component, remove the first element (because it is the last one of the previous way)
			{
				array_shift($wayArray); // remove first element
				$returnArray[$presentComponent] = array_merge($returnArray[$previousComponent], $wayArray); // add to the current component
			}
			else // start new component in $returnArray
			{
				$returnArray[$presentComponent] = $wayArray;
			}
			$wayArray = Array(); // reset $wayArray
			$previousIdx =  $point['way_idx'];
			$previousComponent = $presentComponent;
			$presentComponent = $directions['components'][$point['way_idx']];
			$wayIsBackward = 1 - $directions['directions'][$point['way_idx']];
			if($previousComponent == $presentComponent)
			{
				$wayIsStartNewComponent = 0;
			}
			else
			{
				$wayIsStartNewComponent = 1;
			}
		}
		
		// add the point to $wayArray
		$wayArray[] = ['id' => $point['pointid'], 'lat' => $point['lat'], 'lng' => $point['lng']];
	}
	
	// add the last way
	if($wayIsBackward) // if backward, revert
	{
		$wayArray = array_reverse($wayArray);
	}
	if(!$wayIsStartNewComponent) // if same component, remove the first element (because it is the last one of the previous way)
	{
		array_shift($wayArray); // remove first element
		$returnArray[$presentComponent] = array_merge($returnArray[$previousComponent], $wayArray); // add to the current component
	}
	else // start new component in $returnArray
	{
		$returnArray[$presentComponent] = $wayArray;
	}
	
	// remove the empty array at index 0 (artefact from the first time the loop is passed)
	unset($returnArray[0]);
	
	// return the array
	return($returnArray);
}


// function to get the direction of the ways and the componenent
// it returns an array containing two array's, both with length equal to the number of members in the relation:
// * the first one returns an id of the component that the way belongs to (1, 2, etc)
// * the second one contains 0 and 1 where 1 indicated the way should be followed in forward direction,
//     while 0 indicates backward direction
// the array indices correspond to the field idx in table relationmembers
// (necessary if a relation contains not only ways)
// WARNING: this function assumes that the relation is well ordered
function getWaysOrder($relationid, $downloadid, $dbConn)
{
	// get the ways of the relation with id of first and last node
	$sql = "SELECT rm.downloadid, rm.memberid, rm.idx, rm.role, p1.pointid startpoint, p2.pointid endpoint 
				FROM 
				relationmembers rm
				JOIN ways w
					ON rm.downloadid = w.downloadid AND rm.memberid = w.wayid
				JOIN pointsinway p1
					ON rm.downloadid = p1.downloadid AND rm.memberid = p1.wayid
				JOIN pointsinway p2
					ON rm.downloadid = p2.downloadid AND w.pointcount = p2.idx AND rm.memberid = p2.wayid
				WHERE rm.downloadid = :downloadid1 AND w.downloadid = :downloadid2 AND p1.downloadid = :downloadid3 AND p2.downloadid = :downloadid4 
					AND p1.idx = 1 
				ORDER BY rm.idx";
	$stmt = $dbConn->prepare($sql);
	$stmt->execute(['downloadid1'=>$downloadid, 'downloadid2'=>$downloadid, 'downloadid3'=>$downloadid, 'downloadid4'=>$downloadid]);
	$allWays = $stmt->fetchAll();
	
	// get the number of way members in the relation
	$memberCount = count($allWays);

	// start the work
	$returnArray = Array('components' => Array(), 'directions' => Array());
	if($memberCount == 0) //corner case of relation without ways in it
	{
		return(
			Array(
				'components' => Array(), 
				'directions' => Array()
			)
		);
	}
	elseif($memberCount == 1) // corner case with one way in relation, then it doesn't matter
	{
		return(
			Array(
				'components' => [$allWays[0]['idx'] => 1], 
				'directions' => [$allWays[0]['idx'] => 1]
			)
		);
	}
	else // there are at least two ways
	{
		// first way
		if($allWays[0]['role'] == 'backward' or // role is set
			($allWays[0]['role'] != 'backward' and ($allWays[0]['startpoint'] == $allWays[1]['startpoint'] or $allWays[0]['startpoint'] == $allWays[1]['endpoint']) ) // role is not set to backward and startpoint of first one is end or start of second one
		) // take it backward
		{
			$returnArray['directions'][$allWays[0]['idx']] = 0;
			$outNode = $allWays[0]['startpoint'];
		}
		else // take it forward
		{
			$returnArray['directions'][$allWays[0]['idx']] = 1;
			$outNode = $allWays[0]['endpoint'];
		}
		// put it in the first component
		$returnArray['components'][$allWays[0]['idx']] = 1;
		
		// loop over second way to last but one way in relation
		$i = 1;
		while($i < $memberCount - 1)
		{
			if($outNode == $allWays[$i]['startpoint'] and $allWays[$i]['role'] != 'backward') // connects and should be forward
			{
				$returnArray['components'][$allWays[$i]['idx']] = $returnArray['components'][$allWays[$i-1]['idx']]; // same as previous
				$returnArray['directions'][$allWays[$i]['idx']] = 1;
				$outNode = $allWays[$i]['endpoint'];
			}
			elseif($outNode == $allWays[$i]['endpoint'] and $allWays[$i]['role'] != 'forward') // connects and should be backward
			{
				$returnArray['components'][$allWays[$i]['idx']] = $returnArray['components'][$allWays[$i-1]['idx']]; // same as previous
				$returnArray['directions'][$allWays[$i]['idx']] = 0;
				$outNode = $allWays[$i]['startpoint'];
			}
			else // does not connect
			{
				if($allWays[$i]['role'] == 'backward' or // role is set
					($allWays[$i]['role'] != 'backward' and ($allWays[$i]['startpoint'] == $allWays[$i+1]['startpoint'] or $allWays[$i]['startpoint'] == $allWays[$i+1]['endpoint']) ) // role is not set to backward and startpoint of first one is end or start of second one
				) // take it backward
				{
					$returnArray['directions'][$allWays[$i]['idx']] = 0;
					$outNode = $allWays[$i]['startpoint'];
				}
				else // take it forward
				{
					$returnArray['directions'][$allWays[$i]['idx']] = 1;
					$outNode = $allWays[$i]['endpoint'];
				}
				// put it in a new component
				$returnArray['components'][$allWays[$i]['idx']] = $returnArray['components'][$allWays[$i-1]['idx']] + 1;
					}
			$i++;
		}
		
		// do the last way of the relation
		if($outNode == $allWays[$i]['endpoint'] and $allWays[$i]['role'] != 'forward') // connects and should be backward
		{
			$returnArray['components'][$allWays[$i]['idx']] = $returnArray['components'][$allWays[$i-1]['idx']]; // same as previous
			$returnArray['directions'][$allWays[$i]['idx']] = 0;
			$outNode = $allWays[$i]['startpoint'];
		} 
		elseif($outNode == $allWays[$i]['startpoint'] and $allWays[$i]['role'] != 'backward') // connects and should be forward
		{
			$returnArray['components'][$allWays[$i]['idx']] = $returnArray['components'][$allWays[$i-1]['idx']]; // same as previous
			$returnArray['directions'][$allWays[$i]['idx']] = 1;
			$outNode = $allWays[$i]['endpoint'];
		}
		else // doesn't connect, just take it forward
		{
			$returnArray['components'][$allWays[$i]['idx']] = $returnArray['components'][$allWays[$i-1]['idx']] + 1; // start new component
			$returnArray['directions'][$allWays[$i]['idx']] = 1;
			$outNode = $allWays[$i]['endpoint'];
		}
		
		// return the constructed array
		return($returnArray);
	}
}

//include 'settings.inc.ph//p';
// connect to db
//$dsn = "mysql:host=$db_server;dbname=$db_name;charset=utf8";
//$dbConn = new PDO($dsn, $db_user, $db_pw);

//rint_r(getOrderedNodes(3121667, 1, $dbConn));

?>
