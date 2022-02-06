<?php

// Luca D.
// Last updated 2/5/2022
//
// backendAnalysis.php
// Analyst-focused endpoint that returns current market
// information for a list of materials given the current
// portfolio object. Appends new information to the object
// and returns the value.
// Required keys:
//   1.) List of materials as strings
//   2.) Portfolio object (see Discord Bot/app.js)

// Required for API endpoints, initializes rest of document
header("Content-Type: application/json; charset=UTF-8");
require_once "assets/functions.php";
require_once "assets/config.php";

// Requirements: materials, portfolio
if (isset($_POST["materials"]) && isset($_POST["portfolio"])) {
    $materials = $_POST["materials"];
    $portfolio = $_POST["portfolio"];
} else {
    errorOut("arguments not provided");
}

// Restrict analysis information to the main market
$location = "Citadel";
$dump = new stdClass();

// For each material requested by the POST request,
// 1.) Find how many are being sold
// 2.) Find the most recent block of orders
// 3.) Return the data for all materials
foreach ($materials as $index => $material) {
    // Create a child object
    $dump->$material = new stdClass();
    // Grab 
    try {
        $rateSelObj = $dbh->prepare("
            SELECT * FROM `marketMovement`
            WHERE
                material = :material AND
                location = :location AND
                ts > date_sub(now(), interval 1 day)
            ORDER BY id DESC;
        ");
        $rateSelObj->bindValue(":material", $material);
        $rateSelObj->bindValue(":location", $location);
        if (!($rateSelObj->execute())) {
            errorOut("Could not find rates");
        }
        $dump->$material->rate = new stdClass();
        $dump->$material->rate = $rateSelObj->fetchAll();
    } catch (PDOException $e) {
        errorOut("Error doing database stuff");
    }

    try {
        $ordersObj = $dbh->prepare("
            SELECT * FROM `marketOrders`
            WHERE
                (SELECT id FROM `marketOrders` WHERE material AND location = :location ORDER BY id DESC LIMIT 1) - id < 25
                AND ts BETWEEN DATE_SUB((SELECT ts FROM `marketOrders` WHERE material = :mat AND location = :location ORDER BY id DESC LIMIT 1), INTERVAL 3 second)
                AND DATE_ADD((SELECT ts FROM `marketOrders` WHERE material = :mat AND location = :location ORDER BY id DESC LIMIT 1), INTERVAL 3 second)
                AND material=:mat
                AND location = :location
            ORDER BY id DESC;
        ");
        $ordersObj->bindValue(":mat", $material);
        $ordersObj->bindValue(":location", $location);
        if (!$ordersObj->execute()) {
            errorOut($ordersObj->errorInfo());
        }
        $resultingOrders = $ordersObj->fetchAll();
        foreach ($resultingOrders as $id => $row) {
            $row["user_id"] = findUsername($row["user_id"], $dbh);
        }
        $dump->$material->orders = new stdClass();
        $dump->$material->orders = $resultingOrders;
    } catch (PDOException $e) {
        errorOut("Could not get information from the database");
    }
}

// Structure response object and return
$returnPackage->portfolio = new stdClass();
$returnPackage->portfolio = $portfolio;
$returnPackage->dump = new stdClass();
$returnPackage->dump = $dump;
respond($returnPackage);
