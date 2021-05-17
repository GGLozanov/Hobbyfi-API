<?php

    class ModelUtils {
        public static function arrayContainsIdValue(array $objects, $value) {
            foreach($objects as $object) {
                if(property_exists($object, Constants::$id) && $object->getId() === $value) {
                    return true;
                }
            }
            return false;
        }
    }
