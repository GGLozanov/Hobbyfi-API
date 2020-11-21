<?php

    // TODO: Unnecessary?
    require_once("model.php");
   
    class Tag extends Model implements JsonSerializable {
        private string $name;
        private string $colour;

        public function __construct(string $name, string $colour) {
            $this->name = $name;
            $this->colour = $colour;
        }

        public function getUpdateQuery(string $password = null) {
            
        }

        public function getName() {
            return $this->name;
        }

        public function getColour() {
            return $this->colour;
        }

        public function jsonSerialize() {
            return [
                Constants::$name => $this->name,
                Constants::$colour => $this->colour
            ];
        }
    }

?>