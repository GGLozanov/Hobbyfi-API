<?php
    require_once("model.php");
   
    class Tag extends Model implements JsonSerializable {
        private string $name;
        private string $colour;
        private bool $isFromFacebook;

        public function __construct(string $name, string $colour, bool $isFromFacebook = false) {
            $this->name = $name;
            $this->colour = $colour;
            $this->isFromFacebook = $isFromFacebook;
        }

        public function getUpdateQuery(string $password = null) {
            
        }

        public function isUpdateFormEmpty() {
            return $this->name == null && $this->colour == null && $this->isFromFacebook == null;
        }

        public function getName() {
            return $this->name;
        }

        public function setName($name) {
            $this->name = $name;
        }

        public function getColour() {
            return $this->colour;
        }

        public function setColour($colour) {
            $this->colour = $colour;
        }

        public function setIsFromFacebook($isFromFacebook) {
            $this->isFromFacebook = $isFromFacebook;
        }

        public function isFromFacebook() {
            return $this->isFromFacebook;
        }

        public function jsonSerialize() {
            return [
                Constants::$name => $this->name,
                Constants::$colour => $this->colour,
                Constants::$isFromFacebook => $this->isFromFacebook
            ];
        }

        public function escapeStringProperties(mysqli $conn) {
            if(!is_null($this->name)) {
                $this->setName($conn->real_escape_string($this->getName()));
            }

            if(!is_null($this->colour)) {
                $this->setColour($conn->real_escape_string($this->getColour()));
            }
        }
    }

?>