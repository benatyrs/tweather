<?php
require("classes/config.php");
require("classes/twitterapi.class.php");
require("classes/json.class.php");
require("classes/mysql.class.php");
require("classes/g.class.php");

$Settings = array(
    'oauth_access_token' => oauth_access_token,
    'oauth_access_token_secret' => oauth_access_token_secret,
    'consumer_key' => oauth_consumer_key,
    'consumer_secret' => oauth_consumer_secret
);

$Twitter = new twitter;
$JSON = new json;
$DB = new mysqldb;

G::init();

$QueryStartTime = microtime(true);
$Result = Twitter::Search($Settings);
$Tweets = json::parseTweets($Result, api_google);
$QueryEndTime = microtime(true);

$ScriptExecutionTime = ($QueryEndTime - $QueryStartTime) * 1000;
JSON::submitDebug(Twitter::$FoundTweets,
    Twitter::$TrashedTweets,
    JSON::$GeoTweets,
    JSON::$LocationTweets,
    JSON::$LocationValidTweets,
    JSON::$InUKTweets,
    $DB::$Queries,
    $DB::$Time,
    $ScriptExecutionTime,
    memory_get_peak_usage());