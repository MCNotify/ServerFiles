<?php


function isServerValidated($conn, $server_id, $server_secret_key){	
	// Check that the server secret key matches the server id's secret key
	$sql = "SELECT server_id FROM Servers WHERE server_id = ? AND server_secret_key = ?";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("is", $server_id, $server_secret_key);

	$stmt->execute();
	
	$result = $stmt->get_result();
	
	// Ensure that the server is validated
	if($result->num_rows == 0){		
		return false;
	} else {
		return true;
	}
}

function getUserId($conn, $uuid){	
	// Check that the server secret key matches the server id's secret key
	$sql = "SELECT user_id FROM Users WHERE uuid = ?";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("s", $uuid);
	
	$stmt->execute();
	
	$result = $stmt->get_result();
	
		// Ensure that the server is validated
	if($result->num_rows == 1){		
		$row = $result->fetch_assoc();
		return $row["user_id"];
	} else {
		return -1;
	}
}

?>