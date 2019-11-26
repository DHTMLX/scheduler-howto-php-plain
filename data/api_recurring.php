<?php
require_once("config.php");

function read($db, $requestParams){
	$queryParams = [];
	$queryText = "SELECT * FROM `recurring_events`";
	if (isset($requestParams["from"]) && isset($requestParams["to"])) {
		$queryText .= " WHERE `end_date`>=? AND `start_date` < ?;";
		$queryParams = [$requestParams["from"], $requestParams["to"]];
	}
	$query = $db->prepare($queryText);
	$query->execute($queryParams);
	$events = $query->fetchAll(PDO::FETCH_ASSOC);
	foreach($events as $index=>$event){
		$events[$index]["text"] = htmlentities($event["text"]);
	}
	return $events;
}

function create($db, $event){
	$queryText = "INSERT INTO `recurring_events` SET
		`start_date`=?,
		`end_date`=?,
		`text`=?,

		`event_pid`=?,
		`event_length`=?,
		`rec_type`=?";
	$queryParams = [
		$event["start_date"],
		$event["end_date"],
		$event["text"],
		// recurring events columns
		$event["event_pid"] ? $event["event_pid"] : 0,
		$event["event_length"] ? $event["event_length"] : 0,
		$event["rec_type"]
	];

	$query = $db->prepare($queryText);
	$query->execute($queryParams);

	return $db->lastInsertId();
}

function update($db, $event, $id){
	$queryText = "UPDATE `recurring_events` SET
		`start_date`=?,
		`end_date`=?,
		`text`=?,
		`event_pid`=?,
		`event_length`=?,
		`rec_type`=?
		WHERE `id`=?";

	$queryParams = [
		$event["start_date"],
		$event["end_date"],
		$event["text"],

		$event["event_pid"] ? $event["event_pid"] : 0,
		$event["event_length"] ? $event["event_length"] : 0,
		$event["rec_type"],//!
		$id
	];
	if ($event["rec_type"] && $event["rec_type"] != "none") {
		//all modified occurrences must be deleted when you update recurring series
		//https://docs.dhtmlx.com/scheduler/server_integration.html#savingrecurringevents
		$subQueryText = "DELETE FROM `recurring_events` WHERE `event_pid`=? ;";
		$subQuery = $db->prepare($subQueryText);
		$subQuery->execute([$id]);
	}

	$query = $db->prepare($queryText);
	$query->execute($queryParams);
}

function delete($db, $id){
	// some logic specific to recurring events support
	// https://docs.dhtmlx.com/scheduler/server_integration.html#savingrecurringevents
	$subQueryText = "SELECT * FROM `recurring_events` WHERE id=? LIMIT 1;";
	$subQuery = $db->prepare($subQueryText);
	$subQuery->execute([$id]);
	$event = $subQuery->fetch();

	if ($event["event_pid"]) {
		// deleting a modified occurrence from a recurring series
		// If an event with the event_pid value was deleted - it needs updating
		// with rec_type==none instead of deleting.
		$subQueryText="UPDATE `recurring_events` SET `rec_type`='none' WHERE `id`=?;";
		$subQuery = $db->prepare($subQueryText);
		$subQuery->execute([$id]);

	}else{
		if ($event["rec_type"] && $event["rec_type"] != "none") {//!
			// if a recurring series deleted, delete all modified occurrences of the series
			$subQueryText = "DELETE FROM `recurring_events` WHERE `event_pid`=? ;";
			$subQuery = $db->prepare($subQueryText);
			$subQuery->execute([$id]);
		}

		/*
		end of recurring events data processing
		*/

		$queryText = "DELETE FROM `recurring_events` WHERE `id`=? ;";
		$query = $db->prepare($queryText);
		$query->execute([$id]);
	}
}

try {
	$db = new PDO($dsn, $username, $password, $options);
	switch ($_SERVER["REQUEST_METHOD"]) {
		case "GET":
			$result = read($db, $_GET);
		break;
		case "POST":
			$requestPayload = json_decode(file_get_contents("php://input"));
			$id = $requestPayload->id;
			$action = $requestPayload->action;
			$body = (array) $requestPayload->data;

			$result = [
				"action" => $action
			];

			if ($action == "inserted") {
				$databaseId = create($db, $body);
				$result["tid"] = $databaseId;
				// delete a single occurrence from  recurring series
				if ($body["rec_type"] === "none") {
					$result["action"] = "deleted";//!
				}
			} elseif($action == "updated") {
				update($db, $body, $id);
			} elseif($action == "deleted") {
				delete($db, $id);
			}
		break;
		default: throw new Exception("Unexpected Method"); break;
	}
} catch (Exception $e) {
	http_response_code(500);
	$result = [
		"action" => "error",
		"message" => $e->getMessage()
	];
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");
echo json_encode($result);