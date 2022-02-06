<?php


// Luca D.
// Last updated 2/5/2022
//
// build.php
// Should not be exposed to the public.
// Sample SQL statement to build core
// Discord database tables.

require_once "assets/functions.php";
require_once "assets/config.php";

$statement = "CREATE TABLE `userAccounts` (
  id INT NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(255),
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);";

$statement = "CREATE TABLE `robloxUsers` (
  id INT NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(255),
  username VARCHAR(255),
  checked BOOLEAN,
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);";

try {
  $obj = $dbh->prepare($statement);
  $resObj = $obj->execute();
  var_dump($resObj);
} catch (PDOException $e) {
  errorOut("Unable to create table");
}
