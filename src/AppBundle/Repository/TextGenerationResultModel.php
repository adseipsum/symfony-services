<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbTextGenerationResult;
use CouchbaseBundle\Base\CbBaseModel;
use CouchbaseBundle\Base\CbDirectKeyModel;
use CouchbaseBundle\CouchbaseService;
use CouchbaseBundle\Base\CbBaseObject;

class TextGenerationResultModel extends CbDirectKeyModel
{

    const DISDOC_ID = "textGenerationResult";


    public function __construct(CouchbaseService $service)
    {
        parent::__construct('tsk', 'TextGenerationResult', $service->getBucketForType('TextGenerationResult'));
    }

    public function factory() : CbBaseObject
    {
        $ret = new CbTextGenerationResult();
        return $ret;
    }

    public function getDisdocId() : string
    {
        return self::DISDOC_ID;
    }


}