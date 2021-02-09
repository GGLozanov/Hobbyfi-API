<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require_once("../init.php");
    /* @var $db */

    RequestUtils::performChatroomsReadRequestWithDbSource(function(int $id, int $page) use ($db) {
        return $db->getChatrooms($id, $page, true);
    });

    $db->closeConnection();
?>