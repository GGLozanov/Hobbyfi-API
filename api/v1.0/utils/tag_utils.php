<?php
    require_once("../models/tag.php");

    class TagUtils {
        public static function extractTagsFromJson(?array $tags) {
            // TODO: Error handling for incorrect info here
            // example JSON structure for tags:
                // tags: [ {"name": "tag_name", "colour": "#FFFFFF", "isFromFacebook": true }, {"name": "tag_name2", "colour": "#FFFFFF" } ]

            if($tags == null) return null;

            $newTags = array();
            foreach($tags as $tag) {
                $jsonTag = json_decode($tag, true);
                $newTags[] = new Tag($jsonTag[Constants::$name], $jsonTag[Constants::$colour], $jsonTag[Constants::$isFromFacebook]);
            }

            return $newTags;
        }
    }

?>