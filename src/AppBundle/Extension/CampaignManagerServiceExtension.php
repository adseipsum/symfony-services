<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbTask;
use AppBundle\Entity\CbBlog;
use AppBundle\Repository\TaskModel;
use AppBundle\Repository\BlogModel;
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

        /* @var  $taskObject CbTask */
        $taskObject = $this->taskModel->get($taskId);

        /* @var  $blogObject CbBlog */
        $blogObject = $this->blogModel->get($taskObject->getBlogId());

        if(!$taskObject){
            return false;
        }

        /* @var  $campaignObject CbCampaign */
        $campaignObject = $this->campaignModel->get($taskObject->getCampaignId());

        if($status == CbTask::STATUS_COMPLETED) {
            $campaignObject->setPosted($campaignObject->getPosted() + 1);
            $campaignObject->updatePostsForBlog($taskObject->getBlogId());
            $taskObject->setStatus(CbTask::STATUS_COMPLETED);
        }elseif($status == CbTask::STATUS_FAILED){
            //try to remove domain from posted on blog list
            $this->blogModel->updateMainDomainLinksPosted($blogObject, $this->campaignObject->getMainDomain(), true);
            $taskObject->setStatus(CbTask::STATUS_FAILED);
        }

        $this->taskModel->upsert($taskObject);

        $campaignObject->setNextPostTime($this->campaignModel->calculateNextPostTime($campaignObject));
        $campaignObject->setStatus(CbCampaign::STATUS_READY);

        //close campaign if no more tasks to do
        if ($campaignObject->getNeedPosts() <= $campaignObject->getPosted()) {
            $campaignObject->setStatus(CbCampaign::STATUS_COMPLETED);
        }

        $this->campaignModel->upsert($campaignObject);

        $blogObject->setLocked(false);
        $blogObject->setLastTypePosted($campaignObject->getType());
        $this->blogModel->upsert($blogObject);
    }
}