<?php
header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
$domain = !empty($_REQUEST["domain"]) ? strtolower(strip_tags($_REQUEST["domain"])) : false;
$message = !empty($_REQUEST["message"]) ? strip_tags($_REQUEST["message"]) : false;
$time = time();
$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
$identifier = hash("sha256", $ip);
if (file_exists("last-sent/$identifier.json")) {
	$lastSentJson = json_decode(file_get_contents("last-sent/$identifier.json"), true);
	$lastSentTime = $lastSentJson["time"];
} else {
	$lastSentTime = false;
}
$bannedIdentifiers = explode("\n", json_decode(file_get_contents("etc/banned-users.txt"), true));
if (in_array($identifier, $bannedIdentifiers)) {
	$error = "You have been banned!";
} else if ($domain === false) {
	$error = "Please enter a domain";
} else if (!filter_var(gethostbyname($domain), FILTER_VALIDATE_IP)) {
	$error = "Invalid domain name";
} else if ($message === false) {
	$error = "Please enter a message";
} else if ($time < $lastSentTime + 5) {
	$error = "Please slow down! You can only send a message once every 5 seconds.";
} else if (strlen($message) > 500) {
	$error = "Message can't exceed 500 characters";
} else {
	$messageContents = file_get_contents("messages/$domain/$time.json");
	if ($messageContents === false) {
		$messageArray = [];
	} else {
		$messageArray = json_decode($messageContents, true);
	}
	$logContents = file_get_contents("messages/$domain/log.json");
	if ($logContents === false) {
		$logArray = [];
	} else {
		$logArray = json_decode($logContents, true);
	}
	$check = json_decode(file_get_contents("https://www.purgomalum.com/service/json?text=" . urlencode($message)), true);
	$messageFiltered = $check["result"];
	$messageArray[] = [
		"identifier" => $identifier,
		"message" => $messageFiltered,
		"time" => $time
	];
	$logArray[] = [
		"identifier" => $identifier,
		"message" => $messageFiltered,
		"time" => $time
	];
	$directories[] = "messages";
	$directories[] = "messages/$domain";
	$directories[] = "last-sent";
	$directories[] = "identifiers";
	foreach ($directories as $directory) {
		if (!file_exists($directory)) {
    		mkdir($directory, 0777, true);
		}
	}
	file_put_contents("messages/$domain/$time.json", json_encode($messageArray));
	// only save 100 messages in log
	if (count($logArray) > 100) {
		$removed = array_shift($logArray);
	}
	file_put_contents("messages/$domain/log.json", json_encode($logArray));
	$lastSentArray = [
		"time" => $time
	];
	file_put_contents("last-sent/$identifier.json", json_encode($lastSentArray));
	$identifierArray = [
		"ip" => $ip
	];
	file_put_contents("identifiers/$identifier.json", json_encode($identifierArray));
	$error = false;
}
$outputArray = [
	"error" => $error
];
echo json_encode($outputArray);
?>