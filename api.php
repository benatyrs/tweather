<?php
session_start();
require('classes/json.class.php');
require("classes/mysql.class.php");
require('classes/config.php');
require('classes/g.class.php');
$DB = new mysqldb;
$JSON = new json();
G::init();

function isValidAPIKEY($APIKey)
{
    if ($APIKey == md5($_SERVER['REMOTE_ADDR'] . $_SESSION['rand_key'] . SECRET_KEY) && $_SESSION['key_timeout'] > time()) {
        return true;
    }
    else {
        return false;
    }
}

if (isset($_GET['API_KEY']) && isset($_SESSION['rand_key'])) {
    $APIKey = $_GET['API_KEY'];
    if (isValidAPIKEY($APIKey)) {
        if (isset($_GET['debug'])) {
            echo $JSON::getDebug();
        }
        else {
            echo $JSON::getLatestTweets();
        }
    }
    else {
        echo json_encode(array("error" => "403", "response" => "You are not authorized to use this API."));
    }
}
else {
    echo json_encode(array("error" => "403", "response" => "You are not authorized to use this API."));
}