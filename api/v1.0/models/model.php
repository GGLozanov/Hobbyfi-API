<?php
    abstract class Model {

        protected function addUpdateFieldToQuery(bool $fieldNull, string $field, $value) {
            $isValueString = is_string($value);
            if($fieldNull) {
                return $isValueString ? " $field = '$value'" : " $field = $value";
            }
            return null;
        }

        // only User model does something with the argument, the rest can ignore it
        abstract public function getUpdateQuery(string $userPassword = null);
    }
?>