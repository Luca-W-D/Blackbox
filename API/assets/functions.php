<?php

// Luca D.
// Last updated 2/5/2022

// Generic response object used for all APIs
$returnPackage = new stdClass();

// Basic global values
$locations = ["harbor", "citadel"];
$materials = ["korrelite", "gellium", "water", "red narcor", "vexnium", "narcor", "reknite", "axnit"];

// Will immediately exit the program with the
// given response object.
function respond($package) {
  echo json_encode($package);
  exit();
}

// Given an error message, this will immediately
// exit the program and give a standardized
// error format response.
function errorOut($string) {
  global $returnPackage;
  $returnPackage->error = new stdClass();
  $returnPackage->error = $string;
  respond($returnPackage);
  exit();
}

// Given a discord user id, this will return
// whether the given user is able to use this service
function validateUser($dbh, $uid) {
  try {
    $validated = False;
    $selObj = $dbh->prepare("SELECT * FROM `userAccounts` WHERE user_id = :uid");
    $selObj->bindValue(":uid", $uid);
    if(!($selObj->execute())) {
      errorOut("Could not check");
    }
    if($selObj->rowCount() != 0) {
      $validated = True;
    }
    return $validated;
  } catch(PDOException $e) {
    errorOut("Error doing Discord authentication");
  }
}

// Given the ROBLOX id from an in-game order, this method
// will return the given username. It also increments
// a global counter of how often the user is referenced.
// This user score later helps generate a heat-map-esque
// functionality of key market players.
function findUsername($id, $dbh) {
  try {
    $selObj = $dbh->prepare("SELECT username FROM `robloxUsers` WHERE user_id=:id; UPDATE `robloxUsers` SET count = count + 1 WHERE user_id=:id");
    $selObj->bindValue(":id", $id);
    if(!$selObj->execute()) {
      return $id;
    }
    if($selObj->rowCount() > 0) {
      return $selObj->fetch()["username"];
    } else {
      return $id;
    }
  } catch(PDOException $e) {
    return $id;
  }
}

// Extends findUsername functionality to include diverse
// identification. Sometimes usernames are referenced by
// id but are also associated with specific usernames.
// Similarly increases the count mentioned above.
function getIdFromIdentifier($id, $dbh) {
  try {
    $selObj = $dbh->prepare("SELECT user_id, username FROM `robloxUsers` WHERE user_id = :identifier OR username = :identifier; UPDATE `robloxUsers` SET count = count + 1 WHERE user_id=\":id\"");
    $selObj->bindValue(":identifier", $id);
    if(!$selObj->execute()) {
      return False;
    }
    if($selObj->rowCount() > 0) {
      $res = $selObj->fetch();
      $arr = [$res["user_id"], $res["username"]];
      return $arr;
    } else {
      return False;
    }
  } catch(PDOException $e) {
    return False;
  }
}

// Given a diverse identifier (username/ROBLOX id)
// this function returns an object describing the
// user and their orders.
// Return format:
// {
//    "id": <the player's ROBLOX id>,
//    "username": <the player's ROBLOX username>,
//    "orders": <an array of user's orders less than 100 minutes old>,
//    "history": <an array of <=30 historical order>,
// }
function reportCurrentTrades($identifier, $dbh) {
  $info = getIdFromIdentifier($identifier, $dbh);
  if(!$info) {
    return False;
  }
  $id = $info[0];
  $username = $info[1];
  try {
    $selObj = $dbh->prepare("SELECT DISTINCT quantity, price, material, type, location
                             FROM `marketOrders`
                             WHERE user_id=:identifier
                             GROUP BY material, type, location
                             ORDER BY id DESC
                             LIMIT 30");
    $selObj->bindValue(":identifier", $id);
    if(!$selObj->execute()) {
      return False;
    }
    if($selObj->rowCount() > 0) {

      try {
        $selObj2 = $dbh->prepare("SELECT quantity, price, material, type, location
                                 FROM `marketOrders`
                                 WHERE user_id=:identifier
                                 AND (SELECT max(id) FROM `marketOrders`) - id < 1000000
                                 AND material IS NOT NULL
                                 AND quantity IS NOT NULL
                                 AND price IS NOT NULL
                                 AND ts > date_sub(now(), interval 100 minute)
                                 GROUP BY material, type, location
                                 ORDER BY id DESC");
        $selObj2->bindValue(":identifier", $id);
        if(!$selObj2->execute()) {
          return False;
        }
      } catch(PDOException $e) {
        return False;
      }

      return [
        "id" => $id,
        "username" => $username,
        "orders" => $selObj2->fetchAll(),
        "history" => $selObj->fetchAll()
      ];
    } else {
      return False;
    }
  } catch(PDOException $e) {
    return False;
  }
}

// Given a Blackbox owner's Discord ID
// and their notification ID index, this method
// returns the ROBLOX ID of the corresponding 
// user.
//
// For example, if Blackbox owner id#222 has
// notifications enabled for:
//  #1.) Roblox user 555
//  #2.) Roblox user 777
//  #3.) Roblox user 999
// Then, getIdOfAuthor(222, $dbh, 2) would return 777
function getIdOfAuthor($id, $dbh, $index) {
  try {
    $s = $dbh->prepare("SELECT * FROM discordUsers WHERE discord_id = :id");
    $s->bindValue(":id", $id);
    if(!$s->execute()) {
      return False;
    }
    if($s->rowCount() > 0) {
      $ties = $s->fetchAll();
      if(count($ties) - 1 >= $index) {
        if($index < 0) {
          return False;
        }
        return $ties[$index]["roblox_id"];
      } else {
        return False;
      }
    } else {
      return False;
    }
  } catch(PDOException $e) {
    return False;
  }
}

// Internet-based functions to extend PHP functionality

if (!function_exists('str_contains')) {
  function str_contains(string $haystack, string $needle): bool
  {
      return '' === $needle || false !== strpos($haystack, $needle);
  }
}

?>

