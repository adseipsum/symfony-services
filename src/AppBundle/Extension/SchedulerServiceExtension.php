<?php
namespace AppBundle\Extension;

use Rbl\CouchbaseBundle\CouchbaseService;
use AppBundle\Entity\CbCampaign;
use AppBundle\Repository\CampaignModel;

class SchedulerServiceExtension
{
    protected $cb;
    protected $campaignModel;

    const THIS_SERVICE_KEY = 'cmp';

    const GENERATION_ROUTING_KEY = 'srv.txtgen.v1';
    const POSTING_ROUTING_KEY = 'srv.posting.v1';
    const RESPONCE_ROUTING_KEY = 'srv.cmpmanager.v1';

    const MESSAGE_GENERATION_KEY = 'generation';
    const MESSAGE_POSTED_KEY = 'posted';

    /**
     * @param CouchbaseService $cb
     * @param object $amqp
     * @return void
     */

    public function __construct(CouchbaseService $cb, $amqp){
        $this->cb = $cb;
        $this->campaignModel = new CampaignModel($this->cb);
        $this->amqp = $amqp;
    }

    /**
     * @return void
     */
    public function processTask(){

        $campaignObject = $this->campaignModel->getCampaignsByStatus(CbCampaign::STATUS_PROCESSING);

        if(!$campaignObject){
            return false;
        }

        if($this->campaignModel->needsGeneration($campaignObject)){
            $this->sendGenerationMessage($campaignObject);
        }

        if(time() > $campaignObject->getNextPostTime()->getTimestamp() && $campaignObject->getNewPostingTasks()){
            $this->sendPostingMessage($campaignObject);
        }

        //close campaign if no more tasks to do
        if(!$this->campaignModel->needsGeneration($campaignObject) && !$campaignObject->getNewPostingTasks()){
            $campaignObject->setStatus(CbCampaign::STATUS_COMPLETED);
            $this->campaignModel->upsert($campaignObject);
        }

    }

    /**
     * @param CbCampaign $campaign
     * @return void
     */
    private function sendGenerationMessage(CbCampaign $campaign){

        $taskId = uniqid();

        $generatedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $campaign->getObjectId()));
        $generatedResultKeyId = implode('::', array(self::THIS_SERVICE_KEY, $campaign->getObjectId(), $taskId, self::MESSAGE_GENERATION_KEY));

        $msg = array(
            'taskId' => $generatedTaskId,
            'resultKey' => $generatedResultKeyId,
            'responceRoutingKey' => self::RESPONCE_ROUTING_KEY
        );

        $this->amqp->publish(json_encode($msg), self::GENERATION_ROUTING_KEY);
    }

    /**
     * @param CbCampaign $campaign
     * @return void
     */
    private function sendPostingMessage(CbCampaign $campaign){
        $newPostingTasks = $campaign->getNewPostingTasks();
        $postingTaskId = key($newPostingTasks);
        $generatedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $campaign->getObjectId(), $postingTaskId));

        $msg = array(
            'taskId' => $generatedTaskId,
            'blogs' => $campaign->getBlogs(),
            'responceRoutingKey' => self::RESPONCE_ROUTING_KEY
        );

        $this->amqp->publish(json_encode($msg), self::POSTING_ROUTING_KEY);
    }

    /**
     * @param object $msg
     * @return void
     */
    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        //cmp::campaign-1::5a5f5321e3cde::generation
        $idString = explode('::', $message->resultKey);

        $campaignObject = $this->campaignModel->get($idString[1]);
        if(!$campaignObject){
            return false;
        }

        if(strstr($message->resultKey, self::MESSAGE_GENERATION_KEY)){
            $campaignObject->setPostingTask(array($idString[2] => 0));
        }elseif(strstr($message->resultKey, self::MESSAGE_POSTED_KEY)){
            $campaignObject->setNextPostTime($this->campaignModel->calculateNextPostTime($campaignObject));
            $campaignObject->updatePostingTask($idString[2], 1);
            $campaignObject->setPosted($campaignObject->getPosted() + 1);
            $campaignObject->incrementPostsForBlog($message->blogId);
        }
        $this->campaignModel->upsert($campaignObject);
    }
}