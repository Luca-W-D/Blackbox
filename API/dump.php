<?php

//////////////////////////////////////////
// TODO: Decrease clutter of repeated SQL
// statements. Low priority; this api
// is nearly deprecated
//////////////////////////////////////////

// Luca D.
// Last updated 2/5/2022
//
// dump.php
// Provides a debug-only service where all data
// is collected for the given locations and returned
// as an excessively large response. Generally used 
// for analysis or a one-time service for customers. 

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Iterates over every location and
// 1.) Finds the prices for every material at every location
// 2.) 
foreach ($locations as &$location) {
  try {
    $sellObj = $dbh->prepare("
    select *
    from bestOffers
    where id in
    (
      select max(id)
      from bestOffers
      where location=:loc
      and type = 'sell'
      and price is not null
      and total_volume is not null
      and material is not null
      group by material
    )
    ");
    $sellObj->bindValue(":loc", $location);
    $sellObj->execute();
    $sells = $sellObj->fetchAll();
  } catch (PDOException $e) {
    errorOut("Could not find highest sell order");
  }

  try {
    $buyObj = $dbh->prepare("
    SELECT *
    FROM bestOffers
    WHERE id IN
      (
        SELECT max(id)
        FROM bestOffers
        WHERE location=:loc AND
        type = 'buy'
        GROUP BY material
      ) AND
      price IS NOT NULL
      AND total_volume IS NOT NULL
      AND material IS NOT NULL
    ");
    $buyObj->bindValue(":loc", $location);
    $buyObj->execute();
    $buys = $buyObj->fetchAll();
  } catch (PDOException $e) {
    errorOut("Could not find highest buy order");
  }

  // Create the response object
  $prices = [];
  foreach ($sells as &$record) {
    $prices[strtolower($record["material"])] = [];
    $prices[strtolower($record["material"])]["name"] = $record["material"];
    $prices[strtolower($record["material"])]["sell"] = $record["price"];
  }
  foreach ($buys as &$record) {
    $prices[strtolower($record["material"])]["buy"] = $record["price"];
    $prices[strtolower($record["material"])]["volume"] = $record["total_volume"];
  }

  // Create an object for the specific location
  $returnPackage->$location = [];
  // Extend the object and prepare for materials
  $returnPackage->$location["location"] = new stdClass();
  $returnPackage->$location["location"] = $location;
  // Extend locations to add materials
  foreach($material as &$material) {
    $returnPackage->$location[$material] = new stdClass();
  }
  // Extend materials to add 
  foreach($material as &$material) {
    $returnPackage->$location[$material]->data = new stdClass();
  }
  // Add data to each material
  foreach($material as &$material) {
    $returnPackage->$location[$material]->data = $prices[$material];
  }

  // Find most recent orders for all materials
  foreach ($materials as &$mat) {
    try {
      $matObjBuy = $dbh->prepare("SELECT * FROM `marketOrders` WHERE material=:mat AND location=:location AND price IS NOT NULL AND ts = (SELECT max(ts) FROM `marketOrders` WHERE material=:mat and location=:location and type='sell') ORDER BY id ASC");
      $matObjBuy->bindValue(":mat", $mat);
      $matObjBuy->bindValue(":location", $location);
      $matObjBuy->execute();
      $orders = $matObjBuy->fetchAll();
    } catch (PDOException $e) {
      errorOut("Could not get information from the database");
    }
    try {
      $matObjBuy = $dbh->prepare("SELECT * FROM `marketOrders` WHERE material=:mat AND location=:location AND price IS NOT NULL AND ts = (SELECT max(ts) FROM `marketOrders` WHERE material=:mat and location=:location and type='buy') ORDER BY id DESC");
      $matObjBuy->bindValue(":mat", $mat);
      $matObjBuy->bindValue(":location", $location);
      $matObjBuy->execute();
      // Add to list of orders
      array_merge($orders, $matObjBuy->fetchAll());
    } catch (PDOException $e) {
      errorOut("Could not get information from the database");
    }
    // Append usernames to every order
    foreach ($orders as &$record) {
      $id = $record["user_id"];
      $record["user_id"] = findUsername($id, $dbh);
      $record["original_user_id"] = $id;
    }

    // Extend response to include orders and materials
    $returnPackage->$location[$mat]->orders = new stdClass();
    $returnPackage->$location[$mat]->material = new stdClass();
    $returnPackage->$location[$mat]->material = $mat;
    $returnPackage->$location[$mat]->orders = $orders;
  }
}

respond($returnPackage);
