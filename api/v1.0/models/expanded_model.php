<?php

    // TODO: Rename
    trait ExpandedModel {
        private string $name;
        private string $description;

        public function getName() {
            return $this->name;
        }

        public function getDescription() {
            return $this->description;
        }

        public function setName(string $name = null) {
            $this->name = $name;
        }

        public function setDescription(string $description = null) {
            $this->description = $description;
        }
    }

?>