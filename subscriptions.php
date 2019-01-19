<?php
include("db.php");
include("helpers.php");

// Get the server secret key
if(isset($_COOKIE['server_secret_key'])){
	$server_secret = $_COOKIE['server_secret_key'];
} else {
	$data["unauthorized"] = "true";
	http_response_code(401);
	echo json_encode($data);
	exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	$uuid = $json['uuid'];
	$server_id = $json['server_id'];
	$event_name = $json['event_name'];
	$event_properties = $json['event_properties'];
	
	if(!isServerValidated($conn, $server_id, $server_secret)){
		$data["unauthorized"] = "true";
		http_response_code(401);
		echo json_encode($data);
		exit;
	}
	
	$id = getUserId($conn, $uuid, $server_id);
	if($id == -1){
		$data["error"] = "User does not exist";
		http_response_code(400);
		echo json_encode($data);
		exit;
	}
	
	// Insert new subscription
	$sql = "INSERT INTO subscriptions (user_id, event_name, event_properties) VALUES (?, ?, ?)";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("iss", $id, $event_name, $event_properties);
	$stmt->execute();
	$data["subscription_id"] = $stmt->insert_id;
	echo json_encode($data);
	exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET'){
	
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	$uuid = $json['uuid'];
	$server_id = $json['server_id'];
	
	if(!isServerValidated($conn, $server_id, $server_secret)){
		$data["unauthorized"] = "true";
		http_response_code(401);
		echo json_encode($data);
		exit;
	}
	
	$id = getUserId($conn, $uuid, $server_id);
	if($id == -1){
		$data["error"] = "User does not exist";
		http_response_code(400);
		echo json_encode($data);
		exit;
	}
	
	// Check that the server secret key matches the server id's secret key
	$sql = "SELECT subscription_id, event_name, event_properties FROM Subscriptions WHERE user_id = ? AND deleted_on IS NULL";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	$data["events"] = array();
	
	if($result->num_rows > 0){
		$index = 0;
		while($row = $result->fetch_assoc()){
			$newRow["subscription_id"] = $row["subscription_id"];
			$newRow["event_name"] = $row["event_name"];
			$newRow["event_properties"] = $row["event_properties"];
			array_push($data["events"], $newRow);
			$index = $index + 1;
		}
	}
	
	echo json_encode($data);
	exit;
	
} else if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	$subscription_id = $json['subscription_id'];
	$server_id = $json['server_id'];
	
	if(!isServerValidated($conn, $server_id, $server_secret)){
		$data["unauthorized"] = "true";
		http_response_code(401);
		echo json_encode($data);
		exit;
	}
	
	// Delete subscription
	$sql = "UPDATE Subscriptions SET deleted_on = ? WHERE subscription_id = ?";
	$now = date("Y-m-d H:i:s");
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("si", $now, $subscription_id);
	$stmt->execute();
	exit;
}