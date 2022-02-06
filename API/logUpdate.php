<?php

//////////////////////////////////////////
// TODO: Make an interative `checkKey` function
// to declutter "strictly necessary key" line
//////////////////////////////////////////

// Luca D.
// Last updated 2/5/2022
//
// logUpdate.php
// Expansion on the logTrack notification
// Update notifications are unique in that they
// provide unique insight into market orders.
// When the quantity of an order changes, they either
// relisted their item (added more items to the order)
// or sold some of their items.
// Required keys:
//  1.) ROBLOX User id
//  2.) Material of the order
//  3.) The amount fulfilled or added to the order
//  4.) The type of order (buy / sell)
//  5.) Whether the update represents an order
//      fulfilled or relisted.
//  6.) The price at which the items were modified
//  7.) Where the order occured

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly necessary keys
if(isset($_POST["user_id"]) && isset($_POST["material"]) && isset($_POST["amount"]) && isset($_POST["type_of_order"]) && isset($_POST["type_of_update"]) && isset($_POST["price"]) && isset($_POST["location"])) {
  $user_id = $_POST["user_id"];
  $material = $_POST["material"];
  $amount = $_POST["amount"];
  $type_order = $_POST["type_of_order"];
  $type_update = $_POST["type_of_update"];
  $price = $_POST["price"];
  $location = $_POST["location"];
} else {
  errorOut("arguments not provided");
}

try {
  $insObj = $dbh->prepare("INSERT INTO `marketMovement` (user_id, material, amount, type_of_order, type_of_update, price, location) VALUES (:user_id, :material, :amount, :type_of_order, :type_of_update, :price, :location)");
  $insObj->bindValue(":user_id", $user_id);
  $insObj->bindValue(":material", $material);
  $insObj->bindValue(":amount", $amount);
  $insObj->bindValue(":type_of_order", $type_order);
  $insObj->bindValue(":type_of_update", $type_update);
  $insObj->bindValue(":price", $price);
  $insObj->bindValue(":location", $location);
  if(!($insObj->execute())) {
    errorOut("Could not insert update notification");
  }
} catch(PDOException $e) {
  errorOut("Database error while inserting update notification");
}

$returnPackage->info = new stdClass();
$returnPackage->info = "Logged order update.";

respond($returnPackage);

?>
