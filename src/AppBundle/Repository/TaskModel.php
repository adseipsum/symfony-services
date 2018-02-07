<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbTask;
use Rbl\CouchbaseBundle\Base\CbBaseModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Rbl\CouchbaseBundle\Base\CbBaseObject;

class TaskModel extends CbBaseModel
{
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

    public function createTask($blogId, $campaignId){
        $task = new CbTask();
        $task->setBlogId($blogId);
        $task->setCampaignId($campaignId);
        $task->setStatus(CbTask::STATUS_NEW);
        $this->upsert($task);

        return $task->getObjectId();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function updateTask($taskId, $array){
        $task = $this->get($taskId);

        if($task && $array){
            foreach($array as $method => $value){
                if(is_callable(array($task, $method))){
                    $task->$method($value);
                }
            }

            $this->upsert($task);
            return true;
        }

        return false;
    }
}
