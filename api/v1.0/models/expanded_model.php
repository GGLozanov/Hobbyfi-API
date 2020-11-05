<?php

    // TODO: Rename
    trait ExpandedModel {
        private $name;
        private $description;

        public function getName() {
            return $this->name;
        }

        public function getDescription() {
            return $this->description;
        }

        public function setName($name) {
            $this->name = $name;
        }

        public function setDescription($description) {
            $this->description = $description;
        }
    }

?>