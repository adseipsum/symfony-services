<?php

namespace CouchbaseBundle\Base;

use Couchbase\Exception as CouchbaseException;

class ReferenceHelper
{

    const KEY_SEPARATOR = '::';

    const BASE = 'ref::';

    var $prefix;

    var $model;

    var $bucket;

    public function __construct($prefix, $model)
    {
        $this->prefix = $prefix;
        $this->model = $model;
        $this->bucket = $model->getBucket();
    }

    public function key($id)
    {
        return self::BASE . $this->prefix . self::KEY_SEPARATOR . $id;
    }

    public function createReference($refId, $value)
    {
        if ($refId != null) {
            $this->bucket->upsert($this->key($refId), array(
                "id" => $value
            ));
        }
    }

    public function deleteReference($model, $id)
    {
        $key = $this->key($id);
        if ($model->isExist($key)) {
            $model->remove($key);
        }
    }

    public function getIdByReference($refId)
    {
        $refKey = $key = $this->key($refId);

        try {
            $obj = $this->bucket->get($refKey)->value;
            return $obj->id;
        } catch (CouchbaseException $e) {
            return null;
        }
    }
}