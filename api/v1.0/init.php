<?php
    // DO not put config/core.php here
    require_once("service/database.php");
    require_once("consts/constants.php");
    require_once("utils/facebook_token_utils.php");
    require_once(__DIR__ . "/../../vendor/autoload.php");
    require_once("utils/jwt_utils.php");
    require_once ("utils/converter_utils.php");
    require_once("utils/tag_utils.php");
    require_once("utils/api_utils.php");

    $db = new Database(); // connects to DB with given web server params
?>
