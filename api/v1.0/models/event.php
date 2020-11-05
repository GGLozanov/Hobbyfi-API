<?php

    class Event extends Model {
        use \IdModel;
        use \ExpandedModel;
        use \ImageModel;
    
        private $startDate;
        private $date;

        private $lat;
        private $long;

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

        public function setStartDate($startDate) {
            $this->startDate = $startDate;
        }

        public function setDate($date) {
            $this->date = $date;
        }

        public function setLat($lat) {
            $this->lat = $lat;
        }

        public function setLong($long) {
            $this->long = $long;
        }
    }

?>