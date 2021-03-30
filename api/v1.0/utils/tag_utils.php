<?php
    require_once("../models/tag.php");

    class TagUtils {
        public static function extractTagsFromJson(?array $tags) {
            // TODO: Error handling for incorrect info here
            // example JSON structure for tags:
                // tags: [ {"name": "tag_name", "colour": "#FFFFFF", "isFromFacebook": true }, {"name": "tag_name2", "colour": "#FFFFFF" } ]
            if($tags == null) return array();

            return array_filter(array_map(function($tag) {
                if(!is_null($tag)) {
                    return TagUtils::mapTag(json_decode($tag, true));
                }
                return null;
            }, $tags));
        }

        public static function extractTagsFromSingleJson(?array $tags) {
            if($tags == null) return array();

            return array_filter(array_map(function($tag) {
                if(!is_null($tag)) {
                    return TagUtils::mapTag($tag);
                }
                return null;
            }, json_decode($tags[0], true)));
        }

        public static function mapTag(?array $tagArray) {
            if(is_null($array) || !array_key_exists(Constants::$name, $tagArray)
                    || !array_key_exists(Constants::$colour, $tagArray) || !array_key_exists(Constants::$isFromFacebook, $tagArray)) {
                return null;
            }

            return new Tag($tagArray[Constants::$name], $tagArray[Constants::$colour], $tagArray[Constants::$isFromFacebook]);
        }
    }
?>