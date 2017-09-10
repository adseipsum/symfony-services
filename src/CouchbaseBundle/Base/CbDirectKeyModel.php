<?php

namespace CouchbaseBundle\Base;

use Couchbase\Exception as CouchbaseException;
use Couchbase\ViewQuery as CouchbaseViewQuery;

abstract class CbDirectKeyModel
{

    const VIEW_BY_ID = 'id';

    const VIEW_BY_TITLE = 'title';

    const KEY_SEPARATOR = '::';

    protected $prefix;

    protected $prefixsepar;

    protected $doctype;

    protected $bucket;

    protected $filter;

    public function __construct($prefix, $doctype, $bucket)
    {
        $this->prefix = $prefix;
        $this->doctype = $doctype;
        $this->bucket = $bucket;
        $this->prefixsp = $this->prefix . self::KEY_SEPARATOR;
    }

    abstract public function factory();

    abstract public function getDisdocId();

    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function setFilter(IFilter $filter)
    {
        $this->filter = $filter;
    }

    public function docType()
    {
        return $this->doctype;
    }

    public function prefix()
    {
        return $this->prefix;
    }

    public function key($id)
    {
        if (is_array($id)) {
            $idstr = '';
            foreach ($id as $idelem) {
                if (strlen($idstr)) {
                    $idstr .= self::KEY_SEPARATOR;
                }
                $idstr .= $idelem;
            }
            return $this->prefix . self::KEY_SEPARATOR . $idstr;
        } else {

            if (substr($id, 0, strlen($this->prefixsp)) === $this->prefixsp) {
                return $id;
            } else {
                return $this->prefixsp . $id;
            }
        }
    }

    public function isExist($key)
    {
        try {
            $this->bucket->get($this->key($key));
            return true;
        } catch (CouchbaseException $e) {
            if ($e->getCode() == COUCHBASE_KEY_ENOENT) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    public function remove($key)
    {
        $this->bucket->remove($this->key($key));
    }

    public function create($object, $id, $ttl = 0)
    {
        return $this->upsert($object, $id, $ttl);
    }

    public function insert($object, $id, $ttl = 0)
    {
        $object->setObjectId($this->key($id));
        $object->setDocType($this->doctype);
        $key = $object->getObjectId();

        $value = null;
        if ($this->filter != null) {
            $value = $this->filter->filter($object->getObjectAsArray());
        } else {
            $value = $object->getObjectAsArray();
        }

        if ($ttl == 0) {
            $this->bucket->insert($key, $value);
        } else {
            $this->bucket->insert($key, $value, array(
                'expiry' => $ttl
            ));
        }

        return $key;
    }

    public function upsert($object, $id = null, $ttl = 0)
    {
        $key = null;
        if ($id == null) {
            $key = $object->getObjectId();
        } else {
            $key = $this->key($id);
            $object->setObjectId($this->key($id));
        }

        $object->setDocType($this->doctype);

        $value = null;
        if ($this->filter != null) {
            $value = $this->filter->filter($object->getObjectAsArray());
        } else {
            $value = $object->getObjectAsArray();
        }

        if ($ttl == 0) {
            $this->bucket->upsert($key, $value);
        } else {
            $this->bucket->upsert($key, $value, array(
                'expiry' => $ttl
            ));
        }

        return $key;
    }

    public function update($object, $ttl = 0)
    {
        $key = $object->getObjectId();

        $value = null;
        if ($this->filter != null) {
            $value = $this->filter->filter($object->getObjectAsArray());
        } else {
            $value = $object->getObjectAsArray();
        }

        $ret = null;
        if ($ttl == 0) {
            $ret = $this->bucket->replace($key, $value, array(
                'cas' => $object->getCas()
            ));
        } else {
            $ret = $this->bucket->replace($key, $value, array(
                'expiry' => $ttl,
                'cas' => $object->getCas()
            ));
        }
    }

    public function touch($id, $ttl)
    {
        $key = $this->key($id);
        $this->bucket->touch($key, $ttl);
    }

    public function get($id)
    {
        return $this->getSingle($id);
    }

    /*
     * Принимает ключ в виде строки или массива если ключ составной
     */
    public function getSingle($id)
    {
        $key = $this->key($id);

        try {
            $retCbs = $this->bucket->get($key);
            $ret = $this->factory();
            $ret->setCbValues($retCbs->value);
            $ret->setCas($retCbs->cas);
            return $ret;
        } catch (CouchbaseException $e) {
            if ($e->getCode() == 13) {
                return null;
            } else {
                throw $e;
            }
        }
    }

    /*
     * Принимает ключ в виде массива строк или массива массивов для составных ключей
     */
    public function getMulti($key)
    {
        $keylist = [];

        foreach ($key as $elem) {
            $keylist[] = $this->key($elem);
        }

        $retValues = $this->bucket->get($keylist);

        $ret = [];

        foreach ($retValues as $cbValue) {
            if ($cbValue != null && $cbValue->value != null) {
                $obj = $this->factory();
                $obj->setCbValues($cbValue->value);
                $ret[] = $obj;
            }
        }
        return $ret;
    }

    public function warmup()
    {
        $view = CouchbaseViewQuery::from($this->getDisdocId(), CbBaseModel::VIEW_BY_ID);
        $view->limit(1);
        $view->stale(CouchbaseViewQuery::UPDATE_BEFORE);
        $this->bucket->query($view);
    }

    public function listObjectIdByView($viewname, $key = null, $descending = false, $skip = 0, $limit = -1)
    {
        $view = CouchbaseViewQuery::from($this->getDisdocId(), $viewname);

        if ($descending == true) {
            $view->order(CouchbaseViewQuery::ORDER_DESCENDING);
        }

        if ($key != null) {
            $view->key($key);
        }

        if ($skip != 0) {
            $view->skip($skip);
        }
        if ($limit != -1) {
            $view->limit($limit);
        }

        $result = $this->bucket->query($view);

        if (isset($result->rows) && is_array($result->rows)) {
            $ret = [];
            foreach ($result->rows as $row) {
                $ret[] = $row->id;
            }

            return $ret;
        } else {
            return [];
        }
    }

    public function getObjectByView($key, $viewName, $updateBefore = false)
    {
        return $this->get($this->getIdByView($key, $viewName, $updateBefore));
    }

    public function getIdByView($key, $viewName, $updateBefore = false)
    {
        $view = CouchbaseViewQuery::from($this->getDisdocId(), $viewName);
        $view->key($key);
        if ($updateBefore == true) {
            $view->stale(CouchbaseViewQuery::UPDATE_BEFORE);
        }
        $result = $this->bucket->query($view);

        if (isset($result->rows) && is_array($result->rows) && isset($result->rows[0])) {
            return $result->rows[0]->id;
        } else {
            return null;
        }
    }

    // Common helpers methods
    public function getAllIds($descending = false, $skip = 0, $limit = -1)
    {
        return $this->listObjectIdByView(CbBaseModel::VIEW_BY_ID, null, $descending, $skip, $limit);
    }

    public function getAllObjects($descending = false, $skip = 0, $limit = -1)
    {
        $objectIds = $this->listObjectIdByView(CbBaseModel::VIEW_BY_ID, null, $descending, $skip, $limit);
        return $this->getMulti($objectIds);
    }
}