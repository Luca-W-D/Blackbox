<?php

//////////////////////////////////////////
// TODO: Merge with discordUntie.php
//////////////////////////////////////////

// Luca D.
// Last updated 2/5/2022
//
// discordTie.php
// Blackbox customer focused endpoint
// that given a Discord ID and a ROBLOX
// ID will connect notifications for the
// customer.
// Required keys:
//   1.) Discord ID as a string from the
//       user who sent the tie request.
//   2.) ROBLOX ID to tie to the customer

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Require keys
if (!isset($_POST["discord_id"]) || !isset($_POST["roblox_id"])) {
  errorOut("arguments weren't provided");
} else {
  $discord_user_id = $_POST["discord_id"];
  $roblox_user_id = $_POST["roblox_id"];
}

// Validate types and permissions
if (!is_numeric($roblox_user_id)) {
  errorOut("Expected roblox id (received a non-number)");
}
$validated = validateUser($dbh, $discord_user_id);
if (!$validated) {
  errorOut("User does not have permission to access this service");
}

// Attempt to insert into the database
try {
  $insObj = $dbh->prepare("INSERT IGNORE INTO discordUsers (discord_id, roblox_id) VALUES(:discord_id, :roblox_id)");
  $insObj->bindValue(":discord_id", $discord_user_id);
  $insObj->bindValue(":roblox_id", $roblox_user_id);
  if (!$insObj->execute()) {
    errorOut("Failed to tie customer to the ROBLOX ID");
  } else {
    $returnPackage->data = new stdClass();
    $returnPackage->data = true;
  }
} catch (PDOException $e) {
  errorOut("Failed to tie customer to the ROBLOX ID");
}

respond($returnPackage);
