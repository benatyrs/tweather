<?php

class G {
    public static $DB;

    public static function init() {
        global $DB;
        self::$DB = $DB;
    }
}