<?php
/**
 * Created by PhpStorm.
 * User: void
 * Date: 7/18/17
 * Time: 10:39 PM
 */

namespace AppBundle\Repository;


use CouchbaseBundle\Base\CbBaseModel;
use CouchbaseBundle\Base\CbDirectKeyModel;
use CouchbaseBundle\CouchbaseService;
use AppBundle\Entity\CbTemplate;

class TemplateModel extends CbBaseModel
{
    //Views Section
    const DISDOC_ID = "template";

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

}