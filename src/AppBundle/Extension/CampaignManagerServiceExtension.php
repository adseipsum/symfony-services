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
    protected $blogModel;

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
        $this->blogModel = new BlogModel($this->cb);
        $this->taskModel = new TaskModel($this->cb);
        $this->amqp = $amqp;
    }

    /**
     * @param object $msg
     * @return void
     */
    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        echo "recieved message: \n";
        var_dump($message);
        $idString = explode('::', $message->taskId);
        $taskId = $idString[1];
        $status = $idString[2];

        $taskObject = $this->taskModel->get($taskId);

        if(!$taskObject){
            return false;
        }

        $campaignObject = $this->campaignModel->get($taskObject->getCampaignId());

        if($status == CbTask::STATUS_COMPLETED) {
            $campaignObject->setNextPostTime($this->campaignModel->calculateNextPostTime($campaignObject));
            $campaignObject->setPosted($campaignObject->getPosted() + 1);
            $campaignObject->setStatus(CbCampaign::STATUS_READY);
            $campaignObject->incrementPostsForBlog($taskObject->getBlogId());
        }elseif($status == CbTask::STATUS_FAILED){

            $taskObject->setStatus(Cb::STATUS_FAILED);
            $this->taskModel->upsert($taskObject);

            $campaignObject->setNextPostTime($this->campaignModel->calculateNextPostTime($campaignObject));
            $campaignObject->setStatus(CbCampaign::STATUS_READY);
        }

        //close campaign if no more tasks to do
        if ($campaignObject->getNeedPosts() <= $campaignObject->getPosted()) {
            $campaignObject->setStatus(CbCampaign::STATUS_COMPLETED);
        }

        $this->campaignModel->upsert($campaignObject);

        $blogObject = $this->blogModel($taskObject->getBlogId());
        $blogObject->setLocked(false);
        $this->blogModel->upsert($blogObject);
    }
}