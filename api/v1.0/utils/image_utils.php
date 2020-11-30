<?php
    class ImageUtils {
        public static function uploadImageToPath(string $title, string $path, string $base64Image, string $modelType) {
            require "../init.php";
            /* @var $db */

            $decoded = base64_decode($base64Image);
            if($decoded == false || ($modelType != Constants::$chatrooms && $modelType != Constants::$users
                    && $modelType != Constants::$events)) {
                return false;
            }

            $dir = __DIR__ . "/../../../uploads/$path/";
            if(!is_dir($dir)){
                // dir doesn't exist; create it
                mkdir($dir, 0755, true);
            }

            $upload_path = $dir . "$title.jpg";

            file_put_contents($upload_path, $decoded); // write decoded image to the filesystem (1.jpg, 2.jpg, etc.)

            return $db->setModelHasImage($title, true, $modelType);
        }
    }

?>