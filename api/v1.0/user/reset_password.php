<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    // TODO: Reset passwd functionality
