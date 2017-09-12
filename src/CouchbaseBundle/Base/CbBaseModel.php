<?php

namespace CouchbaseBundle\Base;

use Couchbase\Exception as CouchbaseException;
use Couchbase\ViewQuery as CouchbaseViewQuery;
use Couchbase\Bucket as CouchbaseBucket;

/**
 *
 * ACL and other stuff there
 *
 */
abstract class CbBaseModel
{

    const VIEW_BY_ID = 'id';
    const VIEW_BY_TITLE = 'title';

    const SUFFIX_COUNTER = 'count';
    const KEY_SEPARATOR = '-';
    const SEQUENCE_START_VALUE = 0;
    const SEQUENCE_INCREMENT = 1;
    const SEQUENCE_STEP = 10;


    protected $counter;
    protected $prefix;
    protected $docType;

    /**
     * @var CouchbaseBucket
     */
    protected $bucket;

    /**
     * @var IFilter
     */
    protected $filter;

    protected $sequence_initialized;

    public function __construct($prefix, $docType, $bucket)
    {
        $this->prefix = $prefix;
        $this->docType = $docType;
        $this->bucket = $bucket;
    }

    abstract public function factory() : CbBaseObject;

    abstract public function getDisdocId();


    public function setBucket(CouchbaseBucket $bucket)
    {
        $this->bucket = $bucket;
    }

    public function getBucket() : CouchbaseBucket
    {
        return $this->bucket;
    }


    public function setFilter(IFilter $filter)
    {
        $this->filter = $filter;
    }


    public function initializeSequence()
    {
        try {
            $this->bucket->get($this->countKey());
        } catch (CouchbaseException $e) {
            $this->bucket->insert($this->countKey(), CbBaseModel::SEQUENCE_START_VALUE);
        }
        $this->sequence_initialized = true;
    }

    public function docType()
    {
        return $this->docType;
    }

    public function prefix()
    {
        return $this->prefix;
    }

    public function key($id)
    {
        return $this->prefix . CbBaseModel::KEY_SEPARATOR . $id;
    }

    public function countKey()
    {
        return $this->prefix . CbBaseModel::KEY_SEPARATOR . CbBaseModel::SUFFIX_COUNTER;
    }

    public function idNext()
    {
        if ($this->sequence_initialized == false) {
            $this->initializeSequence();
        }

        return $this->bucket->counter($this->countKey(), 1)->value;
    }

    public function idCurrent()
    {
        if ($this->sequence_initialized == false) {
            $this->initializeSequence();
        }

        return $this->bucket->counter($this->countKey(), 0)->value;
    }

    public function isExist($key)
    {
        try {
            $this->bucket->get($key);
            return true;
        } catch (CouchbaseException $e) {
            if ($e->getCode() == 13) {
                return true;
            } else {
                throw  $e;
            }
        }
    }

    public function removeBySequence($id)
    {
        $this->bucket->remove($this->key($id));
    }

    public function removeByKey($key)
    {
        $this->remove($key);
    }

    public function remove($key)
    {
        $this->bucket->remove($key);
    }


    public function create(CbBaseObject $object)
    {

        $key = $object->getObjectId();

        if ($key == null) {
            $object->setObjectId($this->key($this->idNext()));
            $object->setDocType($this->docType);
            $key = $object->getObjectId();
        }

        if ($this->filter != null) {
            $this->bucket->upsert($key, $this->filter->filter($object->getObjectAsArray()));
        } else {
            $this->bucket->upsert($key, $object->getObjectAsArray());
        }


        return $key;
    }

    public function createInsert($object, $ttl = 0)
    {
        $this->insert($object, $ttl);
    }

    public function insert(CbBaseObject $object, int $ttl = 0)
    {
        $object->setObjectId($this->key($this->idNext()));
        $object->setDocType($this->docType);
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
            $this->bucket->insert($key, $value, array('expiry' => $ttl));
        }

        return $key;
    }

    public function upsert(CbBaseObject $object, int $ttl = 0)
    {
        $key = $object->getObjectId();

        if ($key == null) {
            $object->setObjectId($this->key($this->idNext()));
            $object->setDocType($this->docType);
            $key = $object->getObjectId();
        }

        $value = null;
        if ($this->filter != null) {
            $value = $this->filter->filter($object->getObjectAsArray());
        } else {
            $value = $object->getObjectAsArray();
        }

        if ($ttl == 0) {
            $this->bucket->upsert($key, $value);
        } else {
            $this->bucket->upsert($key, $value, array('expiry' => $ttl));
        }
    }

    public function update(CbBaseObject $object, int $ttl = 0)
    {
        $key = $object->getObjectId();

        if ($key == null) {
            throw new CouchbaseException("Invalid operation, lack of valid key");
        }

        $value = null;
        if ($this->filter != null) {
            $value = $this->filter->filter($object->getObjectAsArray());
        } else {
            $value = $object->getObjectAsArray();
        }

        if ($ttl == 0) {
            $this->bucket->update($key, $value);
        } else {
            $this->bucket->update($key, $value, array('expiry' => $ttl));
        }
    }

    public function get($key)
    {
        if ($key == null) {
            return null;
        }

        if (is_array($key)) {
            if (empty($key)) {
                return [];
            }

            $retValues = $this->bucket->get($key);

            foreach ($retValues as $cbValue) {
                $obj = $this->factory();
                $obj->setCbValues($cbValue->value);
                $ret [] = $obj;
            }
            return $ret;
        } else {
            try {
                $retCbs = $this->bucket->get($key);
                $ret = $this->factory();
                $ret->setCbValues($retCbs->value);
                return $ret;
            } catch (CouchbaseException $e) {
                if ($e->getCode() == 13) {
                    return null;
                } else {
                    throw  $e;
                }
            }
        }
    }


    public function validateObject(CbBaseObject $object, $id = null)
    {

        if ($id != null && $object->getObjectId() != $id) {
            return false;
        } elseif ($object->getDocType() != $this->doctype()) {
            return false;
        }
        return true;
    }

    /**
     * Triger indexing of view in Database
     */
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

    public function getObjectByView($key, $viewName)
    {
        return $this->get($this->getIdByView($key, $viewName));
    }

    public function getIdByView($key, $viewName)
    {
        $view = CouchbaseViewQuery::from($this->getDisdocId(), $viewName);
        $view->key($key);
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
        $ret = $this->get($objectIds);
        return $ret == null ? [] : $ret;
    }


    public function listObjects($descending = false, $skip = 0, $limit = -1)
    {
        $objectIds = $this->listObjectIdByView(CbBaseModel::VIEW_BY_ID, null, $descending, $skip, $limit);
        return $this->get($objectIds);
    }

    public function listObjectsByTitle($descending = false, $skip = 0, $limit = -1)
    {
        $objectIds = $this->listObjectIdByView(CbBaseModel::VIEW_BY_TITLE, $descending, $skip, $limit);
        return $this->get($objectIds);
    }

}
