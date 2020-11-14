<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once("image_model.php");

    class Event extends Model {
        use \IdModel;
        use \ExpandedModel;
        use \ImageModel;
    
        private string $startDate;
        private string $date;

        private float $lat;
        private float $long;

        public function __construct() {
            
        }

        public function getUpdateQuery(string $userPassword = null) {
            
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
    }

?>