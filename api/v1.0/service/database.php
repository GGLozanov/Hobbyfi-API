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

            // TODO: insert tags here

            return mysqli_insert_id($this->connection); // TODO: Check if this still works properly
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

        public function getUser(int $id) { // user already auth'd at this point due to token => get user by id
            $stmt = $this->connection->prepare("SELECT description, username, email, has_image FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $result = $stmt->get_result();

            // TODO: JOIN for tags
        
            if(mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result); // fetch the resulting rows in the form of a map (associative array)
                
                return new User($id, $row['email'], $row['username'], $row['description'], $row['has_image'], $row['user_chatroom_id']);
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

            return $stmt->affected_rows >= 0;
        }
        
        // gets all users with any id BUT this one;
        // TODO: Implement multiplier paging functionality with limit & offset (offset given in request query parameters)
        public function getUsers(int $id, int $multiplier) {
            $stmt = $this->connection->prepare(
                "SELECT id, description, username, email, has_image FROM users WHERE id != ? LIMIT 5 OFFSET 5*$multiplier"); // TODO: add more fetching client-side functionality through small fetches
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $result = $stmt->get_result();

            if(mysqli_num_rows($result) > 0) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

                return array_map(function(array $row) {
                    return new User($row['id'], $row['email'], $row['username'], $row['description'], $row['has_image'], $row['user_chatroom_id']);
                }, $rows); // might bug out with the mapping here FIXME
            }

            return null;
        }


        // TODO: Model CRUD operations here
    }
?>