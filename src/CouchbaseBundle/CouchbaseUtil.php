<?php

namespace CouchbaseBundle;

class CouchbaseUtil
{

    public static function dateToArray($date)
    {
        $ret = [
            (int)$date->format("Y"),
            (int)$date->format("n"), // month without begining 0
            (int)$date->format("j"), // day without begining 0
            (int)$date->format("G"), // Hours 24 without 0
            (int)$date->format("i"), // minutes
            (int)$date->format("s") // seconds
        ];

        return $ret;
    }
}
