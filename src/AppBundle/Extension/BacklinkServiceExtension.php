<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use AppBundle\Repository\CampaignModel;
use AppBundle\Repository\TextGenerationResultModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Krombox\OAuth2\Client\Provider\Wordpress;

class BacklinkServiceExtension
{
    protected $cb;
    protected $taskModel;
    protected $taskObject;
    protected $textModel;
    protected $campaignModel;
    protected $campaignObject;

    const THIS_SERVICE_KEY = 'bln';
    const POST_MANAGER_ROUTING_KEY = 'srv.postmanager.v1';
    const CAMPAIGN_MANAGER_SERVICE_ROUTING_KEY = 'srv.cmpmanager.v1';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->campaignModel = new CampaignModel($this->cb);
        $this->taskModel = new TaskModel($this->cb);
        $this->textModel = new TextGenerationResultModel($this->cb);
        $this->textModel->setBucket($this->cb->getBucketForType('TextGenerationResult'));

        $this->amqp = $amqp;
    }

    /**
     * @return bool
     */
    public function insertBacklink(){
        $bodyObject = $this->textModel->getSingle($this->taskObject->getBodyId());

        if($this->campaignObject->getMaxPostsAtMain() > $this->campaignObject->getPostedAtMain()){
                //main backlink
            $mainPostedPercentage = $this->campaignObject->getPostedAtMain() * 100 / $this->campaignObject->getMaxPostsAtMain();
            if(100 - $mainPostedPercentage > $this->campaignObject->getAdditionalKeysPercentage()){
                //can be additional
                '<a href="' . $this->campaignObject->getMainDomain() . '">random addittional</a>';
            }else{
                //only main
                $keyword = $this->campaignObject->getMainKeywords();
                '<a href="' . $this->campaignObject->getMainDomain() . '">' . $keyword . '</a>';
            }

            $this->campaignObject->incrementPostedAtMain();
        }else{
                //sub backlink
            $sublinks = $this->campaignObject->getSubLinks();

            foreach($sublinks as $sublink => $keywords){

            }

            $this->campaignObject->incrementPostedAtSublinks();
        }

        $test = array(
            $this->campaignObject->getAdditionalKeysPercentage(),
            $this->campaignObject->getMainKeywords(),
            $this->campaignObject->getSubLinks(),
            $this->campaignObject->getMainDomain(),
            $this->campaignObject->getMaxPostsAtMain()
        );


        var_dump($test); die;
    }

    /**
     * @param object $msg
     * @return void
     */
    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        $idString = explode('::', $message->taskId);
        $taskId = $idString[1];

        $this->taskObject = $this->taskModel->get($taskId);

        if($this->taskObject){
            $this->campaignObject = $this->campaignModel->get($this->taskObject->getCampaignId());
            if($this->insertBacklink()) {
                $this->sendCompletePostingMessage($this->taskObject->getObjectId(), $message->responseRoutingKey);
            }else{
                $msg = array('taskId' => implode( '::', array(self::THIS_SERVICE_KEY, $this->taskObject->getObjectId(), CbTask::STATUS_FAILED)));
                $this->amqp->publish(json_encode($msg), self::CAMPAIGN_MANAGER_ROUTING_KEY);
            }
        }
    }

    /**
     * @param string $taskId
     * @return void
     */
    protected function sendCompletePostingMessage($taskId, $responseRoutingKey){
        $msg = array(
            'taskId' => implode( '::', array(self::THIS_SERVICE_KEY, $taskId, CbTask::STATUS_BACKLINK_INSERT)),
        );

        $this->amqp->publish(json_encode($msg), $responseRoutingKey);
    }


}