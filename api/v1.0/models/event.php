<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once("image_model.php");

    class Event extends Model implements JsonSerializable {
        use \IdModel;
        use \ExpandedModel;
        use \ImageModel;
    
        private ?string $startDate;
        private ?string $date;

        private ?float $lat;
        private ?float $long;

        private ?int $chatroomId;

        public function __construct(int $id = null,
                    string $name = null, string $description = null,
                    bool $hasImage = false,
                    string $startDate = null, string $date = null,
                    float $lat = null, float $long = null, int $chatroomId = null) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            $this->startDate = $startDate;
            $this->date = $date;
            $this->lat = $lat;
            $this->long = $long;
            $this->chatroomId = $chatroomId;
        }

        public function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE events SET";

            $updateColumns = array();
            $updateColumns[] = $this->addUpdateFieldToQuery($this->name != null, Constants::$name, $this->name);
            $updateColumns[] = $this->addUpdateFieldToQuery(isset($this->description), Constants::$description, $this->description);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->date != null, Constants::$startDate, $this->date);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->lat != null, Constants::$lat, $this->lat);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->long != null, Constants::$long, $this->long);
            $updateColumns[] = $this->addUpdateFieldToQuery($this->chatroomId != null, Constants::$chatroomId, $this->chatroomId);

            $updateColumns = array_filter($updateColumns);
            $sql .= implode(',', $updateColumns) . " WHERE id = $this->id";
            return $sql;
        }

        public function isUpdateFormEmpty() {
            return $this->name == null && !isset($this->description)
                && $this->hasImage == null && $this->date == null && $this->startDate == null
                && $this->lat == null && $this->long == null && $this->chatroomId == null;
        }

        public function getStartDate() {
            return $this->startDate;
        }

        public function getDate() {
            return $this->date;
        }

        public function getLat() {
            return $this->lat;
        }

        public function getLong() {
            return $this->long;
        }

        public function getChatroomId() {
            return $this->chatroomId;
        }

        public function setStartDate(string $startDate = null) {
            $this->startDate = $startDate;
        }

        public function setDate(string $date = null) {
            $this->date = $date;
        }

        public function setLat(float $lat = null) {
            $this->lat = $lat;
        }

        public function setLong(float $long = null) {
            $this->long = $long;
        }

        public function setChatroomId(int $chatroomId = null) {
            $this->chatroomId = $chatroomId;
        }

        public function jsonSerialize() {
            return [
                Constants::$id=>$this->id,
                Constants::$name=>$this->name,
                Constants::$description=>$this->description,
                Constants::$photoUrl=>$this->hasImage ?
                    (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . ':'
                    . $_SERVER['SERVER_PORT'] .'/Hobbyfi-API/uploads' . Constants::chatroomEventImagesDir($this->id)
                    . "/" . $this->id . ".jpg"
                    : null,
                Constants::$startDate=>$this->startDate,
                Constants::$date=>$this->date,
                Constants::$lat=>$this->lat,
                Constants::$long=>$this->long,
                Constants::$chatroomId=>$this->chatroomId
            ];
        }

        public function escapeStringProperties(mysqli $conn) {
            if(!is_null($this->name)) {
                $this->setName($conn->real_escape_string($this->getName()));
            }

            if(!is_null($this->description)) {
                $this->setDescription($conn->real_escape_string($this->getDescription()));
            }

            if(!is_null($this->date)) {
                $this->setDate($conn->real_escape_string($this->getStartDate()));
            }
        }
    }

?>