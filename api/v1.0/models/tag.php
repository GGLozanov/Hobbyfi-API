<?php

    // TODO: Unnecessary?
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

        public function getName() {
            return $this->name;
        }

        public function getColour() {
            return $this->colour;
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
    }

?>