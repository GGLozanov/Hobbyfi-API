<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once ("tag_model.php");
    require_once("image_model.php");

    class Chatroom extends Model implements JsonSerializable {
        use \ExpandedModel;
        use \IdModel;
        use \ImageModel;
        use \TagModel;

        private ?int $ownerId;
        private ?int $lastEventId;

        function __construct(
                int $id = null, 
                string $name = null, 
                string $description = null, 
                bool $hasImage = null, 
                int $ownerId = null, int $lastEventId = null,
                array $tags = null) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            
            $this->ownerId = $ownerId;
            $this->lastEventId = $lastEventId;
            $this->tags = $tags;
        }

        function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE chatrooms SET";

            $updateColumns = array();
            $updateColumns[] = $this->addUpdateFieldToQuery($this->name != null, Constants::$name, $this->name);
            $updateColumns[] = $this->addUpdateFieldToQuery(isset($this->description), Constants::$description, $this->description);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->ownerId != null, Constants::$ownerId, $this->ownerId);
            $updateColumns[] = $this->addUpdateFieldToQuery(isset($this->lastEventId), Constants::$lastEventId, $this->lastEventId);

            $updateColumns = array_filter($updateColumns);

            $sql .= implode(',', $updateColumns) . " WHERE id = $this->id";

            return $sql;
        }

        public function isUpdateFormEmpty() {
            return $this->name == null
                && !isset($this->description) && $this->ownerId == null &&
                !isset($this->lastEventId);
        }

        function getOwnerId() {
            return $this->ownerId;
        }

        function setOwnerId(int $ownerId) {
            $this->ownerId = $ownerId;
        }

        function getLastEventId() {
            return $this->lastEventId;
        }

        function setLastEventId(int $lastEventId) {
            $this->lastEventId = $lastEventId;
        }

        public function jsonSerialize() {
            return [
                Constants::$id=>$this->id,
                Constants::$name=>$this->name,
                Constants::$description=>$this->description,
                Constants::$photoUrl=>$this->hasImage ?
                    (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . ':'
                    . $_SERVER['SERVER_PORT'] .'/Hobbyfi-API/uploads/' . Constants::userProfileImagesDir($this->id)
                    : null,
                Constants::$ownerId=>$this->ownerId,
                Constants::$lastEventId=>$this->lastEventId,
                Constants::$tags=>$this->tags
            ];
        }
    }

?>