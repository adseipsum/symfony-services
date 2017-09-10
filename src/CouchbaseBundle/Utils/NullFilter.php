<?php

namespace CouchbaseBundle\Utils;

use CouchbaseBundle\Base\IFilter;

/**
 * Class NullFilter
 *
 * @package Ontourcloud\CouchbaseBundle
 *
 *          Class filter out null values before insert into CouchBase
 *
 */
class NullFilter implements IFilter
{

    public function filter($param)
    {
        if (is_object($param)) {
            $param = (array)$param;
        }

        return $this->remove_null_recursive($param);
    }

    private function remove_null_recursive(&$array)
    {
        $sequential = false;
        $removed = false;

        foreach ($array as $key => $value) {

            is_array($value) && $array[$key] = $this->remove_null_recursive($value);

            if (is_int($key)) {
                $sequential = true;
            }

            if (is_object($value)) {
                $tmp = (array)$value;
                $array[$key] = $this->remove_null_recursive($tmp);
            }

            if (is_null($array[$key])) {
                unset($array[$key]);
                $removed = true;
            }
        }

        if ($sequential == true && $removed) {
            $array = array_merge($array);
        }

        return $array;
    }
}