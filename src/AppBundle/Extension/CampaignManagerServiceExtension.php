<?php
namespace AppBundle\Extension;

use Rbl\CouchbaseBundle\Entity\CbTask;
use Rbl\CouchbaseBundle\Entity\CbBlog;
use Rbl\CouchbaseBundle\Model\TaskModel;
use Rbl\CouchbaseBundle\Model\BlogModel;
use Rbl\CouchbaseBundle\Entity\CbCampaign;
use Rbl\CouchbaseBundle\Model\CampaignModel;
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
            $blogObject->setLastTypePosted($campaignObject->getType());
        }elseif($status == CbTask::STATUS_FAILED){
            if($campaignObject->getType() == CbCampaign::TYPE_BACKLINKED){
                //try to remove domain from posted on blog list
                $this->blogModel->updateMainDomainLinksPosted($blogObject, $campaignObject->getMainDomain(), true);
            }
            $campaignObject->setErrors($campaignObject->getErrors() + 1);
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

        //unlock the blog
        $blogObject->setLocked(false);
        $this->blogModel->upsert($blogObject);
        echo $blogObject->getObjectId() . ' unlocked';
    }
}