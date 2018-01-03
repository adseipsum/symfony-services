<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbTask;
use CouchbaseBundle\Base\CbBaseModel;
use CouchbaseBundle\CouchbaseService;
use CouchbaseBundle\Base\CbBaseObject;

class TaskModel extends CbBaseModel
{

    const VIEW_BY_STATUS = 'status';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Views Section
    const DISDOC_ID = "task";

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct(CouchbaseService $service)
    {
        parent::__construct('task', 'Task', $service->getBucketForType('Task'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function factory() : CbBaseObject
    {
        $ret = new CbTask();
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDisdocId() : string
    {
        return self::DISDOC_ID;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getTasksByStatus($status)
    {
        return $this->getObjectByView($status, self::VIEW_BY_STATUS, true);
    }


}
