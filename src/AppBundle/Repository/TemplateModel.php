<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbTemplate;
use Couchbase\Exception as CouchbaseException;
use CouchbaseBundle\Base\CbBaseModel;
use CouchbaseBundle\CouchbaseService;

class TemplateModel extends CbBaseModel
{

    // Views Section
    const DISDOC_ID = "template";

    var $version_sequence_initialized;

    public function __construct(CouchbaseService $service)
    {
        parent::__construct('tpl', 'Template', $service->getBucketForType('Template'));
    }

    public function factory()
    {
        return new CbTemplate();
    }

    public function getDisdocId()
    {
        return self::DISDOC_ID;
    }

    // Versioning
    public function initialize_version_sequence($docId)
    {
        try {
            $this->bucket->get($this->version_counter_key($docId));
        } catch (CouchbaseException $e) {
            $this->bucket->insert($this->version_counter_key($docId), CbBaseModel::SEQUENCE_START_VALUE);
        }
        $this->version_sequence_initialized = true;
    }

    public function version_counter_key($docId)
    {
        return $docId . CbBaseModel::KEY_SEPARATOR . CbBaseModel::SUFFIX_COUNTER;
    }

    public function version_next($docId)
    {
        if ($this->version_sequence_initialized == false) {
            $this->initialize_version_sequence($docId);
        }
        return $this->bucket->counter($this->version_counter_key($docId), 1)->value;
    }

    public function version_current($docId)
    {
        if ($this->version_sequence_initialized == false) {
            $this->initialize_version_sequence($docId);
        }

        return $this->bucket->counter($this->version_counter_key($docId), 0)->value;
    }

    public function version_key($docId)
    {
        return $docId . CbBaseModel::KEY_SEPARATOR . $this->version_next($docId);
    }

    // Patched update
    private function _save_version(CbTemplate $object)
    {
        $archived = new CbTemplate();
        $archived->mirror($object);
        $archived->setArchived(true);
        $key = $this->version_key($object->getObjectId());
        $archived->setObjectId($key);
        parent::upsert($archived);
    }

    public function upsert($object, $ttl = 0)
    {
        parent::upsert($object, $ttl);
        if ($ttl == 0) {
            $this->_save_version($object);
        }
    }

    public function update($object, $ttl = 0)
    {
        parent::update($object, $ttl);
        if ($ttl == 0) {
            $this->_save_version($object);
        }
    }

    public function insert($object, $ttl = 0)
    {
        parent::insert($object, $ttl);
        if ($ttl == 0) {
            $this->_save_version($object);
        }
    }
}
