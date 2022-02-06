<?php

// Luca D.
// Last updated 2/5/2022
//
// item.php
// Primary endpoint that provides information about
// a requested material.
// Required keys:
//  1.) Location where the item should be checked
//  2.) Material to check
//  3.) Discord ID of the author's account

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Strictly required keys / validation
if(!isset($_POST["location"]) || !isset($_POST["material"]) || !isset($_POST["authorId"])) {
  errorOut("arguments weren't provided");
} else {
  $location = $_POST["location"];
  $mat = $_POST["material"];
  $user = $_POST["authorId"];
}
if(!is_numeric($user)) {
  errorOut("Not a number");
}
$validated = validateUser($dbh, $user);
if(!$validated) {
  $returnPackage->validated = new stdClass();
  $returnPackage->validated = False;
  errorOut("Not validated.");
} else {
  $returnPackage->validated = new stdClass();
  $returnPackage->validated = True;
}

// Quality of life improvements for users
// Strings may be loosely typed to allow for
// IV ==> 4, bp ==> blueprint, etc.
// They're used interchangeably in game.
$mat_alt = "1234567890123456789012345678901234567890";
if(str_contains($mat, "bp")) {
  $mat_alt = str_replace("bp", "blueprint", $mat);
}
if(str_contains($mat, "blueprint")) {
  $mat_alt = str_replace("blueprint", "bp", $mat);
}

try {
  // Select the relevant buy orders
  $matObjBuy = $dbh->prepare("
    SELECT * FROM `marketOrders`
    WHERE
      (SELECT id FROM `marketOrders` WHERE (material = :mat OR material = :alternate_material) AND location = :location ORDER BY id DESC LIMIT 1) - id < 25
      AND ts BETWEEN DATE_SUB((SELECT ts FROM `marketOrders` WHERE (material = :mat OR material = :alternate_material) AND location = :location ORDER BY id DESC LIMIT 1), INTERVAL 3 second)
      AND DATE_ADD((SELECT ts FROM `marketOrders` WHERE (material = :mat OR material = :alternate_material) AND location = :location ORDER BY id DESC LIMIT 1), INTERVAL 3 second)
      AND type=\"buy\"
      AND (material=:mat
      OR material=:alternate_material)
      AND location = :location
    ORDER BY id DESC;
  ");
  $matObjBuy->bindValue(":mat", $mat);
  $matObjBuy->bindValue(":alternate_material", $mat_alt);
  $matObjBuy->bindValue(":location", $location);
  if(!$matObjBuy->execute()) {
      errorOut($selObj->errorInfo());
  }
  $responseBuy = $matObjBuy->fetchAll();
} catch(PDOException $e) {
  errorOut("Could not get buy information from the database");
}

try {
  // Select relevant sell orders
  $matObjSell = $dbh->prepare("
    SELECT * FROM `marketOrders`
    WHERE
      (SELECT id FROM `marketOrders` WHERE (material = :mat OR material = :alternate_material) AND location = :location ORDER BY id DESC LIMIT 1) - id < 25
      AND ts BETWEEN DATE_SUB((SELECT ts FROM `marketOrders` WHERE (material = :mat OR material = :alternate_material) AND location = :location ORDER BY id DESC LIMIT 1), INTERVAL 3 second)
      AND DATE_ADD((SELECT ts FROM `marketOrders` WHERE (material = :mat OR material = :alternate_material) AND location = :location ORDER BY id DESC LIMIT 1), INTERVAL 3 second)
      AND type='sell'
      AND (material=:mat
      OR material=:alternate_material)
      AND location = :location
    ORDER BY id DESC;
  ");
  $matObjSell->bindValue(":mat", $mat);
  $matObjSell->bindValue(":alternate_material", $mat_alt);
  $matObjSell->bindValue(":location", $location);
  if(!$matObjSell->execute()) {
      errorOut($matObjSell->errorInfo());
  }  $responseSell = $matObjSell->fetchAll();
} catch(PDOException $e) {
  errorOut("Could not get information from the database");
}

try {
  // Find best offers
  $bestOffersObj = $dbh->prepare("SELECT * FROM `bestOffers` WHERE (material=:mat OR material = :alternate_material) AND location=:location ORDER BY ts DESC LIMIT 2;");
  $bestOffersObj->bindValue(":mat", $mat);
  $bestOffersObj->bindValue(":alternate_material", $mat_alt);
  $bestOffersObj->bindValue(":location", $location);
  $bestOffersObj->execute();
  $offers = $bestOffersObj->fetchAll();
} catch(PDOException $e) {
  errorOut("Could not get information from the database");
}

// Append username information
foreach($responseBuy as &$record) {
  $id = $record["user_id"];
  $record["user_id"] = findUsername($id, $dbh);
}
foreach($responseSell as &$record) {
  $id = $record["user_id"];
  $record["user_id"] = findUsername($id, $dbh);
}

// If there was an issue finding the generic name, try to
// aid the search by using alternate names
if(count($offers) == 0) {
  $returnPackage->recordsFound = new stdClass();
  $returnPackage->recordsFound = false;
  try {
    $selObj = $dbh->prepare("SELECT DISTINCT material FROM `marketOrders` WHERE material LIKE CONCAT('%', :mat, '%') AND id > 5000000");
    $selObj->bindValue(":mat", $mat);
    if(!$selObj->execute()) {
        errorOut($selObj->errorInfo());
    }
    $returnPackage->alternatives = new stdClass();
    $returnPackage->alternatives = $selObj->fetchAll();
    if($selObj->rowCount() == 0) {
      $selObj = $dbh->prepare("SELECT DISTINCT material FROM `marketOrders` WHERE material LIKE CONCAT('%', :mat, '%') AND id > 5000000");
      $selObj->bindValue(":mat", substr($mat, 0, floor(strlen($mat) / 2)));
      if(!$selObj->execute()) {
          errorOut($selObj->errorInfo());
      }
      $returnPackage->alternatives = new stdClass();
      $returnPackage->alternatives = $selObj->fetchAll();
    }
    respond($returnPackage);
  }catch(PDOException $e) {
    errorOut($e);
  }
}

try {
  $returnPackage->recordsFound = new stdClass();
  $returnPackage->recordsFound = true;
  $returnPackage->material = new stdClass();
  $returnPackage->buyOrders = new stdClass();
  $returnPackage->sellOrders = new stdClass();
  $returnPackage->volume = new stdClass();
  $returnPackage->time = new stdClass();
  $returnPackage->bestOfferBuy = new stdClass();
  $returnPackage->bestOfferSell = new stdClass();

  $returnPackage->material = $mat;
  $returnPackage->buyOrders = $responseBuy;
  $returnPackage->sellOrders = $responseSell;
  $returnPackage->volume = $offers[0]["total_volume"];
  $returnPackage->time = $offers[0]["ts"];
  $returnPackage->bestOfferBuy = $offers[0]["price"];
  $returnPackage->bestOfferSell = $offers[1]["price"];
} catch(Exception $e) {
  errorOut($e);
}
respond($returnPackage);
