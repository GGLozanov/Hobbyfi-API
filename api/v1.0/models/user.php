<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once("image_model.php");
    require_once ("tag_model.php");
    require_once("../init.php");

    class User extends Model {
        use \IdModel;
        use \ImageModel;
        use \ExpandedModel;
        use \TagModel;
        
        private ?string $email;

        private ?int $chatroomId; // FK

        public function __construct(
                int $id = null, 
                string $email = null, 
                string $username = null, 
                string $description = null, bool $hasImage = null, 
                int $chatroomId = null, 
                array $tags = null) {     
            $this->id = $id;
            $this->email = $email;
            $this->name = $username;
            $this->description = $description;
            $this->hasImage = $hasImage;

            $this->chatroomId = $chatroomId;
            $this->tags = $tags;
        }

        // this method is called for an update user to get the query for its existing/non-null fields
        public function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE users SET";

            $updateColumns = array();
            $updateColumns[] = $this->addUpdateFieldToQuery($this->email != null, Constants::$email, $this->email);
            $updateColumns[] = $this->addUpdateFieldToQuery($userPassword != null, Constants::$password, $userPassword);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->name != null, Constants::$username, $this->name);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->description != null, Constants::$description, $this->description);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->chatroomId != null, Constants::$userChatroomId, $this->chatroomId);

            $updateColumns = array_filter($updateColumns);

            $sql .= implode(',', $updateColumns) . " WHERE id = $this->id";

            return $sql;
        }

        public function getEmail() {
            return $this->email;
        }

        public function setEmail(string $email = null) {
            $this->email = $email;
        }

        public function getChatroomId() {
            return $this->chatroomId;
        }

        public function setChatroomId(int $chatroomId = null) {
            $this->chatroomId = $chatroomId;
        }
    }
?>