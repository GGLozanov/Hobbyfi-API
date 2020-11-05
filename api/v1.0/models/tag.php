<?php

    class Tag extends Model {
        private $name;
        private $colour;

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