<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once("image_model.php");
    require_once("tag_model.php");

    class User extends Model implements JsonSerializable {
        use \IdModel;
        use \ImageModel;
        use \ExpandedModel;
        use \TagModel;
        
        private ?string $email;

        private ?array $chatroomIds;

        public function __construct(
                int $id = null, 
                string $email = null, 
                string $username = null, 
                string $description = null, bool $hasImage = null,
                array $chatroomIds = null,
                array $tags = null) {
            $this->id = $id;
            $this->email = $email;
            $this->name = $username;
            $this->description = $description;
            $this->hasImage = $hasImage;
            $this->chatroomIds = $chatroomIds;
            $this->tags = $tags;
        }

        public function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE users SET";

            $updateColumns = array();
            $updateColumns[] = $this->addUpdateFieldToQuery($this->email != null, Constants::$email, $this->email);
            $updateColumns[] = $this->addUpdateFieldToQuery($userPassword != null, Constants::$password, $userPassword);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->name != null, Constants::$username, $this->name);
            $updateColumns[] = $this->addUpdateFieldToQuery(isset($this->description), Constants::$description, $this->description);

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

        public function getChatroomIds() {
            return $this->chatroomIds;
        }

        public function setChatroomIds(array $chatroomId = null) {
            $this->chatroomIds = $chatroomId;
        }

        public function isUpdateFormEmpty() {
            return $this->email == null && $this->name == null
                && !isset($this->description) && !isset($this->chatroomId);
        }

        public function escapeStringProperties(mysqli $conn) {
            if(!is_null($this->email)) {
                $this->setEmail($conn->real_escape_string($this->getEmail()));
            }

            if(!is_null($this->name)) {
                $this->setName($conn->real_escape_string($this->getName()));
            }

            if(!is_null($this->description)) {
                $this->setDescription($conn->real_escape_string($this->getDescription()));
            }
        }

        public function jsonSerialize() {
            return [
                Constants::$id=>$this->id,
                Constants::$email=>$this->email,
                Constants::$username=>$this->name,
                Constants::$description=>$this->description,
                Constants::$chatroomIds=>$this->chatroomIds,
                Constants::$photoUrl=>$this->hasImage ?
                    Constants::getPhotoUrlForDir(Constants::userProfileImagesDir($this->id))
                    : null,
                Constants::$tags=>$this->getTags()
            ];
        }
    }
?>