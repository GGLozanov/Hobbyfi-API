<?php
    require_once("../init.php");
    /* @var $db */

    RequestUtils::performChatroomsReadRequestWithDbSource(function(int $page) use ($db) {
        return $db->getChatrooms($page);
    });

    $db->closeConnection();
?>