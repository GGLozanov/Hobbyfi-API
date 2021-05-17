<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once ("tag_model.php");
    require_once("image_model.php");
    require_once("../utils/image_utils.php");

    class Chatroom extends Model implements JsonSerializable {
        use \ExpandedModel;
        use \IdModel;
        use \ImageModel;
        use \TagModel;

        private ?int $ownerId;
        private ?array $eventIds;

        function __construct(
                int $id = null, 
                string $name = null, 
                string $description = null, 
                bool $hasImage = null, 
                int $ownerId = null, array $eventIds = null,
                array $tags = null) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            
            $this->ownerId = $ownerId;
            $this->eventIds = $eventIds;
            $this->tags = $tags;
        }

        function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE chatrooms SET";

            $updateColumns = array();
            $updateColumns[] = $this->addUpdateFieldToQuery($this->name != null, Constants::$name, $this->name);
            $updateColumns[] = $this->addUpdateFieldToQuery(isset($this->description), Constants::$description, $this->description);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->ownerId != null, Constants::$ownerId, $this->ownerId);

            $updateColumns = array_filter($updateColumns);

            $sql .= implode(',', $updateColumns) . " WHERE id = $this->id";

            return $sql;
        }

        public function isUpdateFormEmpty() {
            return $this->name == null
                && !isset($this->description);
        }

        function getOwnerId() {
            return $this->ownerId;
        }

        function setOwnerId(int $ownerId) {
            $this->ownerId = $ownerId;
        }

        function getEventIds() {
            return $this->eventIds;
        }

        function setEventIds(array $eventIds) {
            $this->eventIds = $eventIds;
        }

        public function jsonSerialize() {
            return [
                Constants::$id=>$this->id,
                Constants::$name=>$this->name,
                Constants::$description=>$this->description,
                Constants::$photoUrl=>$this->hasImage ? ImageUtils::getPublicContentDownloadUrl(
                        ImageUtils::getBucketLocationForChatroom($this->id), $this->id)
                    : null,
                Constants::$ownerId=>$this->ownerId,
                Constants::$eventIds=>$this->eventIds,
                Constants::$tags=>$this->tags
            ];
        }

        public function escapeStringProperties(mysqli $conn) {
            if(!is_null($this->name)) {
                $this->setName($conn->real_escape_string($this->getName()));
            }

            if(!is_null($this->description)) {
                $this->setDescription($conn->real_escape_string($this->getDescription()));
            }
        }
    }

?>