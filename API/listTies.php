<?php

// Luca D.
// Last updated 2/5/2022
//
// listTies.php
// Given a specific Blackbox customer Discord ID
// this will return a list of their corresponding
// ROBLOX accounts for which they have notifications
// enabled. Expects the user to have at least one
// tied account.

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly required keys / validation
if(!isset($_POST["discord_id"])) {
  errorOut("arguments weren't provided");
} else {
  $discord_user_id = $_POST["discord_id"];
}
$validated = validateUser($dbh, $discord_user_id);
if(!$validated) {
  errorOut("Not validated.");
}

// Selects the user's ties from the database
// and routes the infomation as needed.
try {
  $selObj = $dbh->prepare("SELECT roblox_id FROM `discordUsers` WHERE discord_id = :identifier ORDER BY id ASC");
  $selObj->bindValue(":identifier", $discord_user_id);
  if(!$selObj->execute()) {
    errorOut("Unable to select ties");
  }
  if($selObj->rowCount() > 0) {
    $res = $selObj->fetchAll();
    $returnPackage->ties = new stdClass();
    $returnPackage->ties = $res;
  } else {
    errorOut("No ties were found for this account");
  }
} catch(PDOException $e) {
  errorOut("Database error while searching for discord ties");
}

respond($returnPackage);

?>
