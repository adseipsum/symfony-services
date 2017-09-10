<?php

namespace CouchbaseBundle\Base;

abstract class CbCompositeModel extends CbBaseModel
{

    protected $baseId;

    protected $compositeKey;

    public function __construct($prefix, $compositeKey, $docType, $bucket)
    {
        parent::__construct($prefix, $docType, $bucket);
        $this->compositeKey = $compositeKey;
    }

    public function prefix()
    {
        return $this->baseId + CbBaseModel::KEY_SEPARATOR + $this->compositeKey;
    }

    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;
    }
}