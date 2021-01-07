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

        public function __construct(int $id = null,
                    string $name = null, string $description = null,
                    bool $hasImage = false,
                    string $startDate = null, string $date = null,
                    float $lat = null, float $long = null) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            $this->startDate = $startDate;
            $this->date = $date;
            $this->lat = $lat;
            $this->long = $long;
        }

        public function getUpdateQuery(string $userPassword = null) {
            
        }

        public function isUpdateFormEmpty() {
            return $this->name == null && !isset($this->description)
                && $this->hasImage == null && $this->date == null && $this->startDate == null
                && $this->lat == null && $this->long == null;
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

        public function jsonSerialize() {
            // TODO: Implement jsonSerialize() method.
        }

        public function escapeStringProperties(mysqli $conn) {
            // TODO: Implement escapeStringProperties() method.
        }
    }

?>