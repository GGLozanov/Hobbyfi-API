<?php

    // TODO: Decide if mixin approach or class hierarchy is better
    class Chatroom extends Model {
        use \ExpandedModel;
        use \IdModel;
        use \ImageModel;

        private $ownerId;
        private $lastEventId;

        function __construct($id, $name, $description, $hasImage, $ownerId, $lastEventId) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            
            $this->ownerId = $ownerId;
            $this->lastEventId = $lastEventId;
        }

        function getUpdateQuery() {
            
        }
    }

?>