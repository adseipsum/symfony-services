<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use AppBundle\Repository\BlogModel;
use AppBundle\Repository\TextGenerationResultModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Krombox\OAuth2\Client\Provider\Wordpress;

class PostingServiceExtension
{
    protected $cb;
    protected $taskModel;
    protected $taskObject;
    protected $textModel;
    protected $blogModel;

    const THIS_SERVICE_KEY = 'pst';
    const CAMPAIGN_MANAGER_ROUTING_KEY = 'srv.cmpmanager.v1';

    const WP_POSTS_PATH = '/wp-json/wp/v2/posts/';
    const WP_MEDIA_PATH = '/wp-json/wp/v2/media/';
    const WP_BACKLINK = 'oob';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->taskModel = new TaskModel($this->cb);
        $this->blogModel = new BlogModel($this->cb);
        $this->textModel = new TextGenerationResultModel($this->cb);
        $this->textModel->setBucket($this->cb->getBucketForType('TextGenerationResult'));

        $this->amqp = $amqp;
    }

    /**
     * @return bool
     */
    public function postToBlog(){

        $blogObject = $this->blogModel->get($this->taskObject->getBlogId());
        $bodyObject = $this->textModel->getSingle($this->taskObject->getBodyId());
        $headerObject = $this->textModel->getSingle($this->taskObject->getHeaderId());
        $seoTitleObject = $this->textModel->getSingle($this->taskObject->getSeoTitleId());
        $seoDescriptionObject = $this->textModel->getSingle($this->taskObject->getSeoDescriptionId());

        $WPRequestBody = array(
            'title' => $headerObject->getText(),
            'content' => $bodyObject->getText(),
            'status' => 'draft',
            'type' => 'post',
            'featured_media' => $this->taskObject->getImageId(),
            'meta' => array(
                '_yoast_wpseo_metadesc' => $seoDescriptionObject->getText(),
                '_yoast_wpseo_title' => $seoTitleObject->getText()
            )
        );

        $provider = new Wordpress([
            'clientId'                => $blogObject->getClientId(),
            'clientSecret'            => $blogObject->getClientSecret(),
            'redirectUri'             => self::WP_BACKLINK,
            'domain'                  => $blogObject->getDomainName()
        ]);

        try {
            $accessToken = $provider->getAccessToken('password', [
                'username' => $blogObject->getPostingUserLogin(),
                'password' => $blogObject->getPostingUserPassword()
            ]);

            $options['body'] = json_encode($WPRequestBody);
            $options['headers']['Content-Type'] = 'application/json;charset=UTF-8';
            $options['headers']['access_token'] = $accessToken->getToken();

            $request = $provider->getAuthenticatedRequest(
                'POST',
                $blogObject->getDomainName() . self::WP_POSTS_PATH,
                $accessToken->getToken(),
                $options
            );

            $response = $provider->getResponse($request);

            if(!isset($response['code'])){
                $blogObject->setLastPostDate(new \DateTime);
            }else{
                $blogObject->setLastErrorMessage($response['code']);
            }

            if($this->updateMedia($provider, $accessToken)){
                return true;
            }

        } catch (IdentityProviderException $e) {
            echo $e->getMessage();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function updateMedia($provider, $accessToken){

        $blogObject = $this->blogModel->get($this->taskObject->getBlogId());
        $imageAlt = $this->textModel->getSingle($this->taskObject->getImageAltId());

        $WPRequestBody = array(
            'alt_text' => $imageAlt->getText()
        );

        try {
            $options['body'] = json_encode($WPRequestBody);
            $options['headers']['Content-Type'] = 'application/json;charset=UTF-8';
            $options['headers']['access_token'] = $accessToken->getToken();

            $request = $provider->getAuthenticatedRequest(
                'POST',
                $blogObject->getDomainName() . self::WP_MEDIA_PATH .  $this->taskObject->getImageId(),
                $accessToken->getToken(),
                $options
            );

            $response = $provider->getResponse($request);

            if(isset($response['id'])){
                return true;
            }

        } catch (IdentityProviderException $e) {
            echo $e->getMessage();
        }

        return false;
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
            if($this->postToBlog()) {
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
    private function sendCompletePostingMessage($taskId, $responseRoutingKey){
        $msg = array(
            'taskId' => implode( '::', array(self::THIS_SERVICE_KEY, $taskId, CbTask::STATUS_TEXT_POST)),
        );

        $this->amqp->publish(json_encode($msg), $responseRoutingKey);
    }

}