<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once ("tag_model.php");
    require_once("image_model.php");

    // TODO: Decide if mixin approach or class hierarchy is better
    class Chatroom extends Model {
        use \ExpandedModel;
        use \IdModel;
        use \ImageModel;
        use \TagModel;

        private int $ownerId;
        private int $lastEventId;

        function __construct(
                int $id = null, 
                string $name = null, 
                string $description = null, 
                bool $hasImage = null, 
                int $ownerId = null, int $lastEventId = null,
                array $tags = null) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
            $this->hasImage = $hasImage;
            
            $this->ownerId = $ownerId;
            $this->lastEventId = $lastEventId;
            $this->tags = $tags;
        }

        function getUpdateQuery(string $userPassword = null) {
            
        }
    }

?>