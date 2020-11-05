<?php

    trait ImageModel {
        private $hasImage;

        public function getHasImage() {
            return $this->hasImage;
        }

        public function setHasImage($hasImage) {
            $this->hasImage = $hasImage;
        }
    }

?>