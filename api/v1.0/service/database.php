<?php
    require_once("../consts/constants.php");
    require_once("../forwarder/socket_server_forwarder.php");
    use Kreait\Firebase\Factory;
    use Google\Cloud\Firestore\FirestoreClient;

    class Database {
        private string $host = "127.0.0.1"; // to be changed if hosted on server
        private string $user_name = "root";
        private string $user_password = "";
        private string $db_name = "hobbyfi_db";
        private mysqli $connection;
        private FirestoreClient $firestore;
        private SocketServerForwarder $forwarder;

        function __construct() {
            $this->connection = mysqli_connect(
                $this->host,
                 $this->user_name,
                 $this->user_password,
                  $this->db_name, "3308"
            );
            $this->firestore = (new Factory)->withServiceAccount(
                __DIR__ . '/../keys/hobbyfi-firebase-adminsdk-o1f83-e1d558ffae.json'
            )->createFirestore()->database();
            $this->forwarder = new SocketServerForwarder();
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
            // $user->escapeStringProperties($this->connection);
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

        public function userExistsOrPasswordTaken(string $username, ?string $email) { // user exists if username or password are taken
            $stmt = $this->connection->prepare("SELECT username FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();

            return mysqli_num_rows($stmt->get_result()) > 0; // if more than one row found => user exists
        }

        public function userExists(string $id) { // user exists if username or password are taken
            $result = $this->executeSingleIdParamStatement($id, "SELECT id FROM users WHERE id = ?")
                ->get_result();

            return mysqli_num_rows($result) > 0 ? "true" : "false"; // if more than one row found => user exists
        }

        private function getUserIdAndHashPasswordByEmail(string $email) {
            $stmt = $this->connection->prepare("SELECT id, password FROM users WHERE email = ?"); // get associated user by email
            $email = mysqli_real_escape_string($this->connection, $email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            return $stmt->get_result();
        }


        private function getUserUsername(int $id) {
            return $this->executeSingleIdParamStatement($id, "SELECT username FROM users WHERE id = ?")
                ->get_result()->fetch_assoc()[Constants::$username];
        }

        public function validateUserByEmail(string $email) {
            $result = $this->getUserIdAndHashPasswordByEmail($email);
            if(mysqli_num_rows($result) > 0 && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC))) {
                if(count($rows) && $row = $rows[0]) {
                    if($row[Constants::$password] == null) {
                        return null;
                    }
                    return array(Constants::$id=>$row[Constants::$id], Constants::$password=>$row[Constants::$password]);
                        // get first user (?) FIXME: Handle multiple accounts with unique email
                }
            }
            return false;
        }

        public function validateUser(string $email, string $password) {
            $result = $this->getUserIdAndHashPasswordByEmail($email);

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
            $userStmt = $this->executeSingleIdParamStatement($id, "SELECT
                us.description, us.username, us.email, us.has_image,
                us_tags.tag_name, tgs.colour, tgs.is_from_facebook, usr_chrms.chatroom_id, usr_chrms.push_allowed
                FROM users us
                LEFT JOIN user_chatrooms usr_chrms ON usr_chrms.user_id = us.id
                LEFT JOIN user_tags us_tags ON us.id = us_tags.user_id
                LEFT JOIN tags tgs ON tgs.name LIKE us_tags.tag_name
                WHERE id = ?"
            );

            $userResult = $userStmt->get_result();

            if(mysqli_num_rows($userResult) > 0) {
                $rows = mysqli_fetch_all($userResult, MYSQLI_ASSOC); // fetch the resulting rows in the form of a map (associative array)

                $tags = null;
                $chatroomIds = null;
                $allowedPushChatroomIds = null;
                $this->extractTagsAndTwoUniqueNumericArrayWithPredicatesFromJoinQuery($rows, Constants::$chatroomId, Constants::$chatroomId, Constants::$pushAllowed,
                    $tags, $chatroomIds, $allowedPushChatroomIds);

                return new User($id,
                    $rows[0][Constants::$email], $rows[0][Constants::$username],
                    $rows[0][Constants::$description], $rows[0][Constants::$hasImage],
                    $chatroomIds,
                    $tags,
                    $allowedPushChatroomIds
                ); // not optimal; hack solution because 2 iterations over the rows are being done
            }

            return null;
        }

        public function updateUser(User $user, ?string $password,
                                   ?int $leaveChatroomId = null, ?int $joinChatroomId = null, string $authToken) {
            $this->connection->begin_transaction();
            $userUpdateSuccess = true;
            // FIXME: Pass in this as argument from edit.php
            $shouldUpdateUser = !$user->isUpdateFormEmpty() || $password != null;
            $shouldUpdateChatroomId = $leaveChatroomId != null || $joinChatroomId != null;

            if($shouldUpdateUser) {
                $originalUsername = $this->getUserUsername($user->getId()); // transaction context => old one still accessible
                mysqli_query($this->connection, $user->getUpdateQuery($password));
                // FIXME: Code dup
                $userUpdateSuccess = mysqli_affected_rows($this->connection) > 0;

                // change Firestore record username, if needed
                if(!is_null($user->getName())) {
                    $document = $this->firestore->collection(Constants::$locations)
                        ->document($originalUsername);
                    $docSnapshot = $document->snapshot();

                    if($docSnapshot->exists()) {
                        // doc exists and needs name change
                        $this->firestore->collection(Constants::$locations)
                            ->document($user->getName())
                            ->create($docSnapshot->data());
                        $document->delete();
                    }
                }
            }

            if($shouldUpdateChatroomId) {
                $user = $this->getUser($user->getId());
                $chatroomIds = $user->getChatroomIds() ?: [];

                if($joinChatroomId != null) { // if user has updated chatroom id by joining
                    // send notification for joining
                    $this->insertUserChatroomId($user->getId(), $joinChatroomId);

                    array_push($chatroomIds, $joinChatroomId);
                    $user->setChatroomIds($chatroomIds);

                    $userUpdateSuccess = $this->forwardUserMessageToSocketServer($joinChatroomId, $user,
                        Constants::$JOIN_USER_TYPE, Constants::timelineUserJoinMessage($user->getName()), $authToken);
                } else if($leaveChatroomId != null) { // else get if they are currently in chatroom (or previous chatroom for user)
                    // send notification for edit/leave otherwise
                    $this->deleteUserChatroomId($user->getId(), $leaveChatroomId);

                    unset($chatroomIds[array_search($leaveChatroomId, $chatroomIds)]);
                    $user->setChatroomIds(array_values($chatroomIds));

                    $userUpdateSuccess = $this->forwardUserMessageToSocketServer($leaveChatroomId, $user,
                        Constants::$LEAVE_USER_TYPE, Constants::timelineUserLeaveMessage($user->getName()), $authToken);

                    $firestoreDocSnapshot = $this->firestore->collection(Constants::$locations)
                        ->document($user->getName())->snapshot();
                    if($firestoreDocSnapshot->exists()) {
                        $this->removeFromArrayFieldOrDeleteDocByCountBoundary(
                            $firestoreDocSnapshot,
                            Constants::$chatroomId,
                            Constants::$chatroomId, [$leaveChatroomId]
                        );
                        $eventIds = $this->getChatroomEventIds($leaveChatroomId);

                        if(!is_null($eventIds)) {
                            $this->removeFromArrayFieldOrDeleteDocByCountBoundary(
                                $firestoreDocSnapshot,
                                Constants::$eventIds,
                                Constants::$eventIds, $eventIds, count($eventIds));
                        }
                    }
                }
            } else if($chatroomIds = $this->getUserChatroomIds($user->getId())) {
                $this->forwardBatchedMessageToSocketServerOnCond($userUpdateSuccess,
                    $chatroomIds,
                    Constants::$EDIT_USER_TYPE,
                    $user,
                    $authToken
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

        public function kickUserFromChatroom(int $ownerId, int $chatroomKickId, int $kickUserId, string $authToken) {
            $this->connection->begin_transaction();
            $ownerChatroomIds = $this->getOwnerChatroomIds($ownerId);

            if(!is_array($ownerChatroomIds) || !in_array($chatroomKickId, $ownerChatroomIds)) {
                $this->connection->rollback();
                return false;
            }

            $updateSuccess = $this->updateUser(new User($kickUserId), null, $chatroomKickId,
                null, $authToken);

            $this->finishTransactionOnCond($updateSuccess);
            return $updateSuccess;
        }

        public function toggleUserPushNotificationAllowForChatroom(int $id, int $chatroomId, bool $toggle) {
            $stmt = $this->connection->prepare("UPDATE user_chatrooms SET push_allowed = ? WHERE user_id = ? AND chatroom_id = ?");
            $stmt->bind_param("iii", $toggle, $id, $chatroomId);
            $stmt->execute();
            return mysqli_affected_rows($this->connection) > 0;
        }

        public function deleteUser(int $id, string $authToken) {
            include_once "../utils/image_utils.php";
            $this->connection->begin_transaction();

            $deleteChatrooms = false;
            $leaveChatroom = false;
            $ownChatroomIds = null;
            // db checks prior to deletion (can't access later on, duh)
            if($chatroomIds = $this->getUserChatroomIds($id)) {
                if($ownChatroomIds = $this->getOwnerChatroomIds($id)) {
                    if(count($chatroomIds) > 1) { // has other chatrooms apart from his own
                        $leaveChatroom = true;
                    }

                    $deleteChatrooms = true;
                } else {
                    $leaveChatroom = true;
                }
            }

            $user = $this->getUser($id);

            $stmt = $this->executeSingleIdParamStatement($id, "DELETE FROM users WHERE id = ?");
            $deleteSuccess = $stmt->affected_rows > 0;

            if($deleteSuccess) {
                if($deleteChatrooms) {
                    $this->forwardBatchedMessageToSocketServer(
                        $ownChatroomIds,
                        Constants::$DELETE_CHATROOM_TYPE,
                        new Chatroom(-1), // payload doesn't matter, dest. matters
                        $authToken
                    );

                    foreach($this->firestore->collection(Constants::$locations)
                                ->where(Constants::$chatroomId, 'array-contains', $ownChatroomIds)
                                ->documents()->rows() as $doc) {
                        $this->removeFromArrayFieldOrDeleteDocByCountBoundary($doc, Constants::$chatroomId, Constants::$chatroomId, [$ownChatroomIds]);
                    }
                }

                if($leaveChatroom) {
                    $this->forwardUserBatchedMessageToSocketServer($chatroomIds,
                        $user,
                        Constants::$LEAVE_USER_TYPE,
                        Constants::timelineUserLeaveMessage($user->getName()),
                        $authToken
                    );
                }

                ImageUtils::deleteImageFromPath(
                    $id,
                    ImageUtils::getBucketLocationForUser(),
                    Constants::$users,
                    false,
                    true
                );

                $this->firestore->collection(Constants::$locations)->document($user->getName())
                        ->delete();
            }

            $this->finishTransactionOnCond($deleteSuccess);
            return $deleteSuccess;
        }

        // gets all users with any id BUT this one;
        public function getChatroomUsers(int $userId, int $chatroomId) { // multiplier starts from 1
            require_once("../utils/model_utils.php");

            $this->connection->begin_transaction();

            if(!($chatroomIds = $this->getUserChatroomIds($userId)) || !in_array($chatroomId, $chatroomIds)) {
                return false;
            }

            $stmt = $this->connection->prepare(
                "SELECT us.id, us.description, us.username, us.email, us.has_image, 
                us_tags.tag_name, tgs.colour, tgs.is_from_facebook, us_chrms.chatroom_id, us_chrms.push_allowed FROM users us
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
                $pushAllowedChatroomIds = null;
                $this->extractTagsAndTwoUniqueNumericArrayWithPredicatesFromJoinQuery($rows, Constants::$chatroomId, Constants::$chatroomId, Constants::$pushAllowed,
                    $tags, $chatroomIds, $pushAllowedChatroomIds, true, true, true);

                return $this->extractUsersFromJoinQuery($rows, $tags, $chatroomIds, $pushAllowedChatroomIds);
            }

            $this->connection->rollback();
            return (empty($stmt->error)) ? array() : null;
        }

        public function createChatroom(Chatroom $chatroom) {
            $this->connection->begin_transaction();

            // server-side check that should be lifted to db-trigger
            if(count($this->getOwnerChatroomIds($chatroom->getOwnerId())) > 100) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->connection->prepare("INSERT INTO chatrooms(id, name, description, has_image, owner_id) 
                VALUES(?, ?, ?, ?, ?)");

            // still need to destruct this class and allocate more variables for this goddamn bind_param() method...
            // $chatroom->escapeStringProperties($this->connection);
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
                $userUpdateStmt = $this->connection->prepare("INSERT INTO user_chatrooms VALUES(?, ?, 0)");
                $userUpdateStmt->bind_param("ii", $ownerId, $chatroomId);

                $chatroomCreateSuccess |= $userUpdateStmt->execute();
                $this->finishTransactionOnCond($chatroomCreateSuccess);
                return $chatroomCreateSuccess ? $chatroomId : null; // FIXME: Expand upon errors and have them be more concise.
            }
            $this->connection->rollback();
            return null;
        }

        public function updateChatroom(Chatroom $chatroom, string $authToken) {
            // Owner evaluation not needed for now due to being exposed in chatroom edit.php
            // which is why chatroomId is passed in
            $this->connection->begin_transaction();

            if(!in_array($chatroom->getId(), $this->getOwnerChatroomIds($chatroom->getOwnerId()))) {
                return null;
            }

            $chatroomUpdateSuccess = true;
            $shouldUpdateChatroom = !$chatroom->isUpdateFormEmpty();
            if($shouldUpdateChatroom) {
                // $chatroom->escapeStringProperties($this->connection);
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
            $this->forwardMessageToSocketServerOnCond($updateSuccess,
                $chatroom->getId(),
                Constants::$EDIT_CHATROOM_TYPE,
                $chatroom,
                $authToken
            );

            $this->finishTransactionOnCond($updateSuccess);
            return $updateSuccess;
        }

        public function deleteChatroom(int $ownerId, int $chatroomId, string $authToken) {
            include_once "../utils/image_utils.php";

            $this->connection->begin_transaction();
            if(!in_array($chatroomId, $this->getOwnerChatroomIds($ownerId))) {
                return false;
            }

            $stmt = $this->executeSingleIdParamStatement($chatroomId, "DELETE FROM chatrooms WHERE id = ?");

            ImageUtils::deleteImageFromPath(
                $chatroomId, 
                ImageUtils::getBucketLocationForChatroom($chatroomId),
                Constants::$chatrooms,
                true
            );

            $deleteSuccess = $stmt->affected_rows > 0;
            
            if($deleteSuccess) {
                foreach($this->firestore->collection(Constants::$locations)
                            ->where(Constants::$chatroomId, 'array-contains', $chatroomId)
                            ->documents()->rows() as $doc) {
                    $this->removeFromArrayFieldOrDeleteDocByCountBoundary($doc, Constants::$chatroomId, Constants::$chatroomId, [$chatroomId]);
                }
            }

            // FCM - delete chatroom
            $this->forwardMessageToSocketServerOnCond($deleteSuccess,
                $chatroomId,
                Constants::$DELETE_CHATROOM_TYPE,
                new Chatroom($chatroomId),
                $authToken
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
                $this->extractTagsAndUniqueNumericArrayFromJoinQuery($rows, Constants::$eventId, $tags, $eventIds);

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
            if($fetchOwnChatrooms && !($chatroomIds = $this->getUserChatroomIds($userId))) {
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
        
        public function getChatroomMessages(int $userId, int $chatroomId, ?string $query, ?int $messageId, ?int $multiplier = 1) {
            $this->connection->begin_transaction();
            if(!($chatroomIds = $this->getUserChatroomIds($userId))) {
                $this->connection->rollback();
                return false;
            }
            $messagePageSearch = !is_null($messageId);

            $page = null;
            if($messagePageSearch && is_null($multiplier)) {
                $stmt = $this->connection->prepare("SELECT COUNT(*) AS page_number 
                        FROM messages WHERE create_time > (SELECT create_time FROM messages WHERE id = ? ORDER BY create_time DESC) 
                        AND chatroom_sent_id = ? ORDER BY create_time DESC");
                $stmt->bind_param("ii", $messageId, $chatroomId);
                $stmt->execute();

                $count = $stmt->get_result()->fetch_assoc()[Constants::$pageNumber];
                $page = floor($count / 20);
                $multiplier = 20 * $page;

                // if count = 0 BUT the message ID is NOT the newest message for room (where no more messages is acceptable)
                // => false message id sent
                if($count == 0 && $messageId != $this->executeSingleIdParamStatement($chatroomId,
                        "SELECT MAX(id) AS max_id FROM messages WHERE chatroom_sent_id = ?")->get_result()->fetch_assoc()[Constants::$maxId]) {
                    $this->connection->rollback();
                    return null;
                }
            } else $multiplier = 20*($multiplier - 1);

            $querySearch = !is_null($query);
            $stmt = $this->connection->prepare(
                "SELECT * FROM messages WHERE chatroom_sent_id = ?" . ($querySearch ? " AND INSTR(message, ?) > 0 " : " ") .
                    "ORDER BY create_time DESC
                    LIMIT 20 OFFSET ?"
            );

            if($querySearch) {
                $stmt->bind_param("isi", $chatroomId, $query, $multiplier);
            } else $stmt->bind_param("ii", $chatroomId, $multiplier);

            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $this->connection->commit();

                if(!$messagePageSearch) {
                    return array_map(function($row) {
                        return new Message(
                            $row[Constants::$id],
                            $row[Constants::$message],
                            $row[Constants::$createTime],
                            $row[Constants::$chatroomSentId],
                            $row[Constants::$userSentId],
                        );
                    }, $rows);
                } else {
                    return array(array_map(function($row) {
                        return new Message(
                            $row[Constants::$id],
                            $row[Constants::$message],
                            $row[Constants::$createTime],
                            $row[Constants::$chatroomSentId],
                            $row[Constants::$userSentId],
                        );
                    }, $rows), $page + 1);
                }
            }

            $this->connection->rollback();
            return (empty($stmt->error)) ? array() : null;
        }

        public function createChatroomMessage(Message $message, string $authToken, bool $shouldFinishTransaction = true) {
            $this->connection->begin_transaction();

            if($message->getUserSentId() != null && (!($chatroomIds = $this->getUserChatroomIds($message->getUserSentId())) ||
                    !in_array($message->getChatroomSentId(), $chatroomIds))) {
                $this->connection->rollback();
                return false; // users should only send messages in a their chatrooms from here (theirs) and nothing else
            }

            $stmt = $this->connection->prepare("INSERT INTO messages(id, message, create_time, chatroom_sent_id, user_sent_id) 
                VALUES(?, ?, CURRENT_TIMESTAMP(6), ?, ?)");

            // $message->escapeStringProperties($this->connection);
            $id = $message->getId();
            $msg = $message->getMessage();
            $userSentId = $message->getUserSentId();
            $chatroomId = $message->getChatroomSentId();

            $stmt->bind_param("isii", $id, $msg, $chatroomId, $userSentId);

            $insertSuccess = $stmt->execute();
            $messageId = mysqli_insert_id($this->connection);
            $message->setId($messageId);

            // kinda dumb but workaround for now to get create time needed for create response (error suppression because even though it works, it shows a weird notice)
            @($message->setCreateTime($this->executeSingleIdParamStatement($messageId,
                "SELECT create_time FROM messages WHERE id = ?")
                ->get_result()
                ->fetch_assoc()[Constants::$createTime]));

            if($shouldFinishTransaction) {
                $this->forwardMessageToSocketServerOnCond($insertSuccess,
                    $chatroomId,
                    Constants::$CREATE_MESSAGE_TYPE,
                    $message,
                    $authToken
                );
                $this->finishTransactionOnCond($insertSuccess);
            }

            return $insertSuccess ? $message : null;
        }

        // very bruh method
        public function createChatroomImageMessage(Message $message, string $base64Image, string $authToken) {
            require_once("../utils/image_utils.php");

            $chatroomId = $message->getChatroomSentId();

            if($message->getMessage() == null) {
                $message->setMessage(""); // gets updated
            }

            if(!($message = $this->createChatroomMessage($message, $authToken, false))) {
                $this->connection->rollback();
                return null;
            }

            if(($object = ImageUtils::uploadImageToPath($message->getId(), ImageUtils::getBucketLocationForChatroomMessage($chatroomId),
                    $base64Image, Constants::$messages, false))) {
                // for now this means message img insert => replace message photo url with right one with latest insert id
                // FOR THIS TABLE (very bruh but such is life...)
                $messageId = $message->getId();
                $messageAsPhotoUrl = ImageUtils::getPublicContentDownloadUrl(
                    ImageUtils::getBucketLocationForChatroomMessage($chatroomId), $messageId);

                $stmt = $this->connection->prepare("UPDATE messages SET message = ? WHERE id = ?");
                $stmt->bind_param("si", $messageAsPhotoUrl, $messageId);

                $message->setMessage($messageAsPhotoUrl);
                $insertSuccess = $stmt->execute();

                $this->forwardMessageToSocketServerOnCond($insertSuccess, $chatroomId,
                    Constants::$CREATE_MESSAGE_TYPE,
                    $message,
                    $authToken
                );
                $this->finishTransactionOnCond($insertSuccess);
                return $insertSuccess ? $message : null;
            }

            $this->connection->rollback();
            return null;
        }

        public function deleteChatroomMessage(int $userId, int $messageId, string $authToken) {
            require_once("../utils/image_utils.php");

            $this->connection->begin_transaction();
            // assert message owner OR chatroom owner
            if((!($messageInfo = $this->getMessageOwnerIdAndChatroomId($messageId)) ||
                    $userId != (is_null($messageInfo[Constants::$userSentId]) ? $userId : $messageInfo[Constants::$userSentId]))
                        && !(in_array($messageInfo[Constants::$chatroomSentId], $this->getOwnerChatroomIds($userId)))) {
                $this->connection->rollback();
                return null;
            }

            $stmt = $this->executeSingleIdParamStatement($messageId, "DELETE FROM messages WHERE id = ?");

            ImageUtils::deleteImageFromPath(
                $messageId,
                ImageUtils::getBucketLocationForChatroomMessage($messageInfo[Constants::$chatroomSentId]),
                Constants::$messages,
            );

            $affectedRows = $stmt->affected_rows > 0;
            $this->forwardMessageToSocketServerOnCond($affectedRows,
                $messageInfo[Constants::$chatroomSentId],
                Constants::$DELETE_MESSAGE_TYPE,
                new Message($messageId, null, null, $messageInfo[Constants::$chatroomSentId], $userId),
                $authToken
            );
            $this->finishTransactionOnCond($affectedRows);
            return $affectedRows;
        }

        public function updateChatroomMessage(Message $message, string $authToken) {
            // assert message owner & correct chatroom
            $this->connection->begin_transaction();
            $userSentMessageId = $message->getUserSentId();
            if(!($messageInfo = $this->getMessageOwnerIdAndChatroomId($message->getId()))
                    || ((array_key_exists(Constants::$userSentId, $messageInfo) ?
                            $messageInfo[Constants::$userSentId] : -1) != $userSentMessageId)) {
                $this->connection->rollback();
                return false;
            }

            $message->setChatroomSentId($messageInfo[Constants::$chatroomSentId]);
            $updateSuccess = $this->connection->query($message->getUpdateQuery()); // TODO: Handle no update option in edit.php
            $this->forwardMessageToSocketServerOnCond($updateSuccess,
                $messageInfo[Constants::$chatroomSentId],
                Constants::$EDIT_MESSAGE_TYPE,
                $message,
                $authToken
            );
            $this->finishTransactionOnCond($updateSuccess);
            return $updateSuccess;
        }

        public function isChatroomMessageNotSolelyImage(int $messageId) {
            $results = $this->executeSingleIdParamStatement($messageId,
                "SELECT message, chatroom_sent_id FROM messages WHERE id = ?")->get_result();

            if($results->num_rows > 0 && ($row = $results->fetch_assoc())) {
                $supposedMessageImageUrl = Constants::getPhotoUrlForDir(
                    Constants::chatroomMessageImagesDir($row[Constants::$chatroomSentId])
                    . "/" . $messageId . ".jpg");
                if(strcmp($row[Constants::$message], $supposedMessageImageUrl) == 0) return false;
                else return strstr($row[Constants::$message], $supposedMessageImageUrl) ? $supposedMessageImageUrl : true;
                    // check if message with image contained or just pure message
            }

            return null;
        }

        public function getChatroomEvent(int $userId, int $eventId) {
            $this->connection->begin_transaction();

            $stmt = $this->connection->prepare("SELECT evnt.id, evnt.name, 
                    evnt.description, evnt.latitude, evnt.longitude, evnt.has_image, evnt.start_date, evnt.date, evnt.chatroom_id
                 FROM events evnt
                INNER JOIN chatrooms chrms ON chrms.id = evnt.chatroom_id
                INNER JOIN user_chatrooms usr_chrms ON usr_chrms.chatroom_id = chrms.id 
                    AND usr_chrms.user_id = ? AND evnt.id = ?");
            $stmt->bind_param("ii", $userId, $eventId);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result && mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $this->connection->commit();

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
            }

            $this->connection->rollback();
            return null;
        }

        public function getChatroomEvents(int $userId, int $chatroomId) {
            $this->connection->begin_transaction();

            $stmt = $this->connection->prepare("SELECT evnt.id, evnt.name, 
                    evnt.description, evnt.latitude, evnt.longitude, evnt.has_image, evnt.start_date, evnt.date, evnt.chatroom_id
                 FROM events evnt
                INNER JOIN chatrooms chrms ON chrms.id = evnt.chatroom_id
                INNER JOIN user_chatrooms usr_chrms ON usr_chrms.chatroom_id = chrms.id 
                    AND usr_chrms.user_id = ? AND usr_chrms.chatroom_id = ?");
            $stmt->bind_param("ii", $userId, $chatroomId);
            $stmt->execute();

            if(($result = $stmt->get_result()) && mysqli_num_rows($result) > 0) {
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

        private function getChatroomEventIds(int $chatroomId) {
            $result = $this->executeSingleIdParamStatement($chatroomId, "SELECT evnts.id
                FROM events evnts
                INNER JOIN chatrooms chrms ON chrms.id = evnts.chatroom_id AND chrms.id = ?
            ")->get_result();

            if($result && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC)) != null) {
                return array_filter(array_map(function($row) {
                    return array_key_exists(Constants::$id, $row) ?
                        $row[Constants::$id] : false;
                }, $rows));
            }

            return null;
        }

        // chatroom id is sent through query param
        public function createChatroomEvent(int $ownerId, Event $event, string $authToken) {
            $this->connection->begin_transaction();

            if(!in_array($event->getChatroomId(), $this->getOwnerChatroomIds($ownerId))) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->connection->prepare("INSERT INTO 
                events(id, name, description, date, has_image, latitude, longitude, chatroom_id) 
                    VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
            // $event->escapeStringProperties($this->connection);
            $id = $event->getId();
            $name = $event->getName();
            $description = $event->getDescription();
            $date = $event->getDate();
            $hasImage = $event->getHasImage();
            $latitude = $event->getLat();
            $longitude = $event->getLong();
            $chatroomId = $event->getChatroomId();

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

            $this->forwardMessageToSocketServerOnCond($eventCreateSuccess,
                $chatroomId,
                Constants::$CREATE_EVENT_TYPE,
                $event,
                $authToken
            );

            $this->finishTransactionOnCond($eventCreateSuccess);
            return $eventId != null && $eventCreateSuccess ? $event : null;
        }

        public function deleteChatroomEvent(int $ownerId, int $eventId, string $authToken) {
            $this->connection->begin_transaction();

            if(!($chatroomId = $this->getEventChatroomId($eventId)) ||
                    !(in_array($chatroomId, $this->getOwnerChatroomIds($ownerId)))) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->executeSingleIdParamStatement($eventId, "DELETE FROM events WHERE id = ?");

            $deleteSuccess = $stmt->affected_rows > 0;

            if(!$deleteSuccess) {
                $this->connection->rollback();
                return null;
            }

            foreach($this->firestore->collection(Constants::$locations)
                        ->where(Constants::$eventIds, 'array-contains', $eventId)
                        ->documents()->rows() as $doc) {
                $this->removeFromArrayFieldOrDeleteDocByCountBoundary($doc, Constants::$eventIds, Constants::$eventIds, [$eventId]);
            }

            $this->forwardMessageToSocketServerOnCond($deleteSuccess,
                $chatroomId,
                Constants::$DELETE_EVENT_TYPE,
                new Event($eventId),
                $authToken
            );
            $this->finishTransactionOnCond($deleteSuccess);

            return $deleteSuccess ? $deleteSuccess : null;
        }

        public function deleteOldChatroomEvents(int $ownerId, int $chatroomId, string $authToken) {
            $this->connection->begin_transaction();

            if(!in_array($chatroomId, $this->getOwnerChatroomIds($ownerId))) {
                $this->connection->rollback();
                return false;
            }

            $preDeleteResult = $this->executeSingleIdParamStatement($chatroomId,
                "SELECT id FROM events WHERE date < NOW(3) AND chatroom_id = ?")->get_result();
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
                "DELETE FROM events WHERE date < NOW(3) AND chatroom_id = ?");

            $deleteSuccess = $stmt->affected_rows > 0;

            if(!$deleteSuccess) {
                $this->connection->rollback();
                return null;
            }

            foreach($this->firestore->collection(Constants::$locations)
                        ->where(Constants::$eventIds, 'array-contains', $eventIds)
                        ->documents()->rows() as $doc) {
                $this->removeFromArrayFieldOrDeleteDocByCountBoundary($doc, Constants::$eventIds,
                    Constants::$eventIds, $eventIds, count($eventIds));
            }

            $this->forwardMessageToSocketServerOnCond($deleteSuccess,
                $chatroomId,
                Constants::$DELETE_EVENT_BATCH_TYPE,
                new Chatroom(null, null, null, null, null, $eventIds, null), // uh... don't ask
                $authToken
            );
            $this->finishTransactionOnCond($deleteSuccess);

            return $deleteSuccess ? $eventIds : null;
        }

        public function updateChatroomEvent(Event $event, string $authToken) {
            $this->connection->begin_transaction();

            // $event->escapeStringProperties($this->connection);
            $this->connection->query($event->getUpdateQuery());
            $eventUpdateSuccess = mysqli_affected_rows($this->connection) > 0;

            $this->forwardMessageToSocketServerOnCond($eventUpdateSuccess,
                $event->getChatroomId(),
                Constants::$EDIT_EVENT_TYPE,
                $event,
                $authToken
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

            $sql = $this->getTagArrayReplaceQuery($table, $modelColumn, $modelId, $tags);
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

        // should be always called in transaction context for CRUD methods OR only as a single query
        function getUserChatroomIds(int $userId) {
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
        function getOwnerChatroomIds(int $userId) {
            $result = $this->executeSingleIdParamStatement($userId, "SELECT chrms.id FROM chatrooms chrms
                INNER JOIN user_chatrooms us_chrms ON chrms.owner_id = us_chrms.user_id AND chrms.id = us_chrms.chatroom_id 
                    AND us_chrms.user_id = ?")->get_result();

            if($result && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC)) != null) {
                return array_filter(array_map(function($row) {
                    return array_key_exists(Constants::$id, $row) ?
                        $row[Constants::$id] : false;
                }, $rows)) ?: array();
            }
            return array();
        }

        function getEventChatroomIdByOwnerIdAndEvent(int $ownerId, Event $event) {
            $chatroomId = $this->getEventChatroomId($event->getId());

            if(!in_array($chatroomId, $this->getOwnerChatroomIds($ownerId))) {
                return false;
            }

            return $chatroomId;
        }

        private function getEventChatroomId(int $eventId) {
            $result = $this->executeSingleIdParamStatement($eventId, "SELECT chatroom_id FROM events WHERE id = ?")
                ->get_result();
            return $this->getOneColumnValueFromSingleRow($result, Constants::$chatroomId);
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
                return "($userId, $chatroomId, 0)";
            }, $chatroomIds);

            $sql = "INSERT IGNORE INTO user_chatrooms(" . Constants::$userId . ", " . Constants::$chatroomId . ", "
                . Constants::$pushAllowed . ") VALUES "; // IGNORE ignores duplicate key errors and doesn't insert same ids
            $sql .= implode(',', $dataArray);

            return $sql;
        }

        private function insertUserChatroomId(int $userId, int $chatroomId) {
            $stmt = $this->connection->prepare("INSERT INTO user_chatrooms VALUES(?, ?, 0)");
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

        private function extractUsersFromJoinQuery(array $rows, ?array $tags, ?array $chatroomIds, ?array $allowedPushChatroomIds) {
            return array_reduce($rows, function($result, array $row) use ($allowedPushChatroomIds, $chatroomIds, $tags) {
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
                            $tags[$row[Constants::$id]] : null) : null,
                        $allowedPushChatroomIds != null ? (array_key_exists($row[Constants::$id], $allowedPushChatroomIds) ?
                            $allowedPushChatroomIds[$row[Constants::$id]] : null) : null
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

        public function removeFailedDeviceTokens(array $deviceTokens) {
            $filterCondition = (count($deviceTokens) ? "device_token IN('" . implode("', '",
                    array_map(function($deviceToken) { return $this->connection->real_escape_string($deviceToken); }, $deviceTokens)) . "')" : 0);
            $stmt = $this->connection->prepare("DELETE FROM device_tokens WHERE " . $filterCondition);
            $stmt->execute();

            return $stmt->affected_rows > 0;
        }

        public function uploadDeviceToken(int $id, string $deviceToken) {
            $stmt = $this->connection->prepare(
                "INSERT IGNORE INTO device_tokens(user_id, device_token, last_update_time) VALUES(?, ?, NOW(6))");
            $stmt->bind_param("is", $id, $deviceToken);

            return $stmt->execute();
        }

        public function deleteDeviceToken(int $id, string $deviceToken) {
            $stmt = $this->connection->prepare(
                "DELETE FROM device_tokens WHERE user_id = ? AND device_token LIKE ?");
            $stmt->bind_param("is", $id, $deviceToken);
            $stmt->execute();

            return $stmt->affected_rows > 0;
        }

        private function getIdAndDeviceTokenPairsForUsersInChatroom(int $chatroomId) {
            $result = $this->executeSingleIdParamStatement($chatroomId, "SELECT dvc_tokens.user_id, dvc_tokens.device_token FROM device_tokens dvc_tokens 
                    INNER JOIN user_chatrooms usr_chrms ON usr_chrms.user_id = dvc_tokens.user_id 
                        AND usr_chrms.chatroom_id = ? AND usr_chrms.push_allowed = 1")->get_result();
            return $this->extractDeviceTokenAndIdPairsFromJoinQuery($result);
        }

        private function getChatroomIdToIdAndDeviceTokenPairsForUsersInChatrooms(array $chatroomIds) {
            $stmt = $this->connection->prepare("SELECT usr_chrms.chatroom_id, dvc_tokens.user_id, dvc_tokens.device_token FROM device_tokens dvc_tokens 
                    INNER JOIN user_chatrooms usr_chrms ON usr_chrms.user_id = dvc_tokens.user_id 
                        AND usr_chrms.chatroom_id IN(" . implode(", ", $chatroomIds) .") AND usr_chrms.push_allowed = 1");
            $stmt->execute();
            $result = $stmt->get_result();

            return $this->extractDeviceTokenAndIdPairsFromJoinQuery($result, true);
        }

        // FIXME: Unoptimised mappings and searches
        private function extractDeviceTokenAndIdPairsFromJoinQuery(mysqli_result $result, bool $groupedBySeparateChatroomIds = false) {
            if($result && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC)) != null) {
                $idTokenMap = array();
                $tokenMapIds = array();
                
                if($groupedBySeparateChatroomIds) {
                    $tokenMapRoomIds = array();    
                }
                
                $deviceTokenAndIdPairExtractor = function(array $row, array& $idTokenMap, array& $tokenMapIds) {
                    if(!in_array($row[Constants::$userId], $tokenMapIds)) {
                        $tokenMapIds[] = $row[Constants::$userId];
                        $idTokenMap[] = array(
                            Constants::$userId => $row[Constants::$userId],
                            Constants::$deviceTokens => array($row[Constants::$deviceToken])
                        );
                    } else {
                        $idTokenMap[array_search($row[Constants::$userId], $tokenMapIds)][Constants::$deviceTokens][] =
                            $row[Constants::$deviceToken];
                    }
                };

                $roomIdToDeviceTokenAndIdPairExtractor = function(array $row, array& $idTokenMap, array& $tokenMapIds, array& $tokenMapRoomIds)
                            use ($deviceTokenAndIdPairExtractor) {
                    if(!in_array($row[Constants::$chatroomId], $tokenMapRoomIds)) {
                        $tokenMapIds[] = $row[Constants::$userId];
                        $tokenMapRoomIds[] = $row[Constants::$chatroomId];
                        $idTokenMap[] = array(Constants::$chatroomId => $row[Constants::$chatroomId],
                            Constants::$idToToken => array(array(Constants::$userId => $row[Constants::$userId],
                                Constants::$deviceTokens => array($row[Constants::$deviceToken])))
                        );
                    } else {
                        $deviceTokenAndIdPairExtractor(
                            $row,
                            $idTokenMap[array_search($row[Constants::$chatroomId], $tokenMapIds)][Constants::$idToToken],
                            $tokenMapIds
                        );
                    }
                };

                foreach($rows as $row) {
                    if(array_key_exists(Constants::$userId, $row) && array_key_exists(Constants::$deviceToken, $row)) {
                        if($groupedBySeparateChatroomIds) {
                            if(array_key_exists(Constants::$chatroomId, $row)) {
                                $roomIdToDeviceTokenAndIdPairExtractor($row, $idTokenMap, $tokenMapIds, $tokenMapRoomIds);
                            }
                        } else {
                            $deviceTokenAndIdPairExtractor($row, $idTokenMap,$tokenMapIds);
                        }
                    }
                }
                return $idTokenMap;
            }

            return null;
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

        private function parseTagsArrayRow(array $row, array& $tags, bool $keyTagsByRowId) {
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

        private function parseUniqueNumericArrayRow(array $row, array& $numeric, string $column, bool $keyNumericByRowId) {
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

        private function parseUniqueNumericArrayRowWithPredicate(array $row, array& $numeric, string $column, string $columnPredicate, bool $keyNumericByRowId) {
            if($row[$column] != null) {
                if($keyNumericByRowId) {
                    if(!in_array($row[$column],
                        (array_key_exists($row[Constants::$id], $numeric)
                            ? $numeric[$row[Constants::$id]] : array())) && $row[$columnPredicate]) {
                        $numeric[$row[Constants::$id]][] = $row[$column];
                    }
                } else if(!in_array($row[$column], $numeric) && $row[$columnPredicate]) {
                    $numeric[] = $row[$column];
                }
            }
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
                        $this->parseTagsArrayRow($row, $tags, $keyTagsByRowId);
                    }
                    if($parseNumericRow) {
                        $this->parseUniqueNumericArrayRow($row, $numeric, $column, $keyNumericByRowId);
                    }
                }
                if($parseTags && !$keyTagsByRowId) $tags = array_values(array_unique($tags, SORT_REGULAR));
            }
        }

        // might be the worst method I've written in my life
        private function extractTagsAndTwoUniqueNumericArrayWithPredicatesFromJoinQuery(array $rows, string $columnOne, string $columnTwo,
                                                                                        string $columnPredicate,
                                                                                        ?array& $tags, ?array& $numericOne, ?array& $numericTwo,
                                                                                        bool $keyTagsByRowId = false, bool $keyNumericOneByRowId = false,
                                                                                        bool $keyAssocByRowId = false) {
            $parseTags = $this->validateAllRowTags($rows);
            $parseNumericRowOne = $this->validateAllRowColumns($rows, $columnOne);
            $parseAssocRow = $this->validateAllRowColumns($rows, $columnTwo) &&
                $this->validateAllRowColumns($rows, $columnPredicate);

            if($parseTags) {
                $tags = array();
            }

            if($parseNumericRowOne) {
                $numericOne = array();
            }

            if($parseAssocRow) {
                $numericTwo = array();
            }

            if($parseTags || $parseNumericRowOne || $parseAssocRow) {
                foreach($rows as $row) {
                    if($parseTags) {
                        $this->parseTagsArrayRow($row, $tags, $keyTagsByRowId);
                    }
                    if($parseNumericRowOne) {
                        $this->parseUniqueNumericArrayRow($row, $numericOne, $columnOne, $keyNumericOneByRowId);
                    }
                    if($parseAssocRow) {
                        $this->parseUniqueNumericArrayRowWithPredicate($row, $numericTwo, $columnTwo, $columnPredicate, $keyAssocByRowId);
                    }
                }
                if($parseTags && !$keyTagsByRowId) $tags = array_values(array_unique($tags, SORT_REGULAR));
            }
        }

        private function isTagRowInvalid(array $row) {
            return !isset($row[Constants::$tagName]) ||
                !isset($row[Constants::$colour]) || !isset($row[Constants::$isFromFacebook]);
        }

        /** forwarding message functions outlined below should ALWAYS be called from transaction context **/

        private function forwardMessageToSocketServerOnCond(bool &$condition, int $chatroomId,
                                                            string $notificationType, Model $message, string $token) {
            if($condition) {
                $condition = $this->forwardMessageToSocketServer($chatroomId, $notificationType, $message, $token) != false;
            }
        }

        function forwardMessageToSocketServer(int $chatroomId,
                                              string $notificationType, Model $message, string $token) {
            return $this->forwarder->forwardRealtimeMessageToSocketServerWithRoomId($message, $chatroomId,
                $notificationType, $this->getIdAndDeviceTokenPairsForUsersInChatroom($chatroomId), $token);
        }

        private function forwardBatchedMessageToSocketServerOnCond(bool &$condition, array $chatroomIds,
                                                                    string $notificationType, Model $message, string $token) {
            if($condition) {
                $condition = $this->forwardBatchedMessageToSocketServer(
                    $chatroomIds,
                    $notificationType,
                    $message,
                    $token
                )  != false;
            }
        }

        function forwardBatchedMessageToSocketServer(array $chatroomIds, string $notificationType, Model $message, string $token) {
            return $this->forwarder->forwardRealtimeMessageToSecondaryServerWithRoomIdArray(
                $message,
                $chatroomIds,
                $notificationType,
                $this->getChatroomIdToIdAndDeviceTokenPairsForUsersInChatrooms($chatroomIds),
                $token
            );
        }

        private function forwardUserMessageToSocketServer(int $currentUserChatroomId, User $user,
                                                          string $type, string $chatMessage, string $token) {
            $this->forwarder->forwardRealtimeMessageToSocketServerWithRoomId($user,
                $currentUserChatroomId,
                $type,
                $this->getIdAndDeviceTokenPairsForUsersInChatroom($currentUserChatroomId),
                $token
            );

            $messageCreated = $this->createChatroomMessage(new Message(
                null,
                $chatMessage,
                null,
                $currentUserChatroomId,
                null
            ), $token);
            return isset($messageCreated);
        }

        private function forwardUserBatchedMessageToSocketServer(array $chatroomIds, User $user, string $type,
                                                                 string $chatMessage, string $token) {
            $this->forwarder->forwardRealtimeMessageToSecondaryServerWithRoomIdArray($user,
                $chatroomIds,
                $type,
                $this->getChatroomIdToIdAndDeviceTokenPairsForUsersInChatrooms($chatroomIds),
                $token
            );

            // BIG FIXME: Optimise sending of new message notifications to individual chatrooms too!
            foreach ($chatroomIds as $chatroomId) {
                $chatroomMessages[] = $this->createChatroomMessage(new Message(
                    null,
                    $chatMessage,
                    null,
                    $chatroomId,
                    null
                ), $token);
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

        private function removeFromArrayFieldOrDeleteDocByCountBoundary(
            \Google\Cloud\Firestore\DocumentSnapshot $doc, string $dataField,
            string $arrayFieldPath, array $elementsToRemove, int $countBoundary = 1
        ) {
            if(count($doc->data()[$dataField]) == $countBoundary) {
                $doc->reference()->delete();
            } else {
                $doc->reference()->update([
                    [
                        'path' => $arrayFieldPath,
                        'value' => \Google\Cloud\Firestore\FieldValue::arrayRemove($elementsToRemove)
                    ]
                ]);
            }
        }
    }
?>