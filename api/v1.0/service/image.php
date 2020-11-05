<?php
    require "../init.php";
    require "../utils/api_utils.php";

    if(!array_key_exists('image', $_POST)) {
        APIUtils::displayAPIResult(array("response"=>$status), 400);
        return;
    }

    if(!$jwt = APIUtils::getJwtFromHeaders()) {
        return;
    }

    // TODO: move this to individual CREATE scripts for models
    // TODO: Don't have it be a separate request and have it be done in parallel with the CREATEs (user_upload, chatroom_upload), etc.
    
    if($decoded = APIUtils::validateAuthorisedRequest($jwt)) {
        $title = $decoded['userId']; // title = user's id = id in token (unique profile image identifier)
        // chatroom title = user's id + chatroom id
        // message title = user's id + chatroom id + message id + some form of encryption?
        $image = $_POST['image']; // image is received as a base64 encoded string that is decoded and put later

        // upload title and image strings to the server (received from client app)

        $upload_path = "../../uploads/$title.jpg";

        file_put_contents($upload_path, base64_decode($image)); // write decoded image to the filesystem (1.jpg, 2.jpg, etc.)

        if($db->setUserHasImage($title, true)) {
            APIUtils::displayAPIResult(array("response"=>"Image Uploaded")); // send the response back to the client for handling
        }
    }
?>
