<?php
header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
$domain = !empty($_REQUEST["domain"]) ? strtolower($_REQUEST["domain"]) : false;
$time = !empty($_REQUEST["time"]) ? strtolower($_REQUEST["time"]) : false;
$messages = false;
if ($domain === false) {
	$error = "DOMAIN_MISSING";
} else if ($time === false) {
	$error = "TIME_MISSING";
} else if (!file_exists("messages/$domain")) {
	$error = "NOT_FOUND";
} else {
	$messages = json_decode(file_get_contents("messages/$domain/$time.json"), true);
	$error = false;
}
$outputArray = [
	"error" => $error,
	"messages" => $messages
];
echo json_encode($outputArray);
?>