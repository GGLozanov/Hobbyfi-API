<?php
    require "../init.php";
    require "../config/core.php";

    /* @var $db */
    /* @var $fbAppSecret */

    header('Content-Type: application/json');

    $signed_request = $_POST['signed_request'];
    $data = parse_signed_request($signed_request);
    $user_id = $data['user_id'];

    // Start data deletion

    $status_url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '/Hobbyfi-API/api/v1.0/fb/fb_view_callback'; // URL to track the deletion
    $confirmation_code = '420'; // unique code for the deletion request

    $db->deleteUser($user_id);

    $data = array(
        'url' => $status_url,
        'confirmation_code' => $confirmation_code
    );
    echo json_encode($data);

    function parse_signed_request($signed_request) {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);
        
        // decode the data
        $sig = base64_url_decode($encoded_sig);
        $data = json_decode(base64_url_decode($payload), true);

        // confirm the signature
        $expected_sig = hash_hmac('sha256', $payload, $fbAppSecret, $raw = true);
        if ($sig !== $expected_sig) {
            error_log('Bad Signed JSON signature!');
            return null;
        }

        return $data;
    }

    function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }
?>
