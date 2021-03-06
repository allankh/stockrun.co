<?php
// Set default timezone
// date_default_timezone_set('UTC');
// TODO check to see if the file already exists and offer to reprocess
// it or overwrite it
$filename = $_GET["file"];
try {

	// Create (connect to) SQLite database in file
	$file_db = new PDO('sqlite:/home/allan/fairmont/stockrun.co/store5/shipping/db/shipments.sqlite3');
	// Set errormode to exceptions
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// TODO make tracking UNIQUE, but it needs to accept NULL values.
	// Apparently sqlite is already supposed to support this, but she just don't work.
	$file_db->exec("CREATE TABLE IF NOT EXISTS shipments (
						id INTEGER PRIMARY KEY AUTOINCREMENT, 
						shipdate INTEGER, 
						client TEXT, 
						tracking TEXT,
						tstatus, TEXT,
						tstatdate INTEGER,
						transid INTEGER,
						shiptype TEXT,
						shipweight INTEGER,
						shipcost INTEGER,
						flag TEXT)");

 
	// Prepare INSERT statement to SQLite3 file db
	$insert = "INSERT INTO shipments (shipdate, client, tracking, tstatus, tstatdate, transid, shiptype, shipweight, shipcost, flag)";
	$insert .= "VALUES (:shipdate, :client, :tracking, :tstatus, :tstatdate, :transid, :shiptype, :shipweight, :shipcost, :flag)";
	$stmt = $file_db->prepare($insert);

	// Bind parameters to statement variables
	$stmt->bindParam(':shipdate', $shipdate);
	$stmt->bindParam(':client', $client);
	$stmt->bindParam(':tracking', $tracking);
	$stmt->bindParam(':tstatus', $tstatus);
	$stmt->bindParam(':tstatdate', $tstatdate);
	$stmt->bindParam(':transid', $transid);
	$stmt->bindParam(':shiptype', $shiptype);
	$stmt->bindParam(':shipweight', $shipweight);
	$stmt->bindParam(':shipcost', $shipcost);
	$stmt->bindParam(':flag', $flag);

	// Loop thru all messages and execute prepared insert statement
	$ships = getShipments($filename);
	foreach ($ships as $m) {
		// Set values to bound variables
		$shipdate = $m['shipdate'];
		$client = $m['client'];
		$tracking = $m['tracking'];
		$tstatus = $m['tstatus'];
		$tstatdate = $m['tstatdate'];
		$transid = $m['transid'];
		$shiptype = $m['shiptype']; 
		$shipweight = $m['shipweight'];
		$shipcost = $m['shipcost'];
		$flag = $m['flag'];
		// Execute statement
		$stmt->execute();
	}

    // Close file db connection
	$file_db = null;
	//print "Success.";
	//header("Location: /store5/shipping/");
	
} catch(PDOException $e) {
	// Print PDOException message
	echo $e->getMessage();
}

// TODO Make this odempotent
function getShipments($filename) {

	$open = "../files/".$filename;
	$f = fopen($open, "r");
	$ships = Array();
	$linecount = 0;
	$tstatdate = 0;
	while (($line = fgetcsv($f)) !== false) {
		// Skip the first line (of headers)
		if($linecount > 0) {
	        $count = 0;
		$flag = 'enroute';
	        foreach ($line as $cell) {			
				$itembit = htmlspecialchars($cell);
				if($count == 3) $date = $itembit;
				if($count == 4) $track = $itembit;
				if($count == 5) $client = $itembit;
				if($count == 8) $transid = $itembit;
				if($count == 9) $type = $itembit;
				if($count == 10) $weight = $itembit;
				if($count == 17) $cost = $itembit;
				$count++;
	        }
			if($track == "null") {
				$initstat = "No tracking provided.";
			} else {
				$initstat = "Submitted to Canada Post; awaiting pickup.";
			}
			array_push($ships,['shipdate' => $date,
								'client' => $client,
								'tracking' => $track,
								'tstatus' => $initstat,
								'tstatdate' => $tstatdate,
								'transid' => $transid,
								'shiptype' => $type,
								'shipweight' => $weight,
								'shipcost' => $cost,
								'flag' => $flag]);
		}
		$linecount++;
	}
	fclose($f);
	$ships = array_reverse($ships);
	return $ships;
} 

?>
