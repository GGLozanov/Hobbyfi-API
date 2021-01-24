<?php
    include_once "../consts/constants.php";
    require_once("../fcm/fcm.php");

    class Database {
        private string $host = "127.0.0.1"; // to be changed if hosted on server
        private string $user_name = "root";
        private string $user_password = "";
        private string $db_name = "hobbyfi_db";
        private mysqli $connection;
        private FCM $fcm;

        function __construct() {
            $this->connection = mysqli_connect(
                $this->host,
                 $this->user_name,
                 $this->user_password,
                  $this->db_name, "3308"
            );
            $this->fcm = new FCM();
        }

        public function closeConnection() {
            mysqli_close($this->connection);
        }

        public function createUser(User $user, ?string $password) {
            $this->connection->begin_transaction();
            $stmt = $this->connection->prepare(
                "INSERT INTO users(id, email, username, password, description, has_image) 
            VALUES(?, ?, ?, ?, ?, ?)");

            // bind_param accepts only references... bruh...
            $user->escapeStringProperties($this->connection);
            $id = $user->getId();
            $email = $user->getEmail();
            $name = $user->getName();
            $description = $user->getDescription();
            $hasImage = $user->getHasImage();

            $stmt->bind_param("issssi",
                $id,
                $email,
                $name,
                $password,
                $description,
                $hasImage);

            $insertSuccess = $stmt->execute();
            $userId = mysqli_insert_id($this->connection);
            $tagsInsertSuccess = $this->insertTagModel($user);

            $userCreateSuccess = $insertSuccess && $tagsInsertSuccess && $this->insertUserChatroomIds($user);

            $this->finishTransactionOnCond($userCreateSuccess);

            return $userCreateSuccess ? $userId : null;
        }

        public function userExistsOrPasswordTaken(string $username, $password) { // user exists if username or password are taken
            $stmt = $this->connection->prepare("SELECT username FROM users WHERE username = ? OR password = ?");
            $username = mysqli_escape_string($this->connection, $username);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();

            return mysqli_num_rows($stmt->get_result()) > 0; // if more than one row found => user exists
        }

        public function userExists(string $id) { // user exists if username or password are taken
            $result = $this->executeSingleIdParamStatement($id, "SELECT id FROM users WHERE id = ?")
                ->get_result();

            return mysqli_num_rows($result) > 0 ? "true" : "false"; // if more than one row found => user exists
        }

        public function validateUser(string $email, string $password) {
            $stmt = $this->connection->prepare("SELECT id, password FROM users WHERE email = ?"); // get associated user by email
            $email = mysqli_real_escape_string($this->connection, $email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0 && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC))) {
                $filteredRows = array_filter($rows, function (array $row) use ($password) {
                    return password_verify($password, $row[Constants::$password]);
                });

                if(count($filteredRows) && $row = $filteredRows[0]) {
                    return $row[Constants::$id];
                } // if more than one row found AND the passwords match => auth'd user => return id for token
            }

            return null; // else return nothing and mark user as unauth'd
        }

        public function getUser(int $id) { // user already auth'd at this point due to token => get user by id
            $user_result = $this->executeSingleIdParamStatement($id, "SELECT
                us.description, us.username, us.email, us.has_image,
                us_tags.tag_name, tgs.colour, tgs.is_from_facebook, usr_chrms.chatroom_id
                FROM users us
                LEFT JOIN user_chatrooms usr_chrms ON usr_chrms.user_id = us.id
                LEFT JOIN user_tags us_tags ON us.id = us_tags.user_id
                LEFT JOIN tags tgs ON tgs.name LIKE us_tags.tag_name
                WHERE id = ?"
            )->get_result();

            if(mysqli_num_rows($user_result) > 0) {
                $rows = mysqli_fetch_all($user_result, MYSQLI_ASSOC); // fetch the resulting rows in the form of a map (associative array)

                $tags = null;
                $chatroomIds = null;
                $this->extractTagsAndUniqueNumericArrayFromJoinQuery($rows, Constants::$chatroomId, $tags, $chatroomIds);

                return new User($id,
                    $rows[0][Constants::$email], $rows[0][Constants::$username],
                    $rows[0][Constants::$description], $rows[0][Constants::$hasImage],
                    $chatroomIds,
                    $tags
                );
            }

            return null;
        }

        public function updateUser(User $user, ?string $password, ?int $leaveChatroomId = null, ?int $joinChatroomId = null) {
            $this->connection->begin_transaction();
            $userUpdateSuccess = true;
            // FIXME: Pass in this as argument from edit.php
            $shouldUpdateUser = !$user->isUpdateFormEmpty() || $password != null;
            $shouldUpdateChatroomId = $leaveChatroomId != null || $joinChatroomId != null;

            if($shouldUpdateUser) {
                $user->escapeStringProperties($this->connection);
                mysqli_query($this->connection, $user->getUpdateQuery($password));
                // FIXME: Code dup
                $userUpdateSuccess = mysqli_affected_rows($this->connection) > 0;
            }

            if($shouldUpdateChatroomId) {
                $user = $this->getUser($user->getId());

                if($joinChatroomId != null) { // if user has updated chatroom id by joining
                    // send notification for joining
                    $this->insertUserChatroomId($user->getId(), $joinChatroomId);
                    $userUpdateSuccess = $this->sendUserChatroomNotification($joinChatroomId, $user,
                        Constants::$JOIN_USER_TYPE, Constants::timelineUserJoinMessage($user->getName()));
                } else if($leaveChatroomId != null) { // else get if they are currently in chatroom (or previous chatroom for user)
                    // send notification for edit/leave otherwise
                    $this->deleteUserChatroomId($user->getId(), $leaveChatroomId);
                    $userUpdateSuccess = $this->sendUserChatroomNotification($leaveChatroomId, $user,
                        Constants::$LEAVE_USER_TYPE, Constants::timelineUserLeaveMessage($user->getName()));
                }
            } else if($chatroomIds = $this->getUserChatroomsId($user->getId())) {
                $this->sendBatchedNotificationToChatroomOnCond($userUpdateSuccess,
                    $chatroomIds,
                    Constants::$EDIT_USER_TYPE,
                    $user
                );
            }

            $tagsUpdateSuccess = true;
            if(($tags = $user->getTags())) { // somewhat unnecessary check given the method..
                $tagsUpdateSuccess = $this->updateModelTags(Constants::$userTagsTable,
                    Constants::$userId, $user->getId(), $tags, true);
            } else if(!$shouldUpdateUser && !$shouldUpdateChatroomId) {
                $userUpdateSuccess = false;
                $tagsUpdateSuccess = false;
            }

            $updateSuccess = $userUpdateSuccess && $tagsUpdateSuccess;

            $this->finishTransactionOnCond($updateSuccess);

            return $updateSuccess;
        }

        public function deleteUser(int $id) {
            include_once "../utils/image_utils.php";
            $this->connection->begin_transaction();

            if($chatroomIds = $this->getUserChatroomsId($id)) {
                if($ownerId = $this->getOwnerChatroomId($id)) {
                    $this->fcm->sendMessageToChatroom(
                        $ownerId,
                        Constants::$DELETE_CHATROOM_TYPE,
                        new Chatroom($ownerId)
                    );
                } else {
                    $user = $this->getUser($id);
                    $this->sendUserChatroomBatchedNotification($chatroomIds,
                        $user,
                        Constants::$LEAVE_USER_TYPE,
                        Constants::timelineUserLeaveMessage($user->getName())
                    );
                }
            }

            $stmt = $this->executeSingleIdParamStatement($id, "DELETE FROM users WHERE id = ?");

            ImageUtils::deleteImageFromPath($id, Constants::$userProfileImagesDir, Constants::$users, true);
            // FCM if user in chatroom => send notification

            $deleteSuccess = $stmt->affected_rows > 0;
            $this->finishTransactionOnCond($deleteSuccess);
            return $deleteSuccess;
        }

        // gets all users with any id BUT this one;
        public function getChatroomUsers(int $userId, int $chatroomId) { // multiplier starts from 1
            require_once("../utils/model_utils.php");

            $this->connection->begin_transaction();

            if(!($chatroomIds = $this->getUserChatroomsId($userId)) || !in_array($chatroomId, $chatroomIds)) {
                return false;
            }

            $stmt = $this->connection->prepare(
                "SELECT us.id, us.description, us.username, us.email, us.has_image, 
                us_tags.tag_name, tgs.colour, tgs.is_from_facebook, us_chrms.chatroom_id FROM users us
                INNER JOIN user_chatrooms us_chrms ON us_chrms.user_id = us.id
                LEFT JOIN user_tags us_tags ON us_tags.user_id = us.id
                LEFT JOIN tags tgs ON tgs.name LIKE us_tags.tag_name
                WHERE id != ? AND us_chrms.chatroom_id = ?");
            $stmt->bind_param("ii", $userId, $chatroomId);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

                $tags = null;
                $chatroomIds = null;
                $this->extractTagsAndUniqueNumericArrayFromJoinQuery($rows, Constants::$chatroomId,
                    $tags, $chatroomIds, true, true);

                return $this->extractUsersFromJoinQuery($rows, $tags, $chatroomIds);
            }

            $this->connection->rollback();
            return (empty($stmt->error)) ? array() : null;
        }

        public function createChatroom(Chatroom $chatroom) {
            $this->connection->begin_transaction();

            if($this->getOwnerChatroomId($chatroom->getOwnerId())) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->connection->prepare("INSERT INTO chatrooms(id, name, description, has_image, owner_id) 
                VALUES(?, ?, ?, ?, ?)");

            // still need to destruct this class and allocate more variables for this goddamn bind_param() method...
            $chatroom->escapeStringProperties($this->connection);
            $id = $chatroom->getId();
            $name = $chatroom->getName();
            $description = $chatroom->getDescription();
            $hasImage = $chatroom->getHasImage();
            $ownerId = $chatroom->getOwnerId();

            $stmt->bind_param("issii",
                $id,
                $name,
                $description,
                $hasImage,
                $ownerId
            );

            $insertSuccess = $stmt->execute();
            $chatroomId = mysqli_insert_id($this->connection);
            $tagsInsertSuccess = $this->insertTagModel($chatroom);
            $chatroomCreateSuccess = $insertSuccess && $tagsInsertSuccess;

            if($chatroomCreateSuccess) {
                $userUpdateStmt = $this->connection->prepare("INSERT INTO user_chatrooms VALUES(?, ?)");
                $userUpdateStmt->bind_param("ii", $ownerId, $chatroomId);

                $chatroomCreateSuccess |= $userUpdateStmt->execute();
                $this->finishTransactionOnCond($chatroomCreateSuccess);
                return $chatroomCreateSuccess ? $chatroomId : null; // FIXME: Expand upon errors and have them be more concise.
            }
            $this->connection->rollback();
            return null;
        }

        public function updateChatroom(Chatroom $chatroom) {
            // Owner evaluation not needed for now due to being exposed in chatroom edit.php
            // which is why chatroomId is passed in
            $this->connection->begin_transaction();

            $chatroomUpdateSuccess = true;
            $shouldUpdateChatroom = !$chatroom->isUpdateFormEmpty();
            if($shouldUpdateChatroom) {
                $chatroom->escapeStringProperties($this->connection);
                mysqli_query($this->connection, $chatroom->getUpdateQuery());

                $chatroomUpdateSuccess = mysqli_affected_rows($this->connection);
            }

            $tagsUpdateSuccess = true;
            if(($tags = $chatroom->getTags())) { // somewhat unnecessary check given the method..
                $tagsUpdateSuccess = $this->updateModelTags(
                    Constants::$chatroomTagsTable, Constants::$chatroomId,
                    $chatroom->getId(), $tags,
                    true
                );
            } else if(!$shouldUpdateChatroom) {
                $chatroomUpdateSuccess = false;
                $tagsUpdateSuccess = false;
            }

            // fixme: small code dup
            $updateSuccess = $chatroomUpdateSuccess > 0 && $tagsUpdateSuccess;
            $this->sendNotificationToChatroomOnCond($updateSuccess,
                $chatroom->getId(),
                Constants::$EDIT_CHATROOM_TYPE,
                $chatroom
            );

            $this->finishTransactionOnCond($updateSuccess);
            return $updateSuccess;
        }

        public function deleteChatroom(int $ownerId) {
            include_once "../utils/image_utils.php";

            $this->connection->begin_transaction();
            if(!($chatroomId = $this->getOwnerChatroomId($ownerId))) {
                return false;
            }

            $stmt = $this->executeSingleIdParamStatement($chatroomId, "DELETE FROM chatrooms WHERE id = ?");

            ImageUtils::deleteImageFromPath($chatroomId, Constants::chatroomImagesDir($chatroomId), Constants::$chatrooms);

            $deleteSuccess = $stmt->affected_rows > 0;
            // FCM - delete chatroom
            $this->sendNotificationToChatroomOnCond($deleteSuccess,
                $chatroomId,
                Constants::$DELETE_CHATROOM_TYPE,
                new Chatroom($chatroomId)
            );
            $this->finishTransactionOnCond($deleteSuccess);
            return $deleteSuccess;
        }

        public function getChatroom(int $userId, int $chatroomId) {
            $stmt = $this->connection->prepare("SELECT `ch`.`id`, `ch`.`name`, `ch`.`description`, 
                    `ch`.`has_image`, `ch`.`owner_id`,
                    `ch_tags`.`tag_name`, `tgs`.`colour`, `tgs`.`is_from_facebook`, `evnts`.`id` AS `event_id`
                FROM chatrooms `ch`
                LEFT JOIN user_chatrooms usr_chrms ON usr_chrms.chatroom_id = ch.id AND usr_chrms.user_id = ?
                LEFT JOIN events evnts ON evnts.chatroom_id = ch.id 
                LEFT JOIN chatroom_tags `ch_tags` ON `ch`.`id` = `ch_tags`.`chatroom_id` 
                LEFT JOIN tags `tgs` ON `ch_tags`.`tag_name` LIKE `tgs`.`name`
                WHERE `ch`.`id` = ?");

            $stmt->bind_param("ii", $userId, $chatroomId);

            $stmt->execute();
            $result = $stmt->get_result();

            if($result != null && mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

                $tags = null;
                $eventIds = null;
                $this->extractTagsAndUniqueNumericArrayFromJoinQuery($rows, Constants::$eventIds, $tags, $eventIds);

                return new Chatroom(
                    $rows[0][Constants::$id],
                    $rows[0][Constants::$name],
                    $rows[0][Constants::$description],
                    $rows[0][Constants::$hasImage],
                    $rows[0][Constants::$ownerId],
                    $eventIds,
                    $tags
                );
            }
        }

        public function getChatrooms(int $userId, int $multiplier = 1, bool $fetchOwnChatrooms = false) {
            if($fetchOwnChatrooms && !($chatroomIds = $this->getUserChatroomsId($userId))) {
                return false;
            }

            $multiplier = 5*($multiplier - 1);

            $stmt = $this->connection->prepare(
                "SELECT `s`.`id`, `s`.`name`, `s`.`description`, 
                    `s`.`has_image`, `s`.`owner_id`, 
                    `ch_tags`.`tag_name`, `tgs`.`is_from_facebook`, 
                    `tgs`.`colour`, `evnts`.`id` AS `event_id` FROM 
                        (SELECT `ch`.`id`, `ch`.`name`, 
                        `ch`.`description`, `ch`.`has_image`, `ch`.`owner_id`
                         FROM chatrooms `ch` 
                         INNER JOIN user_chatrooms usr_chrms ON usr_chrms.chatroom_id = `ch`.id AND ch.id ".
                            ($fetchOwnChatrooms ? "" : "NOT ") ."IN (SELECT chatroom_id FROM user_chatrooms WHERE user_id = ?)
                         GROUP BY `ch`.`id`
                         LIMIT 5 OFFSET ?) as `s`
                    LEFT JOIN events evnts ON evnts.chatroom_id = s.id
                    LEFT JOIN chatroom_tags `ch_tags` ON `s`.`id` = `ch_tags`.`chatroom_id` 
                    LEFT JOIN tags `tgs` ON `ch_tags`.`tag_name` LIKE `tgs`.`name`"
            );
            $stmt->bind_param("ii", $userId, $multiplier);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                return $this->parseChatroomsOnFinishedTransaction($result);
            }

            $this->connection->rollback();
            return (empty($stmt->error)) ? array() : null; // want to show empty array for pagination end limit purposes
        }

        public function getChatroomMessages(int $userId, int $chatroomId, int $multiplier = 1) {
            $this->connection->begin_transaction();
            if(!($chatroomIds = $this->getUserChatroomsId($userId))) {
                $this->connection->rollback();
                return false;
            }
            $multiplier = 20*($multiplier - 1);
            $stmt = $this->connection->prepare(
                "SELECT * FROM messages WHERE chatroom_sent_id = ?
                    ORDER BY create_time DESC
                    LIMIT 20 OFFSET ?"
            );

            $stmt->bind_param("ii", $chatroomId, $multiplier);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $this->connection->commit();
                return array_map(function($row) {
                    return new Message(
                        $row[Constants::$id],
                        $row[Constants::$message],
                        $row[Constants::$createTime],
                        $row[Constants::$chatroomSentId],
                        $row[Constants::$userSentId],
                    );
                }, $rows);
            }

            $this->connection->rollback();
            return (empty($stmt->error)) ? array() : null;
        }

        public function createChatroomMessage(Message $message, bool $shouldFinishTransaction = true) {
            $this->connection->begin_transaction();

            if($message->getUserSentId() != null && (!($chatroomIds = $this->getUserChatroomsId($message->getUserSentId())) ||
                    !in_array($message->getChatroomSentId(), $chatroomIds))) {
                $this->connection->rollback();
                return false; // users should only send messages in a their chatrooms from here (theirs) and nothing else
            }

            $stmt = $this->connection->prepare("INSERT INTO messages(id, message, create_time, chatroom_sent_id, user_sent_id) 
                VALUES(?, ?, NOW(), ?, ?)");

            $message->escapeStringProperties($this->connection);
            $id = $message->getId();
            $msg = $message->getMessage();
            $userSentId = $message->getUserSentId();
            $chatroomId = $message->getChatroomSentId();

            $stmt->bind_param("isii", $id, $msg, $chatroomId, $userSentId);

            $insertSuccess = $stmt->execute();
            $messageId = mysqli_insert_id($this->connection);
            $message->setId($messageId);

            // kinda dumb but workaround for now to get create time needed for create response
            $message->setCreateTime($this->executeSingleIdParamStatement($messageId,
                "SELECT create_time FROM messages WHERE id = ?")
                ->get_result()
                ->fetch_assoc()[Constants::$createTime]);

            if($shouldFinishTransaction) {
                $this->sendNotificationToChatroomOnCond($insertSuccess,
                    $chatroomId,
                    Constants::$CREATE_MESSAGE_TYPE,
                    $message);
                $this->finishTransactionOnCond($insertSuccess);
            } else {
                // for now this means message img insert => replace message photo url with right one with latest insert id
                // FOR THIS TABLE (very bruh but such is life...)
                $stmt = $this->connection->prepare("UPDATE messages SET message = ? WHERE id = ?");
                $messagePhotoUrl = Constants::getPhotoUrlForDir(Constants::chatroomMessageImagesDir($chatroomId)
                    . "/" . $messageId . ".jpg");
                $stmt->bind_param("si", $messagePhotoUrl, $messageId);

                $insertSuccess = $stmt->execute();
            }

            return $insertSuccess ? $message : null;
        }

        // very bruh method
        public function createChatroomImageMessage(Message $message) {
            require_once("../utils/image_utils.php");

            $base64Image = $message->getMessage();
            $chatroomId = $message->getChatroomSentId();

            if(!($message = $this->createChatroomMessage($message, false))) {
                $this->connection->rollback();
                return null;
            }

            if(ImageUtils::uploadImageToPath($message->getId(), Constants::chatroomMessageImagesDir($chatroomId),
                    $base64Image, Constants::$messages, false)) {
                $this->connection->commit();
                // no need for helper notification method here because this is in a "success create" context, per se
                $this->fcm->sendMessageToChatroom(
                    $chatroomId,
                    Constants::$CREATE_MESSAGE_TYPE,
                    $message
                );
                return $message;
            }

            $this->connection->rollback();
            return null;
        }

        public function deleteChatroomMessage(int $userId, int $messageId) {
            require_once("../utils/image_utils.php");

            $this->connection->begin_transaction();
            // assert message owner OR chatroom owner
            if((!($messageInfo = $this->getMessageOwnerIdAndChatroomId($messageId)) ||
                    $userId != (is_null($messageInfo[Constants::$userSentId]) ? $userId : $messageInfo[Constants::$userSentId]))
                        && !($chatroomId = $this->getOwnerChatroomId($userId))) {
                $this->connection->rollback();
                return null;
            }

            $stmt = $this->executeSingleIdParamStatement($messageId, "DELETE FROM messages WHERE id = ?");

            ImageUtils::deleteImageFromPath(
                $messageId,
                Constants::chatroomMessageImagesDir($messageInfo[Constants::$chatroomSentId]),
                Constants::$message
            );

            $affectedRows = $stmt->affected_rows > 0;
            $this->sendNotificationToChatroomOnCond($affectedRows,
                $messageInfo[Constants::$chatroomSentId],
                Constants::$DELETE_MESSAGE_TYPE,
                new Message($messageId)
            );
            $this->finishTransactionOnCond($affectedRows);
            return $affectedRows;
        }

        public function updateChatroomMessage(Message $message) {
            // assert message owner & correct chatroom
            $this->connection->begin_transaction();
            $userSentMessageId = $message->getUserSentId();
            if(!($messageInfo = $this->getMessageOwnerIdAndChatroomId($message->getId()))
                    || ((array_key_exists(Constants::$userSentId, $messageInfo) ?
                            $messageInfo[Constants::$userSentId] : -1) != $userSentMessageId)) {
                $this->connection->rollback();
                return false;
            }

            $message->escapeStringProperties($this->connection);
            $updateSuccess = $this->connection->query($message->getUpdateQuery()); // TODO: Handle no update option in edit.php
            $this->sendNotificationToChatroomOnCond($updateSuccess,
                $messageInfo[Constants::$chatroomSentId],
                Constants::$EDIT_MESSAGE_TYPE,
                $message
            );
            $this->finishTransactionOnCond($updateSuccess);
            return $updateSuccess;
        }

        public function getChatroomEvents(int $userId) {
            $this->connection->begin_transaction();

            $result = $this->executeSingleIdParamStatement($userId, "SELECT evnt.id, evnt.name, 
                    evnt.description, evnt.latitude, evnt.longitude, evnt.has_image, evnt.start_date, evnt.date, evnt.chatroom_id
                 FROM events evnt
                INNER JOIN chatrooms chrms ON chrms.id = evnt.chatroom_id
                INNER JOIN user_chatrooms usr_chrms ON usr_chrms.chatroom_id = chrms.id 
                    AND usr_chrms.user_id = ?")->get_result();

            if($result && mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $this->connection->commit();

                return array_map(function($row) {
                    return new Event(
                        $row[Constants::$id],
                        $row[Constants::$name],
                        $row[Constants::$description],
                        $row[Constants::$hasImage],
                        $row[Constants::$startDate],
                        $row[Constants::$date],
                        $row[Constants::$lat],
                        $row[Constants::$long],
                        $row[Constants::$chatroomId]
                    );
                }, $rows);
            }

            $this->connection->rollback();
            return (empty($stmt->error)) ? array() : null;
        }

        // chatroom id is sent through query param
        public function createChatroomEvent(int $ownerId, Event $event) {
            $this->connection->begin_transaction();

            if(!($chatroomId = $this->getOwnerChatroomId($ownerId))) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->connection->prepare("INSERT INTO 
                events(id, name, description, date, has_image, latitude, longitude, chatroom_id) 
                    VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
            $event->escapeStringProperties($this->connection);
            $id = $event->getId();
            $name = $event->getName();
            $description = $event->getDescription();
            $date = $event->getDate();
            $hasImage = $event->getHasImage();
            $latitude = $event->getLat();
            $longitude = $event->getLong();

            $stmt->bind_param("isssiddi", $id, $name, $description,
                $date, $hasImage, $latitude, $longitude, $chatroomId);

            $eventCreateSuccess = $stmt->execute();

            if(!$eventCreateSuccess) {
                $this->connection->rollback();
                return null;
            }

            $eventId = mysqli_insert_id($this->connection);
            $event->setId($eventId);
            $event->setStartDate($this->executeSingleIdParamStatement($event->getId(),
                "SELECT start_date FROM events WHERE id = ?"
                )->get_result()->fetch_assoc()[Constants::$startDate]);
            $event->setChatroomId($chatroomId);

            $this->sendNotificationToChatroomOnCond($eventCreateSuccess,
                $chatroomId,
                Constants::$CREATE_EVENT_TYPE,
                $event
            );

            $this->finishTransactionOnCond($eventCreateSuccess);
            return $eventId != null && $eventCreateSuccess ? $event : null;
        }

        public function deleteChatroomEvent(int $ownerId, int $eventId) {
            $this->connection->begin_transaction();

            if(!($chatroomId = $this->getOwnerChatroomId($ownerId))) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->executeSingleIdParamStatement($eventId, "DELETE FROM events WHERE id = ?");

            $deleteSuccess = $stmt->affected_rows > 0;

            if(!$deleteSuccess) {
                $this->connection->rollback();
                return null;
            }

            $this->sendNotificationToChatroomOnCond($deleteSuccess,
                $chatroomId,
                Constants::$DELETE_EVENT_TYPE,
                new Event($eventId)
            );
            $this->finishTransactionOnCond($deleteSuccess);

            return $deleteSuccess ? $deleteSuccess : null;
        }

        public function deleteOldChatroomEvents(int $ownerId) {
            $this->connection->begin_transaction();

            if(!($chatroomId = $this->getOwnerChatroomId($ownerId))) {
                $this->connection->rollback();
                return false;
            }

            $preDeleteResult = $this->executeSingleIdParamStatement($chatroomId,
                "SELECT id FROM events WHERE date < NOW() AND chatroom_id = ?")->get_result();
            if($preDeleteResult && ($rows = mysqli_fetch_all($preDeleteResult, MYSQLI_ASSOC))
                    && mysqli_num_rows($preDeleteResult) > 0) {
                $eventIds = array_filter(array_map(function($row) {
                    return array_key_exists(Constants::$id, $row) ?
                        $row[Constants::$id] : false;
                }, $rows));
            } else {
                $this->connection->rollback();
                return null;
            }

            $stmt = $this->executeSingleIdParamStatement($chatroomId,
                "DELETE FROM events WHERE date < NOW() AND chatroom_id = ?");

            $deleteSuccess = $stmt->affected_rows > 0;

            if(!$deleteSuccess) {
                $this->connection->rollback();
                return null;
            }

            $this->sendNotificationToChatroomOnCond($deleteSuccess,
                $chatroomId,
                Constants::$DELETE_EVENT_BATCH_TYPE,
                new Chatroom(null, null, null, null, null, $eventIds, null) // uh... don't ask
            );
            $this->finishTransactionOnCond($deleteSuccess);

            return $deleteSuccess ? $eventIds : null;
        }

        public function updateChatroomEvent(int $ownerId, Event $event) {
            $this->connection->begin_transaction();
            if(!($chatroomId = $this->getOwnerChatroomId($ownerId)) ||
                    ($chatroomId !=
                        $this->getOneColumnValueFromSingleRow(
                            $this->executeSingleIdParamStatement($event->getId(), "SELECT chatroom_id FROM events WHERE id = ?")->get_result(),
                            Constants::$chatroomId)
                    )
            ) {
                $this->connection->rollback();
                return false;
            }

            $event->escapeStringProperties($this->connection);
            $this->connection->query($event->getUpdateQuery());
            $eventUpdateSuccess = mysqli_affected_rows($this->connection) > 0;

            $this->sendNotificationToChatroomOnCond($eventUpdateSuccess,
                $chatroomId,
                Constants::$EDIT_EVENT_TYPE,
                $event
            );

            $this->finishTransactionOnCond($eventUpdateSuccess);
            return $eventUpdateSuccess;
        }

        public function updateModelTags(string $table, string $modelColumn, int $modelId, ?array $tags, $shouldDeletePrior = false) {
            // INSERT tag if not in tags table => skip otherwise
            // REPLACE query - all user tags
            if(!$tags) {
                return false;
            }

            if($shouldDeletePrior) {
                $stmt = $this->executeSingleIdParamStatement($modelId, "DELETE FROM $table WHERE $modelColumn = ?");
                if($stmt->error) {
                    return false;
                }
            }

            $sql = $this->getTagArrayReplaceQuery($table, $modelColumn, $modelId, $tags, $shouldDeletePrior);
            $result = $this->connection->query($sql);

            return mysqli_affected_rows($this->connection) > 0 || mysqli_num_rows($result) > 0;
        }

        private function executeSingleIdParamStatement(int $id, string $sql) {
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            return $stmt;
        }

        // TODO: this doesn't use prepared statements either
        private function getTagArrayInsertQuery(array $tags) {
            $dataArray = array_map(function($tag) {
                $name = $this->connection->real_escape_string($tag->getName());
                $colour = $this->connection->real_escape_string($tag->getColour());
                $isFromFacebook = $tag->isFromFacebook() ?: 0;

                return "('$name', '$colour', $isFromFacebook)";
            }, $tags);

            $sql = "INSERT IGNORE INTO tags(" . Constants::$name . ", " . Constants::$colour . ", " . Constants::$isFromFacebook . ") VALUES "; // IGNORE ignores duplicate key errors and doesn't insert tags w/ same name
            $sql .= implode(',', $dataArray);

            return $sql;
        }

        private function getTagArrayReplaceQuery(string $table, string $modelColumn, int $modelId, array $tags) {
            $dataArray = array_map(function($tag) use($modelId) {
                $name = $this->connection->real_escape_string($tag->getName());

                return "('$modelId', '$name')";
            }, $tags);

            $sql = "REPLACE INTO $table ($modelColumn, tag_name) VALUES ";
            $sql .= implode(',', $dataArray);

            return $sql;
        }

        private function extractTagsFromJoinQuery(array $rows) {
            $tags = array();
            foreach($rows as $row) {
                if($this->isTagRowInvalid($row)) {
                    continue;
                }

                $tags[$row[Constants::$id]][] =
                    new Tag($row[Constants::$tagName], $row[Constants::$colour], $row[Constants::$isFromFacebook]);
            }
            return empty($tags) ? null : $tags;
        }

        // should be always called in transaction context for CRUD methods
        private function getUserChatroomsId(int $userId) {
            $result = $this->executeSingleIdParamStatement($userId,
                "SELECT us_chrms.chatroom_id FROM user_chatrooms us_chrms WHERE user_id = ?")
                ->get_result();

            if($result && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC)) != null) {
                return array_filter(array_map(function($row) {
                    return array_key_exists(Constants::$chatroomId, $row) ?
                        $row[Constants::$chatroomId] : false;
                }, $rows));
            }

            return false;
        }

        // should be always called in transaction context for CRUD methods, otherwise not
        function getOwnerChatroomId(int $userId) {
            $result = $this->executeSingleIdParamStatement($userId, "SELECT chrms.id FROM chatrooms chrms
                INNER JOIN user_chatrooms us_chrms ON chrms.owner_id = us_chrms.user_id AND chrms.id = us_chrms.chatroom_id 
                    AND us_chrms.user_id = ?")->get_result();

            return $this->getOneColumnValueFromSingleRow($result, Constants::$id);
        }

        private function getMessageOwnerIdAndChatroomId(int $messageId) {
            $result = $this->executeSingleIdParamStatement($messageId, "SELECT msgs.user_sent_id, msgs.chatroom_sent_id FROM messages msgs
                WHERE msgs.id = ?")->get_result();

            return $this->getMultiColumnValueFromSingleRow($result,
                array(Constants::$userSentId, Constants::$chatroomSentId));
        }

        private function insertTagModel(Model $model) {
            $tagsInsertSuccess = true;
            if($tags = $model->getTags()) { // insert user tags here
                $tagsInsertSuccess = $this->connection->query($this->getTagArrayInsertQuery($tags));
            }

            return $tagsInsertSuccess;
        }

        private function insertUserChatroomIds(User $user) {
            $userChatroomIdInsertSuccess = true;
            if($chatroomIds = $user->getChatroomIds()) {
                $userChatroomIdInsertSuccess = $this->connection->query(
                    $this->getUserChatroomIdsInsertQuery(
                        $user->getId(), $user->getChatroomIds()
                    )
                );
            }

            return $userChatroomIdInsertSuccess;
        }

        private function getUserChatroomIdsInsertQuery(int $userId, array $chatroomIds) {
            $dataArray = array_map(function($chatroomId) use ($userId) {
                return "($userId, $chatroomId)";
            }, $chatroomIds);

            $sql = "INSERT IGNORE INTO user_chatrooms(" . Constants::$userId . ", " . Constants::$chatroomId . ") VALUES "; // IGNORE ignores duplicate key errors and doesn't insert same ids
            $sql .= implode(',', $dataArray);

            return $sql;
        }

        private function insertUserChatroomId(int $userId, int $chatroomId) {
            $stmt = $this->connection->prepare("INSERT INTO user_chatrooms VALUES(?, ?)");
            $stmt->bind_param("ii", $userId, $chatroomId);
            return $stmt->execute();
        }

        private function deleteUserChatroomId(int $userId, int $chatroomId) {
            $stmt = $this->connection->prepare("DELETE FROM user_chatrooms WHERE user_id = ? AND chatroom_id = ?");
            $stmt->bind_param("ii", $userId, $chatroomId);
            return $stmt->execute();
        }

        private function extractChatroomsFromJoinQuery(array $rows, ?array $tags, ?array $eventIds) {
            return array_reduce($rows, function($result, array $row) use ($eventIds, $tags) {
                if(array_key_exists($row[Constants::$id], $result)) {
                    return $result;
                }

                $result[$row[Constants::$id]] = new Chatroom(
                    $row[Constants::$id],
                    $row[Constants::$name],
                    $row[Constants::$description],
                    $row[Constants::$hasImage],
                    $row[Constants::$ownerId],
                    $eventIds != null ? (array_key_exists($row[Constants::$id], $eventIds) ?
                        $eventIds[$row[Constants::$id]] : null) : null,
                    $tags != null ? (array_key_exists($row[Constants::$id], $tags) ?
                        $tags[$row[Constants::$id]] : null) : null
                );
                return $result;
            }, array());
        }

        private function extractUsersFromJoinQuery(array $rows, ?array $tags, ?array $chatroomIds) {
            return array_reduce($rows, function($result, array $row) use ($chatroomIds, $tags) {
                if(!ModelUtils::arrayContainsIdValue($result, $row[Constants::$id])) {
                    $result[] = new User(
                        $row[Constants::$id],
                        $row[Constants::$email],
                        $row[Constants::$username],
                        $row[Constants::$description],
                        $row[Constants::$hasImage],
                        $chatroomIds != null ? (array_key_exists($row[Constants::$id], $chatroomIds) ?
                            $chatroomIds[$row[Constants::$id]] : null) : null,
                        $tags != null ? (array_key_exists($row[Constants::$id], $tags) ?
                            $tags[$row[Constants::$id]] : null) : null
                    );
                }
                return $result;
            }, array());
        }

        private function parseChatroomsOnFinishedTransaction(mysqli_result $result) {
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $this->connection->commit();

            $tags = null;
            $eventIds = null;
            $this->extractTagsAndUniqueNumericArrayFromJoinQuery($rows, Constants::$eventId, $tags,
                $eventIds, true, true);
            $chatrooms = array_values($this->extractChatroomsFromJoinQuery($rows, $tags, $eventIds));

            return $chatrooms == false ? array() : $chatrooms;
        }

        public function setModelHasImage(int $id, bool $hasImage, string $table) {
            $stmt = $this->connection->prepare("UPDATE $table SET has_image = ? WHERE id = ?");

            $stmt->bind_param("ii", $hasImage, $id);
            $stmt->execute();

            return $stmt->affected_rows >= 0;
        }

        private function finishTransactionOnCond(bool $condition) {
            if($condition) {
                $this->connection->commit();
            } else $this->connection->rollback();
        }

        private function getOneColumnValueFromSingleRow(?mysqli_result $result, string $column) {
            if($result && ($row = $result->fetch_assoc()) != null) {
                return array_key_exists($column, $row) ?
                    $row[$column] : null; // first row is count at first column - the value of said count
            }

            return false;
        }

        private function getMultiColumnValueFromSingleRow(?mysqli_result $result, array $columns) {
            if($result && ($row = $result->fetch_assoc()) != null) {
                return array_reduce($columns, function($result, string $column) use($row) {
                    $result[$column] = array_key_exists($column, $row) ?
                        $row[$column] : null;
                    return $result;
                }, array()); // first row is count at first column - the value of said count
            }

            return false;
        }

        private function extractTagsAndUniqueNumericArrayFromJoinQuery(array $rows, string $column,
                                                                       ?array& $tags, ?array& $numeric,
                                                                       bool $keyTagsByRowId = false, bool $keyNumericByRowId = false) {
            $parseTags = $this->validateAllRowTags($rows);
            $parseNumericRow = $this->validateAllRowColumns($rows, $column);

            if($parseTags) {
                $tags = array();
            }

            if($parseNumericRow) {
                $numeric = array();
            }

            if($parseTags || $parseNumericRow) {
                foreach($rows as $row) {
                    if($parseTags) {
                        if(!$this->isTagRowInvalid($row)) {
                            $tag = new Tag($row[Constants::$tagName], $row[Constants::$colour], $row[Constants::$isFromFacebook]);
                            if($keyTagsByRowId) {
                                if(!in_array($tag,
                                    (array_key_exists($row[Constants::$id], $tags)
                                        ? $tags[$row[Constants::$id]] : array()))) {
                                    $tags[$row[Constants::$id]][] = $tag;
                                }
                            } else if(!in_array($tag, $tags))
                                $tags[] = $tag;
                        }
                    }
                    if($parseNumericRow) {
                        if($row[$column] != null) {
                            if($keyNumericByRowId) {
                                if(!in_array($row[$column],
                                    (array_key_exists($row[Constants::$id], $numeric)
                                        ? $numeric[$row[Constants::$id]] : array()))) {
                                    $numeric[$row[Constants::$id]][] = $row[$column];
                                }
                            } else if(!in_array($row[$column], $numeric)) {
                                $numeric[] = $row[$column];
                            }
                        }
                    }
                }
                if($parseTags && !$keyTagsByRowId) $tags = array_values(array_unique($tags, SORT_REGULAR));
            }
        }

        private function isTagRowInvalid(array $row) {
            return !isset($row[Constants::$tagName]) ||
                !isset($row[Constants::$colour]) || !isset($row[Constants::$isFromFacebook]);
        }

        private function sendNotificationToChatroomOnCond(bool &$condition, int $chatroomId, string $notificationType, Model $message) {
            if($condition) {
                $condition = $this->fcm->sendMessageToChatroom(
                    $chatroomId,
                    $notificationType,
                    $message
                );
            }
        }

        private function sendBatchedNotificationToChatroomOnCond(bool &$condition, array $chatroomIds, string $notificationType, Model $message) {
            if($condition) {
                $condition = $this->fcm->sendBatchedMessageToTopics(
                    $chatroomIds,
                    $notificationType,
                    $message
                );
            }
        }

        private function sendUserChatroomNotification(int $currentUserChatroomId, User $user, string $type, string $chatMessage) {
            $this->fcm->sendMessageToChatroom($currentUserChatroomId,
                $type,
                $user
            );

            $messageCreated = $this->createChatroomMessage(new Message(
                null,
                $chatMessage,
                null,
                $currentUserChatroomId,
                null
            ));
            return isset($messageCreated);
        }

        private function sendUserChatroomBatchedNotification(array $chatroomIds, User $user, string $type, string $chatMessage) {
            $this->fcm->sendBatchedMessageToChatroom($chatroomIds,
                $type,
                $user
            );

            // BIG FIXME: Optimise sending of new message notifications to individual chatrooms too!
            foreach ($chatroomIds as $chatroomId) {
                $chatroomMessages[] = $this->createChatroomMessage(new Message(
                    null,
                    $chatMessage,
                    null,
                    $chatroomId,
                    null
                ));
            }
            return !empty($chatroomMessages);
        }

        private function validateAllRowColumns(array $rows, string $column) {
            foreach($rows as $row) {
                if(isset($row[$column])) return true;
            }
            return false;
        }

        private function validateAllRowTags(array $rows) {
            foreach($rows as $row) {
                if(!$this->isTagRowInvalid($row)) return true;
            }
            return false;
        }
    }
?>