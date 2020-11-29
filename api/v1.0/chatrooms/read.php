<?php
    // TODO: pass chatroom id as query param?
    // TODO: pass page number as query param?
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    // either returns 1 chatroom or many chatrooms (1 if chatroom id passed as query param)
?>