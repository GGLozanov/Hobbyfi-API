<?php

    // TODO: Decide if mixin approach or class hierarchy is better
    class Chatroom extends Model {
        use \ExpandedModel;
        use \IdModel;
        use \ImageModel;

        private int $ownerId;
        private int $lastEventId;

        function __construct(
                int $id = null, 
                string $name = null, 
                string $description = null, 
                bool $hasImage = null, 
                int $ownerId = null, int $lastEventId = null) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            
            $this->ownerId = $ownerId;
            $this->lastEventId = $lastEventId;
        }

        function getUpdateQuery(string $userPassword = null) {
            
        }
    }

?>