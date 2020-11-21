<?php

        trait TagModel {
            private ?array $tags;

            public function getTags() {
                return $this->tags;
            }

            public function setTags(?array $tags) {
                $this->tags = $tags;
            }
        }
?>
