<?php
    include_once "../consts/constants.php";

    class Database { 
        public $host = "localhost"; // to be changed if hosted on server
        public $user_name = "root";
        public $user_password = "";
        public $db_name = "hobbyfi_db";
        public $connection;

        function __construct() {
            $this->connection = mysqli_connect(
                $this->host,
                 $this->user_name, 
                 $this->user_password,
                  $this->db_name, "3308"
            );
        }

        public function closeConnection() {
            mysqli_close($this->connection);
        }

        public function createUser(User $user, ?string $password) {
            $this->connection->begin_transaction();
            $stmt = $this->connection->prepare("INSERT INTO users(id, email, username, password, description, has_image, user_chatroom_id) 
            VALUES(?, ?, ?, ?, ?, ?, ?)");

            // bind_param accepts only references... bruh...
            $id = $user->getId();
            $email = $user->getEmail();
            $name = $user->getName();
            $description = $user->getDescription();
            $hasImage = $user->getHasImage();
            $chatroomId = $user->getChatroomId();

            $stmt->bind_param("issssii", 
                $id, 
                $email,
                $name, 
                $password, 
                $description, 
                $hasImage, 
                $chatroomId);

            $insertSuccess = $stmt->execute();
            $userId = mysqli_insert_id($this->connection);

            $tagsInsertSuccess = $this->insertTagModel($user);
            $this->finishTransactionOnCond($userId);

            return $insertSuccess && $tagsInsertSuccess ? $userId : null;
        }

        public function userExistsOrPasswordTaken(string $username, $password) { // user exists if username or password are taken
            $stmt = $this->connection->prepare("SELECT username FROM users WHERE username = ? OR password = ?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();

            return mysqli_num_rows($stmt->get_result()) > 0; // if more than one row found => user exists
        }

        public function userExists(string $username) { // user exists if username or password are taken
            $stmt = $this->connection->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();

            return mysqli_num_rows($stmt->get_result()) > 0 ? "true" : "false"; // if more than one row found => user exists
        }

        public function validateUser(string $email, string $password) {
            $stmt = $this->connection->prepare("SELECT id, password FROM users WHERE email = ?"); // get associated user by email
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

        // TODO: Use transaction in multiple SELECT queries like these?
        public function getUser(int $id) { // user already auth'd at this point due to token => get user by id
            $user_result = $this->executeSingleIdParamStatement($id, "SELECT 
                us.description, us.username, us.email, us.has_image, us.user_chatroom_id, us_tags.tag_name, tgs.colour, tgs.is_from_facebook
                FROM users us
                LEFT JOIN user_tags us_tags ON us.id = us_tags.user_id
                LEFT JOIN tags tgs ON tgs.name LIKE us_tags.tag_name
                WHERE id = ?"
            )->get_result();
            
            if(mysqli_num_rows($user_result) > 0) {
                $rows = mysqli_fetch_all($user_result, MYSQLI_ASSOC); // fetch the resulting rows in the form of a map (associative array)

                return new User($id,
                    $rows[0][Constants::$email], $rows[0][Constants::$username],
                    $rows[0][Constants::$description], $rows[0][Constants::$hasImage],
                    $rows[0][Constants::$userChatroomId],
                    ($rows[0][Constants::$tagName] != null && $rows[0][Constants::$colour] 
                    != null && $rows[0][Constants::$isFromFacebook] != null) ? array_map(function(array $row) {
                        return new Tag($row[Constants::$tagName], $row[Constants::$colour], $row[Constants::$isFromFacebook]);
                    }, $rows) : null);
            }

            return null;
        }

        public function updateUser(User $user, ?string $password) {
            $this->connection->begin_transaction();
            $result = mysqli_query($this->connection, $user->getUpdateQuery($password));

            // FIXME: Code dup
            $userUpdateSuccess = mysqli_affected_rows($this->connection);
            $tagsUpdateSuccess = true;
            if($tags = $user->getTags()) { // somewhat unnecessary check given the method..
                $tagsUpdateSuccess = $this->updateModelTags(Constants::$userTagsTable, Constants::$userId, $user->getId(), $tags);
            }

            $updateSuccess = $userUpdateSuccess > 0 && $tagsUpdateSuccess;
            $this->finishTransactionOnCond($updateSuccess);

            return $updateSuccess;
        }

        public function deleteUser(int $id) {
            $stmt = $this->executeSingleIdParamStatement($id, "DELETE FROM users WHERE id = ?");

            ImageUtils::deleteImageFromPath($id, Constants::$userProfileImagesDir, Constants::$users);
            // FCM if user in chatroom => send notification

            return $stmt->affected_rows > 0;
        }
        
        // gets all users with any id BUT this one;
        public function getChatroomUsers(int $userId, int $multiplier = 1) { // multiplier starts from 1
            // FIXME: SQL Query limit param
            $stmt = $this->connection->prepare(
                "SELECT us.id, us.description, us.username, us.email, us.has_image, us_tags.tag_name, tgs.colour, tgs.is_from_facebook FROM 
                users us
                LEFT JOIN user_tags us_tags ON us_tags.user_id = us.id
                LEFT JOIN tags tgs ON tgs.name LIKE us_tags.tag_name
                WHERE id != ? AND us.user_chatroom_id = (SELECT user_chatroom_id WHERE id = ?) LIMIT 5 OFFSET 5*(? - 1)");
            $stmt->bind_param("iii", $userId, $userId, $multiplier);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

                $tags = $this->extractTagsFromJoinQuery($rows);

                return array_reduce($rows, function($result, array $row) use ($tags) {
                    if(!in_array($row[Constants::$id], $result)) {
                        $result[] = new User(
                            $row[Constants::$id],
                            $row[Constants::$email],
                            $row[Constants::$username],
                            $row[Constants::$description],
                            $row[Constants::$hasImage],
                            $row[Constants::$userChatroomId],
                            $tags[$row[Constants::$id]]
                        );
                    }
                    return $result;
                }, array());
            }

            return null;
        }

        public function createChatroom(Chatroom $chatroom) {
            $this->connection->begin_transaction();

            if($this->getUserChatroomId($chatroom->getOwnerId())) {
                $this->connection->rollback();
                return false;
            }

            $stmt = $this->connection->prepare("INSERT INTO chatrooms(id, name, description, has_image, owner_id, last_event_id) 
                VALUES(?, ?, ?, ?, ?, ?)");

            // still need to destruct this class and allocate more variables for this goddamn bind_param() method...
            $id = $chatroom->getId();
            $name = $chatroom->getName();
            $description = $chatroom->getDescription();
            $hasImage = $chatroom->getHasImage();
            $ownerId = $chatroom->getOwnerId();
            $lastEventId = $chatroom->getLastEventId();

            $stmt->bind_param("issiii",
                $id,
                $name,
                $description,
                $hasImage,
                $ownerId,
                $lastEventId
            );

            $insertSuccess = $stmt->execute();
            $chatroomId = mysqli_insert_id($this->connection);
            $tagsInsertSuccess = $this->insertTagModel($chatroom);
            $chatroomCreateSuccess = $insertSuccess && $tagsInsertSuccess;

            if($chatroomCreateSuccess) {
                $userUpdateSuccess = $this->setUserChatroomId($ownerId, $chatroomId);
                $this->finishTransactionOnCond($userUpdateSuccess);
                return $userUpdateSuccess ? $chatroomId : null; // FIXME: Expand upon errors and have them be more concise.
            }
            $this->connection->rollback();
            return null;
        }

        public function updateChatroom(int $ownerId, Chatroom $chatroom) {
            // handle user not owner error
            // FCM
            $this->connection->begin_transaction();
            if(!($chatroomId = $this->getOwnerChatroomId($ownerId))) {
                $this->connection->rollback();
                return false;
            }

            $chatroom->setId($chatroomId);

            mysqli_query($this->connection, $chatroom->getUpdateQuery());

            // FIXME: Code dup with updateUser
            $chatroomUpdateSuccess = mysqli_affected_rows($this->connection);
            $tagsUpdateSuccess = true;
            if($tags = $chatroom->getTags()) { // somewhat unnecessary check given the method..
                $tagsUpdateSuccess = $this->updateModelTags(
                    Constants::$chatroomTagsTable, Constants::$chatroomId,
                    $chatroom->getId(), $tags);
            }

            // fixme: small code dup
            $updateSuccess = $chatroomUpdateSuccess > 0 && $tagsUpdateSuccess;
            $this->finishTransactionOnCond($updateSuccess);

            return $updateSuccess;
        }

        public function deleteChatroom(int $ownerId) {
            // handle user not owner error
            $this->connection->begin_transaction();
            if(!($chatroomId = $this->getOwnerChatroomId($ownerId))) {
                return false;
            }

            // FCM - delete chatroom
            $stmt = $this->executeSingleIdParamStatement($chatroomId, "DELETE FROM chatrooms WHERE id = ?");

            return $stmt->affected_rows > 0;
        }

        public function getChatroom(int $userId) {
            if(!($chatroomId = $this->getUserChatroomId($userId))) {
                return false; // users should only get a single chatroom from here (theirs) and nothing else
            }

            // FIXME: Code dup with getChatrooms!
            $result = $this->executeSingleIdParamStatement($chatroomId,
                "SELECT `ch`.`id`, `ch`.`name`, `ch`.`description`, 
                        `ch`.`has_image`, `ch`.`owner_id`, `ch`.`last_event_id`
                        `ch_tags`.`tag_name`, `tgs`.`colour`, `tgs`.`is_from_facebook`
                    FROM chatrooms `ch`
                    LEFT JOIN chatroom_tags `ch_tags` ON `ch`.`id` = `ch_tags`.`chatroom_id` 
                    LEFT JOIN tags `tgs` ON `ch_tags`.`tag_name` LIKE `tgs`.`name`
                    WHERE `ch`.`id` = ?"
            )->get_result();

            if($result && mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result);
                $tags = $this->extractTagsFromJoinQuery($rows);

                return new Chatroom(
                    $rows[0][Constants::$id],
                    $rows[0][Constants::$name],
                    $rows[0][Constants::$description],
                    $rows[0][Constants::$hasImage],
                    $rows[0][Constants::$ownerId],
                    $rows[0][Constants::$lastEventId],
                    $tags
                );
            }

            return null;
        }

        public function getChatrooms(int $userId, int $multiplier) {
            if($chatroomId = $this->getUserChatroomId($userId)) {
                return false;
            }

            $multiplier = 5*($multiplier - 1);
            $stmt = $this->connection->prepare(
                "SELECT `s`.`id`, `s`.`name`, `s`.`description`, 
                    `s`.`has_image`, `s`.`owner_id`, `s`.`last_event_id`, 
                    `ch_tags`.`tag_name`, `tgs`.`is_from_facebook`, 
                    `tgs`.`colour` FROM 
                        (SELECT `ch`.`id`, `ch`.`name`, 
                        `ch`.`description`, `ch`.`has_image`, 
                        `ch`.`owner_id`, `ch`.`last_event_id`
                         FROM chatrooms `ch` LIMIT 5 OFFSET ?) as `s` 
                    LEFT JOIN chatroom_tags `ch_tags` ON `s`.`id` = `ch_tags`.`chatroom_id` 
                    LEFT JOIN tags `tgs` ON `ch_tags`.`tag_name` LIKE `tgs`.`name`"
            );

            $stmt->bind_param("i", $multiplier);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $tags = $this->extractTagsFromJoinQuery($rows);

                $chatrooms = array_reduce($rows, function($result, array $row) use($tags) {
                    if(array_key_exists($row[Constants::$id], $result)) {
                        return $result;
                    }

                    $result[$row[Constants::$id]] = new Chatroom(
                        $row[Constants::$id],
                        $row[Constants::$name],
                        $row[Constants::$description],
                        $row[Constants::$hasImage],
                        $row[Constants::$ownerId],
                        $row[Constants::$lastEventId],
                        $tags[$row[Constants::$id]]
                    );
                    return $result;
                }, array());

                return array_values($chatrooms);
            }

            $this->connection->rollback();
            return null;
        }

        public function getChatroomMessages(int $userId, int $multiplier) {
            // handle user not in chatroom error
        }

        public function createChatroomMessage(int $ownerId, Message $message) {
            // handle user not in chatroom error
            // FCM
        }

        public function deleteChatroomMessage(int $ownerId, int $messageId) {
            // assert message owner OR chatroom owner

            // FCM
        }

        public function updateChatroomMessage(int $ownerId, int $messageId, Message $message) {
            // assert message owner & correct chatroom
            // FCM
        }

        // chatroom id is sent through query param
        public function createChatroomEvent(int $ownerId, Event $event) {
            // assert chatroom owner and update
            // update chatroom lastEventId
            // FCM
        }

        public function deleteChatroomEvent(int $ownerId) {
            // assert chatroom owner & delete
            // FCM
        }

        public function updateChatroomEvent(int $ownerId, Event $event) {
            // assert chatroom owner & update
            // FCM
        }
        
        public function updateModelTags(string $table, string $modelColumn, int $modelId, ?array $tags) {
            // INSERT tag if not in tags table => skip otherwise
            // REPLACE query - all user tags
            if(!$tags) {
                return false;
            }

            $result = $this->connection->query($this->getTagArrayReplaceQuery($table, $modelColumn, $modelId, $tags));

            return mysqli_affected_rows($this->connection) > 0 || mysqli_num_rows($result) > 0;
        }

        // TODO: Model CRUD operations here

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
                $tags[$row[Constants::$id]][] =
                    new Tag($row[Constants::$tagName], $row[Constants::$colour], $row[Constants::$isFromFacebook]);
            }
            return $tags;
        }

        // should be always called in transaction context for CRUD methods
        private function getUserChatroomId(int $userId) {
            $result = $this->executeSingleIdParamStatement($userId, "SELECT user_chatroom_id FROM users WHERE id = ?")
                ->get_result();
            if($result && ($row = $result->fetch_assoc()) != null) {
                return array_key_exists(Constants::$userChatroomId, $row) ?
                    $row[Constants::$userChatroomId] : false;
            }

            return false;
        }

        // should be always called in transaction context for CRUD methods
        private function getOwnerChatroomId(int $userId) {
            $result = $this->executeSingleIdParamStatement($userId, "SELECT chrms.id FROM chatrooms chrms
                INNER JOIN users usrs ON usrs.user_chatroom_id = chrms.id
                WHERE chrms.owner_id = ?")->get_result();

            if($result && ($row = $result->fetch_assoc()) != null) {
                return array_key_exists(Constants::$id, $row) ?
                    $row[Constants::$id] : false; // first row is count at first column - the value of said count
            }

            return false;
        }

        private function insertTagModel(Model $model) {
            $tagsInsertSuccess = true;
            if($model->getTags()) { // insert user tags here
                $tagsInsertSuccess = $this->connection->query($this->getTagArrayInsertQuery($model->getTags()));
            }

            return $tagsInsertSuccess;
        }

        private function finishTransactionOnCond(bool $condition) {
            if($condition) {
                $this->connection->commit();
            } else $this->connection->rollback();
        }

        private function setUserChatroomId(int $id, int $chatroomId) {
            $stmt = $this->connection->prepare("UPDATE users SET user_chatroom_id = ? WHERE id = ?");

            $stmt->bind_param("ii", $chatroomId, $id);
            $stmt->execute();

            return $stmt->affected_rows >= 0; // TODO: Check if this still works properly
        }

        public function setModelHasImage(int $id, bool $hasImage, string $table) {
            $stmt = $this->connection->prepare("UPDATE $table SET has_image = ? WHERE id = ?");

            $stmt->bind_param("ii", $hasImage, $id);
            $stmt->execute();

            return $stmt->affected_rows >= 0; // TODO: Check if this still works properly
        }
    }
?>