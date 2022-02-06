<?php

// Luca D.
// Last updated 2/5/2022
//
// findDiscordIdMultiple.php
// Given a ROBLOX ID, this returns the
// list of ID's of Blackbox customers
// attached to that account. Furthermore,
// the endpoint attaches critical information
// about the account including recent orders.
// Required keys:
//   1.) Roblox user identifier (username / id)
//   2.) newOffer/oldOffer information

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly required keys
if(!isset($_POST["roblox_id"]) || !isset($_POST["newOffer"]) || !isset($_POST["oldOffer"])) {
  errorOut("arguments weren't provided");
}

// Takes either their roblox id or username, ensures
// that the given data is their username
$roblox_id = getIdFromIdentifier($_POST["roblox_id"], $dbh)[0];
// Extracts information from the machine-generated current order
// object. Increases readability.
$roblox_id_new = $_POST["newOffer"][2];
$roblox_id_old = $_POST["oldOffer"][2];
$quantity_new = $_POST["newOffer"][1];
$quantity_old = $_POST["oldOffer"][1];
$price_new = $_POST["newOffer"][0];
$price_old = $_POST["oldOffer"][0];

/////////////////////////
// Historical market data 

// Find recent historical data associated with this order
try {
  $newObjSel = $dbh->prepare("SELECT * FROM `marketOrders` WHERE price=:price AND user_id = :roblox_id AND quantity = :quantity ORDER BY id DESC LIMIT 1");
  $newObjSel->bindValue(":quantity", $quantity_new);
  $newObjSel->bindValue(":price", $price_new);
  $newObjSel->bindValue(":roblox_id", $roblox_id_new);
  $newObjSel->execute();
  $newObjOrder = $newObjSel->fetch();
} catch(PDOException $e) {
  errorOut("Could not get information from the database");
}

// Find historical data associated with the order that was replaced
// Allows comparison between the old information and the new status
try {
  $oldObjSel = $dbh->prepare("SELECT * FROM `marketOrders` WHERE price=:price AND user_id = :roblox_id AND quantity = :quantity ORDER BY id DESC LIMIT 1");
  $oldObjSel->bindValue(":quantity", $quantity_old);
  $oldObjSel->bindValue(":price", $price_old);
  $oldObjSel->bindValue(":roblox_id", $roblox_id_old);
  $oldObjSel->execute();
  $oldObjOrder = $oldObjSel->fetch();
} catch(PDOException $e) {
  errorOut("Could not get information from the database");
}

/////////////////////////
// Blackbox Discord Customer information

try {
  $selObj = $dbh->prepare("SELECT discord_id FROM `discordUsers` WHERE roblox_id = :user_id");
  $selObj->bindValue(":user_id", $roblox_id);
  if(!$selObj->execute()) {
    errorOut("query failed");
  }
  // If users are associated
  if($selObj->rowCount() > 0) {
    $res = $selObj->fetchAll();
    $returnPackage->found = new stdClass();
    $returnPackage->found = "true";
    $ids = [];
    foreach($res as &$record) {
      // add only the relevant id's; objects
      // as a whole are not needed.
      array_push($ids, $record["discord_id"]);
    }
    // Create a specific return format
    $returnPackage->ids = new stdClass();
    $returnPackage->ids = $ids;
    // Type of order
    $returnPackage->type = new stdClass();
    $returnPackage->type = $_POST["type"];
    $returnPackage->oldOffer = new stdClass();
    $returnPackage->oldOffer = $oldObjOrder;
    // Add relevant information about the user's
    // **username** (ID's are not readable)
    $returnPackage->oldOffer["user_id"] = findUsername($returnPackage->oldOffer["user_id"], $dbh);
    $returnPackage->newOffer = new stdClass();
    // And information about the new user
    $returnPackage->newOffer = $newObjOrder;
    $returnPackage->newOffer["user_id"] = findUsername($returnPackage->newOffer["user_id"], $dbh);

  } else {
    $returnPackage->found = new stdClass();
    $returnPackage->found = false; // no customers attached
  }
} catch(PDOException $e) {
  errorOut("Error while trying to find any users associated with this account");
}

respond($returnPackage);
