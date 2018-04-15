<?php
namespace AppBundle\Extension;

use Rbl\CouchbaseBundle\Entity\CbCampaign;
use Rbl\CouchbaseBundle\Entity\CbTask;
use Rbl\CouchbaseBundle\Model\TaskModel;
use Rbl\CouchbaseBundle\Model\CampaignModel;
use Rbl\CouchbaseBundle\Model\BlogModel;
use Rbl\CouchbaseBundle\Model\TextGenerationResultModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Krombox\OAuth2\Client\Provider\Wordpress;

class PostManagerServiceExtension
{
    private $cb;
    private $amqp;
    private $taskModel;
    private $textModel;
    private $backlinked = false;
    private $taskId;
    private $message;
    private $textConfig;
    private $taskObject;

    const THIS_SERVICE_KEY = 'pms';
    const TEXT_GENERATION_RESULT_KEY = 'tgrst';

    const TEXT_GENERATION_ROUTING_KEY = 'prod-satteliter.q.srv-txtgen.v2';
    const TEXT_DPN_GENERATION_ROUTING_KEY = 'prod-satteliter.q.srv-txtderr.v1';
    const BACKLINK_INSERT_SERVICE_ROUTING_KEY = 'srv.backlink.v1';
    const IMAGE_POSTING_SERVICE_ROUTING_KEY = 'srv.imgposting.v1';
    const POSTING_SERVICE_ROUTING_KEY = 'srv.posting.v1';
    const CAMPAIGN_MANAGER_SERVICE_ROUTING_KEY = 'srv.cmpmanager.v1';
    const RESPONSE_ROUTING_KEY = 'srv.postmanager.v1';

    const TEXT_GENERATED = 'text';
    const TEXT_DPN_GENERATED = 'textdpn';

    const HEADER_MAX_LENGTH = 70;

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->amqp = $amqp;
        $this->taskModel = new TaskModel($this->cb);
        $this->textModel = new TextGenerationResultModel($this->cb);
    }

    public function processMessage($msg){
        $this->message = json_decode($msg->getBody());
        $idString = explode('::', $this->message->taskId);
        $this->taskId = $idString[1];
        $this->taskObject = $this->taskModel->get($this->taskId);
        $statusKey = $idString[2];

        echo "received message: \n";
        var_dump($this->message);

        switch($statusKey){
            case CbTask::STATUS_NEW:
                $this->generateBody();
                break;
            case CbTask::STATUS_BODY_GEN:
                if($this->message->status->code != 200){
                    echo $this->message->status->text;
                    $this->generateBody();
                    break;
                }
                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_BODY_GEN, 'setBodyId' => $this->message->resultKey));
                $this->generateHeader();
                break;
            case CbTask::STATUS_HEADER_GEN:
                if($this->message->status->code != 200){
                    echo $this->message->status->text;
                    $this->generateHeader();
                    break;
                }

                //if generated header length more than allowed limit
                $textObject = $this->textModel->get($this->message->resultKey);
                if(strlen($textObject->getText()) > self::HEADER_MAX_LENGTH){
                    $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_NEW));
                    $this->sendMessage(self::RESPONSE_ROUTING_KEY, $this->taskId,CbTask::STATUS_NEW);
                    break;
                }

                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_HEADER_GEN, 'setHeaderId' => $this->message->resultKey));

                $this->generateSeoTitle();
                break;
            case CbTask::STATUS_SEO_TITLE_GEN:
                if($this->message->status->code != 200){
                    echo $this->message->status->text;
                    $this->generateSeoTitle();
                    break;
                }

                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_SEO_TITLE_GEN, 'setSeoTitleId' => $this->message->resultKey));


                break;
            case CbTask::STATUS_SEO_DESCRIPTION_GEN:
                if($this->message->status->code != 200){
                    echo $this->message->status->text;
                    $this->generateSeoDescription();
                    break;
                }

                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_SEO_DESCRIPTION_GEN, 'setSeoDescriptionId' => $this->message->resultKey));

                $this->generateImgAlt();
                break;
            case CbTask::STATUS_IMAGE_ALT_GEN:
                if($this->message->status->code != 200){
                    echo $this->message->status->text;
                    $this->generateImgAlt();
                    break;
                }

                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_IMAGE_ALT_GEN, 'setImageAltId' => $this->message->resultKey));

                $campaignModel = new CampaignModel($this->cb);
                $campaignObject = $campaignModel->get($this->taskObject->getCampaignId());

                if($campaignObject->getType() == CbCampaign::TYPE_REGULAR){
                    $this->sendMessage(self::IMAGE_POSTING_SERVICE_ROUTING_KEY, $this->taskId, CbTask::STATUS_IMAGE_POST);
                }else{
                    $this->sendMessage(self::BACKLINK_INSERT_SERVICE_ROUTING_KEY, $this->taskId, CbTask::STATUS_BACKLINK_INSERT);
                }

                break;
            case CbTask::STATUS_BACKLINK_INSERT:
                $this->taskObject->setBacklinked(true);
                $this->taskModel->upsert($this->taskObject);

                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_BACKLINK_INSERT));
                //send message to image posting service
                $this->sendMessage(self::IMAGE_POSTING_SERVICE_ROUTING_KEY, $this->taskId, CbTask::STATUS_IMAGE_POST);
                break;
            case CbTask::STATUS_IMAGE_POST:
                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_IMAGE_POST, 'setImageId' => $this->message->imageId));
                //send message to posting service
                $this->sendMessage(self::POSTING_SERVICE_ROUTING_KEY, $this->taskId, CbTask::STATUS_TEXT_POST);
                break;
            case CbTask::STATUS_TEXT_POST:
                $this->taskModel->updateTask($this->taskId, array('setStatus' => CbTask::STATUS_TEXT_POST));

                if($this->taskObject->getBacklinked()){
                    $blogModel = new BlogModel($this->cb);
                    $blogObject = $blogModel->get($this->taskObject->getBlogId());
                    $blogObject->setLastBacklinkedPostId($blogObject->getLastPostId());
                    $blogModel->upsert($blogObject);
                }

                //send message to Campaign Manager
                $this->sendMessage(self::CAMPAIGN_MANAGER_SERVICE_ROUTING_KEY, $this->taskId, CbTask::STATUS_COMPLETED);
                break;
            default:
                break;
        }
    }

    protected function generateBody(){

        $this->textConfig = array(
            'type' => 'random',
            'paragraph' => true,
            'paragraphSize' => array(200, 250),
            'size' => 1500,
            'ngram' => array(
                'apply' => true,
                'mode' => 'insert',
                'framesize' => 4,
                'probability' => 0.6
            )
        );

        //send message to generate text
        $this->sendMessage(self::TEXT_GENERATION_ROUTING_KEY, $this->taskId,CbTask::STATUS_BODY_GEN);
    }

    protected function generateHeader(){
        $this->textConfig = array(
            'type' => 'template',
            'templateId' => 'tpl-57',
            'inputTextId' => $this->message->resultKey,
            'mainSubject' => 'Subject2'
        );

        $this->sendMessage(self::TEXT_GENERATION_ROUTING_KEY, $this->taskId,CbTask::STATUS_HEADER_GEN);
    }

    protected function generateSeoTitle(){
        $this->textConfig = array(
            'type' => 'template',
            'templateId' => 'tpl-58',
            'inputTextId' => $this->taskObject->getBodyId(),
            'mainSubject' => 'Subject2'
        );

        $this->sendMessage(self::TEXT_GENERATION_ROUTING_KEY, $this->taskId,CbTask::STATUS_SEO_TITLE_GEN);
    }

    protected function generateSeoDescription(){
        $this->textConfig = array(
            'type' => 'description',
            'inputTextId' => $this->taskObject->getBodyId(),
            'size' => 160
        );

        $this->sendMessage(self::TEXT_DPN_GENERATION_ROUTING_KEY, $this->taskId,CbTask::STATUS_SEO_DESCRIPTION_GEN);
    }

    protected function generateImgAlt(){
        $this->textConfig = array(
            'type' => 'imagealt',
            'inputTextId' => $this->taskObject->getBodyId(),
            'size' => 160
        );

        $this->sendMessage(self::TEXT_DPN_GENERATION_ROUTING_KEY, $this->taskId,CbTask::STATUS_IMAGE_ALT_GEN);
    }

    private function sendMessage($routingKey, $taskId, $statusKey){
        $generatedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $taskId, $statusKey));

        $msg = array(
            'taskId' => $generatedTaskId,
            'responseRoutingKey' => self::RESPONSE_ROUTING_KEY,
        );

        //if something needs to be saved in CB
        if($this->textConfig){
            $msg['textconfig'] = $this->textConfig;
            $generatedResultKeyId = implode('::', array(self::TEXT_GENERATION_RESULT_KEY, $taskId, $statusKey));
            $msg['resultKey'] = $generatedResultKeyId;
        }

        echo "send message: \n";
        var_dump($routingKey);
        var_dump($msg);
        $this->amqp->publish(json_encode($msg), $routingKey);
    }

}