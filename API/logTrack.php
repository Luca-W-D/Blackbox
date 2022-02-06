<?php

// Luca D.
// Last updated 2/5/2022
//
// logTrack.php
// As notifications are sent to Discord users,
// inevitably some will be lost due to an error.
// Since notifications are generated realtime, we
// store them for later reference, whether to analyze
// when a notification was dropped or to retroactively
// track market actions during a specic period. 
// Required keys:
//  1.) User id of the ROBLOX User whos order
//      triggered the notification
//  2.) Material in question that triggered the
//      notification
//  3.) The type of order being analyzed

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly required values and validation
if(isset($_POST["user_id"]) && isset($_POST["material"]) && isset($_POST["type"])) {
  $type = $_POST["type"];
  $user_id = $_POST["user_id"];
  $material = $_POST["material"];
} else {
  errorOut("arguments not provided");
}

try {
  $insObj = $dbh->prepare("INSERT INTO `trackingNotifications` (type, user_id, material) VALUES (:type, :user_id, :material)");
  $insObj->bindValue(":type", $type);
  $insObj->bindValue(":user_id", $user_id);
  $insObj->bindValue(":material", $material);
  if(!($insObj->execute())) {
    errorOut("Could not insert notification track");
  }
} catch(PDOException $e) {
  errorOut("Database error while inserting notificaiotn track");
}

// Responding appropriately
$returnPackage->info = new stdClass();
$returnPackage->info = "Logged notification.";
respond($returnPackage);

?>
