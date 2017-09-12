<?php

namespace CouchbaseBundle\Base;

use Couchbase\Exception as CouchbaseException;

abstract class CbBaseObject
{

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Field Names
    private $cas = null;

    private $values = array();

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setCas($cas)
    {
        $this->cas = $cas;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getCas()
    {
        return $this->cas;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function mirror($cbobject)
    {
        $this->values = $cbobject->values;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setCbValues($cbobject)
    {
        $this->values = json_decode(json_encode($cbobject), true);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function isNew()
    {
        return isset($this->values['docId']);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setObjectId($id)
    {
        $this->values['docId'] = $id;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getObjectId()
    {
        if (isset($this->values['docId'])) {
            return $this->values['docId'];
        }

        return null;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDocType()
    {
        return $this->values['docType'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setDocType($value)
    {
        $this->values['docType'] = $value;

        if (isset($this->values['docType'])) {
            return $this->values['docType'];
        }

        return null;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Cauton with care, with big documents
    public function isExist($key)
    {
        try {
            $this->bucket->get($key);
            return true;
        } catch (CouchbaseException $e) {
            return false;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function get($key)
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        } else {
            return null;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get on system: subobject array
     */
    public function getInSubobject($subobjKey, $key)
    {
        $subarr = (array)$this->get($subobjKey);

        if ($subarr == null || isset($subarr[$key]) == false) {
            return null;
        } else {
            return $subarr[$key];
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Set on system: subobject array
     */
    public function setInSubobject($subobjKey, $key, $value)
    {
        $subarr = (array)$this->get($subobjKey);

        if ($subarr == null) {
            $subarr = [];
        }

        $subarr[$key] = $value;

        $this->set($subobjKey, $subarr);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Set on system: subobject array
     */
    public function resetInSubobject($subobjKey, $key)
    {
        $subarr = (array)$this->get($subobjKey);

        if ($subarr == null) {
            return;
        }

        if (isset($subarr[$key])) {
            unset($subarr[$key]);
        }

        $this->set($subobjKey, $subarr);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get on cache: subobject array
     */
    protected function getCache($key)
    {
        return $this->getInSubobject('cache', $key);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Set on system: subobject array
     */
    protected function setCache($key, $value)
    {
        $this->setInSubobject('cache', $key, $value);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function reset($key)
    {
        unset($this->values[$key]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function addArrayElementUniq($key, $element)
    {
        $subarr = $this->get($key);

        if ($subarr == null) {
            $subarr = [];
            $subarr[] = $element;
        } else {
            $max = count($subarr);
            for ($i = 0; $i < $max; $i++) {
                $elem = $subarr[$i];

                if ($elem == $element) { // Already have this elem
                    return;
                }
            }
        }
        $this->set($key, $subarr);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function addArrayElement($key, $element)
    {
        $subarr = $this->get($key);

        if ($subarr == null) {
            $subarr = [];
        }
        $subarr[] = $element;
        $this->set($key, $subarr);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getArrayElement($key)
    {
        $subarr = $this->get($key);

        if ($subarr == null) {
            $subarr = [];
        }
        return $subarr;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Set dateCreated
     *
     * @param string $date
     *
     */
    public function setDateCreated($date)
    {
        $this->set('createdAt', $date);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get dateCreated
     *
     * @return string
     */
    public function getDateCreated()
    {
        return $this->get('createdAt');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * set dateUpdated
     *
     * @param string $date
     */
    public function setDateUpdated($date)
    {
        $this->set('updatedAt', $date);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get dateUpdated
     *
     * @return string
     */
    public function getDateUpdated()
    {
        return $this->get('updatedAt');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function trim($value)
    {
        if ($value != null) {
            $value = trim($value);
        }
        return $value;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Возвращает внутреннее представление json объекта как ассоциативный массив
     *
     * @return array
     */
    public function getObjectAsArray()
    {
        return $this->values;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
