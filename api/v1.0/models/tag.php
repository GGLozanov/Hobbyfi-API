<?php

    // TODO: Unnecessary?
    require_once("model.php");
   
    class Tag extends Model {
        private string $name;
        private string $colour;

        public function __construct(string $name, string $colour) {
            $this->name = $name;
            $this->colour = $colour;
        }

        public function getUpdateQuery() {
            
        }

        public function getName() {
            return $this->name;
        }

        public function getColour() {
            return $this->colour;
        }
    }

?>