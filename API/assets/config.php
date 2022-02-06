<?php

// Luca D.
// Last updated 2/5/2022

try {
  $DB_HOST = "";
  $DB_NAME = "";
  $DB_USERNAME = "";
  $DB_PASSWORD = "";
  $dbh = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME}", "{$DB_USERNAME}", "{$DB_PASSWORD}");
} catch (PDOException $e) {
  errorOut("DB connection failed to initialize");
}

?>
