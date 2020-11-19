<?php

    class TagUtils {
        public static function extractTagsFromPostArray(array $tags) {
            $jsonTags = json_decode('{' . implode(',', $tags) . '}', true);
    
            // TODO: Error handling for incorrect info here
            // example JSON structure for tags:
                // tags: [ "tag_name" : { "colour" : "#FFFFFF" }, "tag_name2" : { "colour" : "#FFFFFF" } ]
            foreach($jsonTags as $name => $info) {      
                $newTags[] = new Tag($name, $info[0][Constants::$colour]);
            }

            return $newTags;
        }
    }

?>