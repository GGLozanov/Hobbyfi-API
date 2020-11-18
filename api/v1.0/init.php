<?php
    require 'service/database.php';
    require 'consts/constants.php';
    require "utils/facebook_token_utils.php";
    require "../../vendor/autoload.php";
    require "utils/jwt_utils.php";
    require "utils/tag_utils.php";
    require "utils/api_utils.php";

    $db = new Database(); // connects to DB with given web server params
?>
