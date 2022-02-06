<?php

// Luca D.
// Last updated 2/5/2022
//
// validateUser.php
// Discord Bot focused endpoint that returns
// if a given Discord user ID is authenticated
// to use Blackbox services.

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly necessary keys
if(!isset($_POST["user_id"])) {
  errorOut("arguments weren't provided");
} else {
  $user = $_POST["user_id"];
}

// Core functionality lies in `./assets/functions.php`
$validated = validateUser($dbh, $user);

// Respond with validation
$returnPackage->validated = new stdClass();
$returnPackage->validated = $validated;
respond($returnPackage)


?>
