<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require_once("../init.php");
    /* @var $db */

    RequestUtils::performMessagesReadRequestWithDbSource(true);

    $db->closeConnection();

