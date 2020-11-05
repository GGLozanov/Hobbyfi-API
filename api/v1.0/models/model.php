<?php
    abstract class Model {
        protected function addUpdateFieldToQuery(bool $fieldNull, string &$sql, int &$commaCount, string $field, $value, bool $isFirstField) {
            $isValueString = is_string($value);
            if($fieldNull) {
                if($commaCount > 0 && !$isFirstField) {
                    $sql .= $isValueString ?  ", $field = '$value'" : ", $field = $value";
                    $commaCount--;
                } else
                    $sql .= $isValueString ? " $field = '$value'" : " $field = $value";
            }
        }

        // only User model does something with the argument, the rest can ignore it
        abstract public function getUpdateQuery(string $userPassword = null);
    }
?>