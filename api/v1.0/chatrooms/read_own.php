<?php
    require_once("../init.php");
    /* @var $db */

    RequestUtils::performChatroomsReadRequestWithDbSource(null, function(int $id, int $page) use ($db) {
        return $db->getUserChatrooms($id, $page);
    });

    $db->closeConnection();
?>