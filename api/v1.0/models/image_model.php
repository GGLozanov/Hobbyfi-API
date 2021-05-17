<?php

    trait ImageModel {
        private ?bool $hasImage;

        public function getHasImage() {
            return $this->hasImage;
        }

        public function setHasImage(bool $hasImage = null) {
            $this->hasImage = $hasImage;
        }
    }

?>