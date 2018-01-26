<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbTemplate;
use Rbl\CouchbaseBundle\Base\CbBaseObject;
use Rbl\Couchbase\Exception as CouchbaseException;
use Rbl\CouchbaseBundle\Base\CbBaseModel;
use Rbl\CouchbaseBundle\CouchbaseService;

class TemplateModel extends CbBaseModel
{

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Views Section
    const DISDOC_ID = "template";

    private $version_sequence_initialized;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct(CouchbaseService $service)
    {
        parent::__construct('tpl', 'Template', $service->getBucketForType('Template'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function factory() : CbBaseObject
    {
        return new CbTemplate();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDisdocId() : string
    {
        return self::DISDOC_ID;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Versioning
    public function initializeVersionSequence(string $docId)
    {
        try {
            $this->bucket->get($this->versionCounterKey($docId));
        } catch (CouchbaseException $e) {
            $this->bucket->insert($this->versionCounterKey($docId), CbBaseModel::SEQUENCE_START_VALUE);
        }
        $this->version_sequence_initialized = true;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function versionCounterKey(string $docId) : string
    {
        return $docId . CbBaseModel::KEY_SEPARATOR . CbBaseModel::SUFFIX_COUNTER;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function versionNext(string $docId) : int
    {
        if ($this->version_sequence_initialized == false) {
            $this->initializeVersionSequence($docId);
        }
        return $this->bucket->counter($this->versionCounterKey($docId), 1)->value;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function versionCurrent(string $docId) : int
    {
        if ($this->version_sequence_initialized == false) {
            $this->initializeVersionSequence($docId);
        }

        return $this->bucket->counter($this->versionCounterKey($docId), 0)->value;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function versionKey(string $docId) : string
    {
        return $docId . CbBaseModel::KEY_SEPARATOR . $this->versionNext($docId);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Patched update
    private function saveVersionInternal(CbBaseObject $object)
    {
        $archived = new CbTemplate();
        $archived->mirror($object);
        $archived->setArchived(true);
        $key = $this->versionKey($object->getObjectId());
        $archived->setObjectId($key);
        parent::upsert($archived);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function upsert(CbBaseObject $object, int $ttl = 0)
    {
        parent::upsert($object, $ttl);
        if ($ttl == 0) {
            $this->saveVersionInternal($object);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function update(CbBaseObject $object, int $ttl = 0)
    {
        parent::update($object, $ttl);
        if ($ttl == 0) {
            $this->saveVersionInternal($object);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function insert(CbBaseObject $object, int $ttl = 0)
    {
        parent::insert($object, $ttl);
        if ($ttl == 0) {
            $this->saveVersionInternal($object);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
