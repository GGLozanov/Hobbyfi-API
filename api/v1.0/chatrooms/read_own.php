<?php
    require_once("../init.php");
    /* @var $db */

    RequestUtils::performChatroomsReadRequestWithDbSource(function(int $id, int $page) use ($db) {
        $db->getUserChatrooms($id, $page);
    });
?>