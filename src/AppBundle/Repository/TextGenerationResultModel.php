<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbTextGenerationResult;
use Rbl\CouchbaseBundle\Base\CbBaseModel;
use Rbl\CouchbaseBundle\Base\CbDirectKeyModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Rbl\CouchbaseBundle\Base\CbBaseObject;

class TextGenerationResultModel extends CbDirectKeyModel
{

    const DISDOC_ID = "textGenerationResult";


    public function __construct(CouchbaseService $service)
    {
        parent::__construct('pms', 'TextGenerationResult', $service->getBucketForType('TextGenerationResult'));
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