<?php

// Luca D.
// Last updated 2/5/2022
//
// userInfo.php
// Given a specific ROBLOX diverse identifier (username / uid)
// This customer focused endpoint returns relevant information about
// their recent and historical data. Also includes subjective Blackbox
// data such as market ranking.

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";


// Strictly necessary keyds and validation
if(!isset($_POST["user_identifier"]) || !isset($_POST["authorId"])) {
  errorOut("arguments weren't provided");
} else {
  $userIdentifier = $_POST["user_identifier"];
  $author = $_POST["authorId"];
}

// The command also allows users to run `b user self` and expects
// Blackbox to return with their own information. Every user
// is tied to their first notification slot, so getIdOfAuthor(..., 0)
// will return their ROBLOX ID.
if($userIdentifier == "self") {
  $userIdentifier = getIdOfAuthor($author, $dbh, 0);
}

// Furthermore, some users may choose to query one of the users they
// have tied for notifications. Up to 100 notifications are supported,
// and it is safe to assume that no ROBLOX users with usernames [0-99]
// will be active on the Starscape market given our current information
if(is_numeric($userIdentifier)) {
  if($userIdentifier < 100) {
    $userIdentifier = getIdOfAuthor($author, $dbh, $userIdentifier - 1);
  }
}


// Use `./assests/functions.php` to find relevant information
// about the user
$report = reportCurrentTrades($userIdentifier, $dbh);

// Preparing response format
$returnPackage->uid = new stdClass();
$returnPackage->uid = $userIdentifier;
$returnPackage->report = new stdClass();
$returnPackage->report = $report;

// Collect information about the user's rank
$uid = getIdFromIdentifier($userIdentifier, $dbh)[1]; // get username
try {
  $selObj = $dbh->prepare("WITH `countTable` AS ( SELECT username, ROW_NUMBER() OVER ( ORDER BY count DESC) AS 'countRow' FROM `robloxUsers`) SELECT countRow, username FROM `countTable` WHERE username = :uid LIMIT 1");
  $selObj->bindValue(":uid", $uid);
  if(!$selObj->execute()) {
    return False;
  }
  $resObj = $selObj->fetch();
  if($selObj->rowCount() > 0) {
    $returnPackage->rank = new stdClass();
    $returnPackage->rank = $resObj["countRow"];
  }
} catch(PDOException $e) {
  errorOut("Database error while selecting user rank");
}

respond($returnPackage);
