<?php

//////////////////////////////////////////
// TODO: Decrease clutter of repeated SQL
// statements. Med priority; endpoint is
// expected to lose support
//////////////////////////////////////////

// Luca D.
// Last updated 2/5/2022
//
// logRates.php
// Triggered by the backend of the Discord Bot every
// X seconds to create a long-term record of how often
// items are bought / sold without recalculating the
// values on every query.
// Required keys:
//  1.) Rates object, see `app.js` for format.

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly required value
if(isset($_POST["rates"])) {
  $rates = $_POST["rates"];
} else {
  errorOut("arguments not provided");
}

foreach($rates["Citadel"] as $i => $row) {
  // Some rates may have edge case errors where
  // either a price, quantity, or author may
  // be missing. In that case, avoid the row
  if(count($row) != 3) continue;
  
  // Extracting information about the row
  $location = "Citadel";
  $material = $row[0];
  $rate = $row[1];
  $type = $row[2];

  try {
    $insObj = $dbh->prepare("INSERT INTO `itemRates` (location, material, rate, type) VALUES (:location, :material, :rate, :type)");
    $insObj->bindValue(":location", $location);
    $insObj->bindValue(":material", $material);
    $insObj->bindValue(":rate", $rate);
    $insObj->bindValue(":type", $type);
    if(!($insObj->execute())) {
      errorOut("Could not insert market rates for Citadel");
    }
  } catch(PDOException $e) {
    errorOut("Database error while inserting market rates for Citadel");
  }

}

// Harbor may be disabled / enabled depending on the 
// projects given resources and active in-game bots.
// Allows for either condition
if(isset($rates["Harbor"])) {
  foreach($rates["Harbor"] as $i => $row) {
    // Extracting information about the row
    $location = "Harbor";
    $material = $row[0];
    $rate = $row[1];
    $type = $row[2];

    try {
      $insObj = $dbh->prepare("INSERT INTO `itemRates` (location, material, rate, type) VALUES (:location, :material, :rate, :type)");
      $insObj->bindValue(":location", $location);
      $insObj->bindValue(":material", $material);
      $insObj->bindValue(":rate", $rate);
      $insObj->bindValue(":type", $type);
      if(!($insObj->execute())) {
        errorOut("Could not insert market rates for Harbor");
      }
    } catch(PDOException $e) {
      errorOut("Database error while inserting market rates for Harbor");
    }

  }
}

// Respond appropriately
$returnPackage->info = new stdClass();
$returnPackage->info = "Logged rates.";
respond($returnPackage);

?>
