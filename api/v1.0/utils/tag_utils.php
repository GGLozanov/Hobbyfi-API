<?php
    require_once("../models/tag.php");

    class TagUtils {
        public static function extractTagsFromJson(?array $tags) {
            // TODO: Error handling for incorrect info here
            // example JSON structure for tags:
                // tags: [ {"name": "tag_name", "colour": "#FFFFFF", "isFromFacebook": true }, {"name": "tag_name2", "colour": "#FFFFFF" } ]

            $newTags = array();
            if($tags == null) return $newTags;

            foreach($tags as $tag) {
                $jsonTag = json_decode($tag, true);
                if(!array_key_exists(Constants::$name, $jsonTag)
                    || !array_key_exists(Constants::$colour, $jsonTag) || !array_key_exists(Constants::$isFromFacebook, $jsonTag)) {
                    continue;
                }

                $newTags[] = new Tag($jsonTag[Constants::$name], $jsonTag[Constants::$colour], $jsonTag[Constants::$isFromFacebook]);
            }

            return $newTags;
        }
    }

?>