<?php
	
	// Settings
	$conn_user='root';
	$conn_pass='';
	$conn_host='127.0.0.1';
	$conn_database='crm_db';
	 
	date_default_timezone_set ('Etc/GMT-3');
	header('Content-Type: text/html; charset=utf-8');
	
	if( count($argv)<2 || strlen($argv[1])>21 || strlen($argv[1])<10 ) {
		exit('Bad format of linkedid');
	}
	
	$mysqli = new mysqli($conn_host, $conn_user, $conn_pass, $conn_database);
	if( $mysqli->connect_error ) {
		exit('Connection failed');
	}	

	// Query
	$qry =  "USE ".$conn_database;
	$result = $mysqli->query($qry);
	if( $result===false ) {
		exit('Database not found');
	}

	$qry =  "select COUNT(*) AS QUANTITY FROM crm_linkedid WHERE linkedid=".$argv[1];
 	$result = $mysqli->query($qry);
	if( $result===false ) {
		exit('Query error!');
	}
	else {
		while ($row = $result->fetch_row()) {
			if( is_numeric($row[0]) && intval($row[0])>0 ) {
				echo 'yes';
			}
			else {
				echo 'no';
			}
			
			break;
		}
	}
	
	$qry =  "DELETE FROM crm_linkedid WHERE linkedid=".$argv[1];
 	//$result = $mysqli->query($qry);	
	
?>
