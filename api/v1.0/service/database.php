<?php
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

        public function createUser(User $user, string $password) {
            $stmt = $this->connection->prepare("INSERT INTO users(id, email, username, password, description, has_image, user_chatroom_id) 
            VALUES(NULL, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("issssii", 
                $user->getId(), 
                $user->getEmail(),
                $user->getName(), 
                $password, 
                $user->getDescription(), 
                $user->getHasImage(), 
                $user->getChatroomId());

            $stmt->execute();

            $userId = mysqli_insert_id($this->connection);

            if($user->getTags()) { // insert user tags here
                $this->connection->query($this->getTagArrayInsertQuery($user->getTags()));
            }

            return $userId;
        }

        public function setUserHasImage(int $id, bool $hasImage) {
            $stmt = $this->connection->prepare("UPDATE users SET has_image = ? WHERE id = ?");
            
            $stmt->bind_param("ii", $hasImage, $id);
            $stmt->execute();

            return $stmt->affected_rows >= 0; // TODO: Check if this still works properly
        }

        public function userExistsOrPasswordTaken(string $username, string $password) { // user exists if username or password are taken
            $stmt = $this->connection->prepare("SELECT username FROM users WHERE username = ? OR password = ?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();

            return mysqli_num_rows($stmt->get_result()) > 0; // if more than one row found => user exists
        }

        public function validateUser(string $email, string $password) {
            $stmt = $this->connection->prepare("SELECT id, password FROM users WHERE email = ?"); // get associated user by email
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0 && ($rows = mysqli_fetch_all($result, MYSQLI_ASSOC))) {
                    
                $filteredRows = array_filter($rows, function (array $row) use ($password) {
                    return password_verify($password, $row['password']);
                });

                if(count($filteredRows) && $row = $filteredRows[0]) {
                    return $row['id'];
                } // if more than one row found AND the passwords match => auth'd user => return id for token
            }

            return null; // else return nothing and mark user as unauth'd
        }

        // TODO: Use transaction in multiple SELECT queries like these?
        public function getUser(int $id) { // user already auth'd at this point due to token => get user by id
            $user_result = $this->executeSingleUserIdParamStatement($id, "SELECT description, username, email, has_image, user_chaatroom_id 
                FROM users
                WHERE id = ?"
            )->get_result();
            
            if(mysqli_num_rows($user_result) > 0) {
                $row = mysqli_fetch_assoc($user_result); // fetch the resulting rows in the form of a map (associative array)

                $tags = $this->getTagsByUserId($id);
                return new User($id, $row['email'], $row['username'], $row['description'], $row['has_image'], $row['user_chatrom_id'], $tags);              
            }

            return null;
        }

        // only place void of prepared stmtns
        public function updateUser(User $user, string $password) {
            $result = mysqli_query($this->connection, $user->getUpdateQuery($password));

            return mysqli_affected_rows($this->connection) >= 0;
        }

        public function deleteUser(int $id) {
            $stmt = $this->connection->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);

            $stmt->execute();

            return $stmt->affected_rows > 0;
        }
        
        // gets all users with any id BUT this one;
        // TODO: Implement multiplier paging functionality with limit & offset (offset given in request query parameters)
        public function getChatroomUsers(int $id, int $multiplier) {
            $stmt = $this->connection->prepare(
                "SELECT id, description, username, email, has_image FROM 
                users 
                WHERE id != ? AND user_chatroom_id = (SELECT user_chatroom_id WHERE id = ?) LIMIT 5 OFFSET 5*$multiplier");
            $stmt->bind_param("ii", $id, $id);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

                return array_map(function(array $row) {
                    $tags = $this->getTagsByUserId($row['id']);
                    
                    return new User($row['id'], $row['email'], $row['username'], $row['description'], $row['has_image'], $row['user_chatroom_id'], $tags);
                }, $rows); // might bug out with the mapping here FIXME
            }

            return null;
        }

        // TODO: Assert these requests have authority to be performed in db
        public function createChatroom(int $ownerId, Chatroom $chatroom) {
        
        }

        public function updateChatroom(int $ownerId, int $chatroomId, Chatroom $chatroom) {
            // handle user not owner error
        }

        public function deleteChatroom(int $ownerId, int $chatroomId) {
            // handle user not owner error
        }

        public function getChatrooms(int $userId, int $multiplier) {

        }

        public function getChatroomMessages(int $userId, int $multiplier) {
            // handle user not in chatroom error
        }

        public function createChatroomMessage(int $userId, Message $message) {
            // handle user not in chatroom error
        }

        public function deleteChatroomMessage(int $messageId) {
            // assert message owner OR chatroom owner
        }

        public function updateChatroomMessage(int $userId, int $messageId, Message $message) {
            // assert message owner & correct chatroom
        }

        public function createChatroomEvent(int $ownerId, Event $event) {
            // assert chatroom owner and update
        }

        public function deleteChatroomEvent(int $ownerId, int $eventId) {
            // assert chatroom owner & delete
        }

        public function updateChatroomEvent(int $ownerId, int $eventId, Event $event) {
            // assert chatroom owner & update
        }

        public function updateUserTags(int $userId, array $tags) {
            // INSERT tag if not in tags table => skip otherwise
            // REPLACE query - all user tags
            if(!$tags) {
                return false;
            }

            $userTagsStmt = $this->executeSingleUserIdParamStatement($userId, $this->getUserTagArrayReplaceQuery($userId, $tags));

            return $userTagsStmt->affected_rows > 0;
        }

        // TODO: Model CRUD operations here

        private function executeSingleUserIdParamStatement(int $userId, string $sql) {
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        
            return $stmt;
        }

        // TODO: this doesn't use prepared statements either
        private function getTagArrayInsertQuery(array $tags) {
            $dataArray = array_map(function($tag) {
                $name = $this->connection->real_escape_string($tag->getName());
                $colour = $this->connection->real_escape_string($tag->getColour());
        
                return "('$name', '$colour')";
            }, $tags);
        
            $sql = "INSERT IGNORE INTO tags (name, colour) VALUES "; // IGNORE ignores duplicate key errors and doesn't insert tags w/ same name
            $sql .= implode(',', $dataArray);
        
            return $sql;
        }

        private function getUserTagArrayReplaceQuery(int $userId, array $tags) {
            $dataArray = array_map(function($tag) use($userId) {
                $name = $this->connection->real_escape_string($tag->getName());
        
                return "('$userId', '$name')";
            }, $tags);
        
            $sql = "REPLACE INTO user_tags (user_id, tag_name) VALUES "; 
            $sql .= implode(',', $dataArray);
        
            return $sql;
        }

        private function getTagsByUserId(int $userId) {
            $tags_result = $this->executeSingleUserIdParamStatement($userId, "SELECT tag_name, colour FROM user_tags us_tags
            INNER JOIN tags ts ON us_tags.tag_name LIKE ts.name
            WHERE user_id = ?")->get_result();

            if(mysqli_num_rows($tags_result) > 0) {
                $rows = mysqli_fetch_assoc($tags_result);                

                return array_map(function(array $row) {
                    return new Tag($row['name'], $row['colour']);    
                }, $rows);
            }

            return null;
        }
    }
?>