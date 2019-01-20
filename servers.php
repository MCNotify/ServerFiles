<?php

include("db.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	// Get the server's name and port from the request data
	
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	if(isset($json['server_name']) && isset($json['server_port']) && isset($json['server_secret_key']) && isset($json['recovery_email'])){
		$server_name = $json['server_name'];
		$server_port = $json['server_port'];
		$server_secret = $json['server_secret_key'];
		$recovery_email = $json['recovery_email'];
	} else {
		http_response_code(400);
		exit();		
	}
	
	// Check to make sure the server is not in the database yet.
	$sql = "SELECT server_id FROM Servers WHERE server_name = ? AND server_port = ? AND server_secret_key = ?";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("sis", $server_name, $server_port, $server_secret);
	
	$stmt->execute();
	
	$result = $stmt->get_result();
	
	// Only add a new entry if there are no existing servers.
	if($result->num_rows == 0){
		$sql = "INSERT INTO Servers (server_name, server_port, server_secret_key, recovery_email) VALUES (?, ?, ?, ?)";
		$stmt = $conn->stmt_init();
		$stmt->prepare($sql);
		$stmt->bind_param("siss", $server_name, $server_port, $server_secret, $recovery_email);
		
		$stmt->execute();
		
		$data["server_id"] = $stmt->insert_id;
		
		echo json_encode($data);
		exit;
	} else {
		$row = $result->fetch_assoc();
		
		$data["server_id"] = $row["server_id"];
		echo json_encode($data);
		exit;
	}
}