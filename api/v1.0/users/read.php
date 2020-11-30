<?php
    // Retrieves all the users except the one with the id passed in with the auth token
    require "../init.php";
    include_once '../config/core.php';
    require "../models/user.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $page = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$page, $_GET);

    // TODO: fetch chatroom id from user model (support Facebook id fetch as well)
    // TODO: pass page number as query param?

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($users = $db->getChatroomUsers($id, $page)) {
            APIUtils::displayAPIResult(
                array_reduce($users, function($result, User $user) {
                    $result[] = array(
                        Constants::$id=>$user->getId(),
                        Constants::$username=>$user->getName(), 
                        Constants::$description=>$user->getDescription(),
                        Constants::$email=>$user->getEmail(), 
                        Constants::$photoUrl=>$user->getHasImage() ?
                            (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . ':'
                                . $_SERVER['SERVER_PORT'] .'/Hobbyfi-API/uploads' . Constants::$userProfileImagesDir . '/' . $user->getId() . '.jpg'
                            : null);
                    return $result;
            }, array())); // mapping twice; FIXME - refactor database to return JSON responses directly instead of model classes?
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$internalServerError, 500));
        }
    }

    $db->closeConnection();
?>