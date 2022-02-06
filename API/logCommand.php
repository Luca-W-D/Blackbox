<?php

// Luca D.
// Last updated 2/5/2022
//
// logCommand.php
// Run whenever the Discord Bot is triggered, this endpoint
// ensures that long-term abuse is recorded and handled appropriately
// to avoid shared accounts / DOS attacks / strange behavior. 
// Required keys:
//  1.) Discord User ID (validated or not)
//  2.) Their command represented as a string

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly required keys
if(isset($_POST["user_id"]) && isset($_POST["command"])) {
  $user_id = $_POST["user_id"];
  $command = $_POST["command"];
} else {
  errorOut("arguments not provided");
}

// Simple insertion object that includes the user and requested command.
try {
  $insObj = $dbh->prepare("INSERT INTO `discordCommands` (user_id, content) VALUES (:user_id, :command)");
  $insObj->bindValue(":user_id", $user_id);
  $insObj->bindValue(":command", $command);
  if(!($insObj->execute())) {
    errorOut("Could not insert");
  }
} catch(PDOException $e) {
  errorOut("Error doing database stuff");
}

// Respond appropriately
$returnPackage->info = new stdClass();
$returnPackage->info = "Logged command.";
respond($returnPackage);

?>
