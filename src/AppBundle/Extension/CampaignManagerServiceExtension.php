<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use AppBundle\Entity\CbCampaign;
use AppBundle\Repository\CampaignModel;
use Rbl\CouchbaseBundle\CouchbaseService;

class CampaignManagerServiceExtension
{
    protected $cb;
    protected $campaignModel;
    protected $taskModel;

    const THIS_SERVICE_KEY = 'cmp';
    const RESPONCE_ROUTING_KEY = 'srv.cmpmanager.v1';

    /**
     * @param CouchbaseService $cb
     * @param object $amqp
     * @return void
     */

    public function __construct(CouchbaseService $cb, $amqp){
        $this->cb = $cb;
        $this->campaignModel = new CampaignModel($this->cb);
        $this->taskModel = new TaskModel($this->cb);
        $this->amqp = $amqp;
    }

    /**
     * @param object $msg
     * @return void
     */
    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        $idString = explode('::', $message->taskId);
        $taskId = $idString[1];

        $taskObject = $this->taskModel->get($taskId);

        if(!$taskObject){
            return false;
        }

        if($taskObject->getStatus() == CbTask::STATUS_COMPLETED) {
            $campaignObject = $this->campaignModel->get($taskObject->getCampaignId());

            $campaignObject->setNextPostTime($this->campaignModel->calculateNextPostTime($campaignObject));
            $campaignObject->setPosted($campaignObject->getPosted() + 1);
            $campaignObject->incrementPostsForBlog($taskObject->getBlogId());
        }

        //close campaign if no more tasks to do
        if ($campaignObject->getNeedPosts() <= $campaignObject->getPosted()) {
            $campaignObject->setStatus(CbCampaign::STATUS_COMPLETED);
        }

        $this->campaignModel->upsert($campaignObject);
    }
}