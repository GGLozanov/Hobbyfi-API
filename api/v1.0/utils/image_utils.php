<?php
    class ImageUtils {
        public static function uploadImageToPath(string $title, string $path,
                                                 string $base64Image, string $modelType, bool $modifyModel = true) {
            require "../init.php";
            /* @var Database $db */

            $decoded = base64_decode($base64Image);
            if($decoded == false || ($modelType != Constants::$chatrooms && $modelType != Constants::$users
                    && $modelType != Constants::$events && $modelType != Constants::$messages)) {
                return false;
            }

            $dir = __DIR__ . "/../../../uploads/$path/";
            if(!is_dir($dir)){
                // dir doesn't exist; create it
                mkdir($dir, 0755, true);
            }

            $upload_path = $dir . "$title.jpg";

            $success = file_put_contents($upload_path, $decoded); // write decoded image to the filesystem (1.jpg, 2.jpg, etc.)

            return ($modifyModel ? $db->setModelHasImage($title, true, $modelType) : true) && $success != false;
        }

        public static function deleteImageFromPath(int $title, string $path, string $modelType,
                                                    bool $isFile = false, bool $modifyModel = false) {
            require "../init.php";
            /* @var Database $db */
            $dir = __DIR__ . ($isFile ? "/../../../uploads/$path.jpg" : "/../../../uploads/$path/");
            
            if((!$isFile && !is_dir($dir)) || ($modelType != Constants::$chatrooms && $modelType != Constants::$users &&
                        $modelType != Constants::$messages
                    && $modelType != Constants::$events)) {
                return false;
            }

            if(file_exists($dir)) {
                if($isFile) {
                    $deletionSuccess = unlink($dir); // unlink file from the filesystem (1.jpg, 2.jpg, etc.)
                } else {
                    rrmdir($dir);
                    $deletionSuccess = true;
                }
            } else {
                return false;
            }

            if($modifyModel) {
                $deletionSuccess |= $db->setModelHasImage($title, false, $modelType);
            }

            return $deletionSuccess;
        }

        public static function uploadImageWithResponseReturn(int $id, string $dir, string $modelTableName) {
            if(!ImageUtils::uploadImageToPath($id, $dir,
                    $_POST[Constants::$image], $modelTableName)) {
                return Constants::$imageUploadFailed;
            }
            return Constants::$ok;
        }
    }

    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir")
                        rrmdir($dir."/".$object);
                    else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
?>