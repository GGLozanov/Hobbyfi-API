<?php

use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\StorageObject;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Storage;

class ImageUtils {
        private static Storage $storage;

        public static string $defaultBucketName = "hobbyfi.appspot.com";

        public function __construct() {
            echo __CLASS__ . " can be initialized only once!";
        }

        private static function getStorage() {
            if (!isset(self::$storage)) {
                self::$storage = (new Factory)->withServiceAccount(
                    __DIR__ . '/../keys/hobbyfi-firebase-adminsdk-o1f83-e1d558ffae.json'
                )->createStorage();
            }

            return self::$storage;
        }

        public static function uploadImageToPath(string $title, string $bucketPath,
                                                 string $base64Image, string $modelType, bool $modifyModel = true) {
            require "../init.php";
            /* @var Database $db */

            if($modelType != Constants::$chatrooms && $modelType != Constants::$users
                    && $modelType != Constants::$events && $modelType != Constants::$messages) {
                return false;
            }

            $response = ImageUtils::uploadObject($bucketPath, $title, $base64Image);
            $success = true;
            if($modifyModel) {
                $success = $db->setModelHasImage($title, true, $modelType);
            }

            return $response && $success ? $response : false;
        }

        public static function deleteImageFromPath(int $title, string $dir, string $modelType, bool $deleteFolder = false,
                                                   bool $modifyModel = false) {
            require "../init.php";
            /* @var Database $db */

            if($modelType != Constants::$chatrooms && $modelType != Constants::$users &&
                        $modelType != Constants::$messages
                    && $modelType != Constants::$events) {
                return false;
            }

            if(!($bucket = ImageUtils::getDefaultBucket())) {
                return null;
            }

            try {
                if($deleteFolder) {
                    $objects = $bucket->objects(["prefix" => $dir]); // flat file system => returns all objects in this dir folder
                    // TODO: Should be in a separate thread but Thread API sucks in terms of importing & Windows support; may work on hosted image
                    foreach ($objects as $object) {
                        $object->delete();
                    }
                } else if(($object = $bucket->object($dir . $title . ".jpg"))) {
                    $object->delete();
                } else {
                    return false;
                }
            } catch(NotFoundException $ex) {
                return null;
            }

            if($modifyModel) {
                return $db->setModelHasImage($title, false, $modelType);
            }

            return true;
        }

        public static function uploadImageWithResponseReturn(int $id, string $dir, string $modelTableName) {
            if(!ImageUtils::uploadImageToPath($id, $dir,
                    $_POST[Constants::$image], $modelTableName)) {
                return Constants::$imageUploadFailed;
            }
            return ImageUtils::getPublicContentDownloadUrl($dir, $id);
        }

        public static function getBucketLocationForChatroom(?int $chatroomId) {
            return $chatroomId != null ? "chatroom_" . $chatroomId .  "/" : null;
        }

        public static function getBucketLocationForChatroomMessage(?int $chatroomId) {
            return $chatroomId != null ? "chatroom_" . $chatroomId . "/messages/" : null;
        }

        public static function getBucketLocationForChatroomEvent() {
            return "events/";
        }
        
        public static function getBucketLocationForUser() {
            return "users/";
        }

        private static function uploadObject(string $dir, string $title, $data) {
            if(!($bucket = ImageUtils::getDefaultBucket())) {
                return null;
            }

            try {
                // fopen('data://text/plain;base64,' . $data,'r')
                return $bucket->upload(base64_decode($data), [
                    'name' => $dir . $title . ".jpg",
                    'metadata' => [
                        'metadata' => [
                            'firebaseStorageDownloadTokens' => uniqid()
                        ]
                    ]
                ]);
            } catch(InvalidArgumentException $e) {
                return false;
            } catch(\Google\Cloud\Core\Exception\BadRequestException $e) {
                return null;
            }
        }

        private static function getDefaultBucket() {
            $client = ImageUtils::getStorage()->getStorageClient();
            $bucket = $client->bucket(ImageUtils::$defaultBucketName);

            if(!$bucket->exists()) {
                try {
                    return $client->createBucket(ImageUtils::$defaultBucketName);
                } catch (\Google\Cloud\Core\Exception\GoogleException $e) {
                    return null;
                }
            }
            return $bucket;
        }
        
        public static function getObjectSignedDownloadUrl(string $dir, ?string $title) {
            try {
                if($title == null || !($bucket = ImageUtils::getDefaultBucket()) ||
                    !($object = $bucket->object($dir . $title . ".jpg")) || !$object->exists()) {
                    return null;
                }
            } catch(NotFoundException $ex) {
                return null;
            }

            // FIXME: Future security fix w/ Firebase Auth
            return $object->signedUrl(new \Google\Cloud\Core\Timestamp(new DateTime('+1 week')), [
                'predefinedAcl' => 'publicRead',
                'version' => 'v4'
            ]);
        }

        // TODO: Future interoperability of such a URL w/ Firebase Storage Security Rules
        public static function getPublicContentDownloadUrl(string $dir, string $title) {
            return "https://firebasestorage.googleapis.com/v0/b/" . ImageUtils::$defaultBucketName . "/o/" .
                urlencode($dir . $title . ".jpg") . "?alt=media";
        }
    }

//    class DeleteObjectsThread extends Thread {
//        private \Google\Cloud\Storage\ObjectIterator $objects;
//
//        public function __construct(\Google\Cloud\Storage\ObjectIterator $objects) {
//            $this->objects = $objects;
//        }
//
//        public function run() {
//            foreach($this->objects as $object) {
//                $object->delete();
//            }
//        }
//    }
?>