<?php
// require the class
require_once 'foursquare.php';

// specify your credentials
$clientID = 'your client ID';
$clientSecret = 'your client secret';
$callbackURL = 'your callback url';

// create new foursquare instance
$fourSquare = new foursquare($clientID, $clientSecret, $callbackURL); 

// start the authenticationprocess
if(!isset($_GET['code'])) $fourSquare->authenticate();

// fetch an accesstoken if we already authed ourselves
else $access_token = $fourSquare->getToken($_GET['code']);

// set the access token in the class
$fourSquare->setAccessToken($access_token);

/*
 * At this point you completed the authenticationprocess and received
 * an accesstoken. You can save this to a database or sessionvar for
 * later use. 
 */

// let's get the mayorships
$mayorships = $fourSquare->getMayorshipsFromUser();

/*
 * That's all. 
 * 
 * The output is an array of objects.
 */

// show output of the mayorships.
var_dump($mayorships);
?>