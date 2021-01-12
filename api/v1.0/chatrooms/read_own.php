<?php
    require_once("../init.php");
    /* @var $db */

    RequestUtils::performChatroomsReadRequestWithDbSource(function(int $id, int $page) use ($db) {
        return $db->getChatrooms($id, $page, true);
    });

    $db->closeConnection();
?>