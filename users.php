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
	
	if(isset($json['uuid']) && isset($json['username']) && isset($json['minecraft_verification_code'])){
		$uuid = $json['uuid'];
		$username = $json['username'];
		$minecraft_verification_code = $json['minecraft_verification_code'];
	} else {
		http_response_code(400);
		exit();
	}
	
	// Insert the user
	$sql = "INSERT INTO users (uuid, username, minecraft_verification_code) VALUES (?, ?, ?)";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("sss", $uuid, $username, $minecraft_verification_code);
	$stmt->execute();
	$data["user_id"] = $stmt->insert_id;
	echo json_encode($data);
	exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET'){
	
	if(isset($_GET['uuid'])){
		$uuid = $_GET['uuid'];
	} else {
		http_response_code(400);
		exit();		
	}
	
	// Check that the server secret key matches the server id's secret key
	$sql = "SELECT user_id, minecraft_verification_code FROM Users WHERE uuid = ?";
	$stmt = $conn->stmt_init();
	$stmt->prepare($sql);
	$stmt->bind_param("s", $uuid);
	
	$stmt->execute();
	
	$result = $stmt->get_result();
	
		// Ensure that the server is validated
	if($result->num_rows == 1){		
		$row = $result->fetch_assoc();
		$data["user_id"] = $row["user_id"];
		$data["minecraft_verification_code"] = $row["minecraft_verification_code"];
		echo json_encode($data);
		exit();
	} else {
		$data["user_id"] = -1;
		echo json_encode($data);
		exit();
	}

}