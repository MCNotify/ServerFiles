<?php
include("db.php");
include("helpers.php");

// Get the server secret key
if(isset($_COOKIE['server_secret_key']) && isset($_COOKIE['server_id'])){
	$server_secret = $_COOKIE['server_secret_key'];
	$server_id = $_COOKIE['server_id'];
} else {
	$data["unauthorized"] = "true";
	http_response_code(401);
	echo json_encode($data);
	exit();
}

if(!isServerValidated($conn, $server_id, $server_secret)){
	$data["unauthorized"] = "true";
	http_response_code(401);
	echo json_encode($data);
	exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	if(isset($json['uuid']) && isset($json['event_name']) &&  isset($json['event_properties'])){
		$uuid = $json['uuid'];
		$event_name = $json['event_name'];
		$event_properties = $json['event_properties'];
	} else {
		http_response_code(400);
		exit();
	}
	
	$id = getUserId($conn, $uuid);
	if($id == -1){
		$data["error"] = "User does not exist";
		http_response_code(400);
		echo json_encode($data);
		exit();
	}
	
	// Insert new subscription
	$sql = "INSERT INTO subscriptions (user_id, event_name, event_properties, server_id) VALUES (?, ?, ?, ?)";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("issi", $id, $event_name, $event_properties, $server_id);
	$stmt->execute();
	$data["subscription_id"] = $stmt->insert_id;
	echo json_encode($data);
	exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET'){
	
	if(isset($_GET['uuid'])){
		$uuid = $_GET['uuid'];
	} else {
		http_response_code(400);
		exit();		
	}
	
	$id = getUserId($conn, $uuid);
	if($id == -1){
		$data["error"] = "User does not exist";
		http_response_code(400);
		echo json_encode($data);
		exit();
	}
	
	// Check that the server secret key matches the server id's secret key
	$sql = "SELECT subscription_id, event_name, event_properties FROM Subscriptions WHERE user_id = ? AND server_id = ? AND deleted_on IS NULL";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("ii", $id, $server_id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	$data["events"] = array();
	
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$newRow["subscription_id"] = $row["subscription_id"];
			$newRow["event_name"] = $row["event_name"];
			$newRow["event_properties"] = $row["event_properties"];
			array_push($data["events"], $newRow);
		}
	}
	
	echo json_encode($data);
	exit();
	
} else if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	if(isset($json['subscription_id'])){
		$subscription_id = $json['subscription_id'];
	} else {
		http_response_code(400);
		exit();				
	}
	
	// Delete subscription
	$sql = "UPDATE Subscriptions SET deleted_on = ? WHERE subscription_id = ?";
	$now = date("Y-m-d H:i:s");
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("si", $now, $subscription_id);
	$stmt->execute();
	exit();
}