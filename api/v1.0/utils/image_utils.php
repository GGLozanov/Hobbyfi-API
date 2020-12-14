<?php
    class ImageUtils {
        public static function uploadImageToPath(string $title, string $path, string $base64Image, string $modelType) {
            require "../init.php";
            /* @var Database $db */

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

        public static function deleteImageFromPath(string $title, string $path, string $modelType, bool $modifyUser = false) {
            require "../init.php";
            /* @var Database $db */
            $dir = __DIR__ . "/../../../uploads/$path/";
            
            if(!is_dir($dir) || ($modelType != Constants::$chatrooms && $modelType != Constants::$users
                    && $modelType != Constants::$events)) {
                return false;
            }

            $upload_path = $dir . "$title.jpg";

            if(file_exists($upload_path)) {
                $deletionSuccess = unlink($upload_path); // write decoded image to the filesystem (1.jpg, 2.jpg, etc.)
            } else {
                return false;
            }

            if($modifyUser) {
                $deletionSuccess |= $db->setModelHasImage($title, false, $modelType);
            }

            return $deletionSuccess;
        }

        public static function validateBase64(string $data) {
            return base64_encode(base64_decode($data, true)) === $data;
        }
    }
?>