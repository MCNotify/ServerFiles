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
	
	if(isset($json['uuid']) && isset($json['polygon']) && isset($json['area_name'])){
		$uuid = $json['uuid'];
		$polygon = $json['polygon'];
		$area_name = $json['area_name'];
	} else {
		http_response_code(400);
		exit();
	}
	
	$user_id = getUserId($conn, $uuid);
	
	if($user_id == -1){
		$data["error"] = "User does not exist";
		http_response_code(400);
		echo json_encode($data);
		exit();		
	}
	
	// Insert the area
	$sql = "INSERT INTO areas (user_id, server_id, polygon, area_name) VALUES (?, ?, ?, ?)";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("iiss", $user_id, $server_id, $polygon, $area_name);
	$stmt->execute();
	$data["area_id"] = $stmt->insert_id;
	echo json_encode($data);
	exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET'){
	
	if(isset($_GET['uuid'])){
		$uuid = $_GET['uuid'];
	} else {
		http_response_code(400);
		exit();		
	}
	
	$user_id = getUserId($conn, $uuid);
	
	if($user_id == -1){
		$data["error"] = "User does not exist";
		http_response_code(400);
		echo json_encode($data);
		exit();		
	}
	
	// Check that the server secret key matches the server id's secret key
	$sql = "SELECT area_id, polygon, area_name FROM areas WHERE user_id = ? AND server_id = ? AND deleted_on IS NULL";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("ii", $user_id, $server_id);
	
	$stmt->execute();
	
	$result = $stmt->get_result();
	
	$data["areas"] = array();
	
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$newRow["area_id"] = $row["area_id"];
			$newRow["polygon"] = $row["polygon"];
			$newRow["area_name"] = $row["area_name"];
			array_push($data["areas"], $newRow);
		}
	}
	
	echo json_encode($data);
	exit();

} else if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
			
	$input = file_get_contents("php://input");
	$json = json_decode($input, true);
	
	if(isset($json['area_id'])){
		$area_id = $json['area_id'];
	} else {
		http_response_code(400);
		exit();
	}
	
	// Insert the area
	$sql = "UPDATE areas SET deleted_on = ? WHERE area_id = ?";
	$now = date("Y-m-d H:i:s");
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("si", $now, $area_id);
	$stmt->execute();
	exit();
	
}