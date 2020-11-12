<?php
    class ImageUtils {
        public static function uploadImageToPath(string $title, string $path, string $base64Image) {
            require "../init.php";
            $upload_path = "../../../uploads/$path/$title.jpg";

            file_put_contents($upload_path, base64_decode($base64Image)); // write decoded image to the filesystem (1.jpg, 2.jpg, etc.)
    
            return $db->setUserHasImage($title, true);
        }
    }

?>