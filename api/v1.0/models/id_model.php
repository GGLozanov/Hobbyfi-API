<?php

    trait IdModel {
        private ?int $id;

        public function getId() {
            return $this->id;
        }

        public function setId($id = null) {
            $this->id = $id;
        }
    }

?>