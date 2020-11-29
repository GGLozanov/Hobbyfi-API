<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../models/chatroom.php";
    require "../models/tag.php";
    require "../utils/image_utils.php";
    /** @var $db */

?>