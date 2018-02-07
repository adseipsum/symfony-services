<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use AppBundle\Entity\CbBlog;
use AppBundle\Repository\BlogModel;
use AppBundle\Entity\CbTextGenerationResult;
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

    const WP_RESOURCE_PATH = '/wp-json/wp/v2/posts/';
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

        $WPRequestBody = array(
            'title' => $headerObject->getText(),
            'content' => $bodyObject->getText(),
            'status' => 'publish',
            'type' => 'post',
            //'featured_media' => $this->taskObject->getImageId(),
            'meta' => array(
                'description' => 'test descriptions',
                'title' => $seoTitleObject->getText()
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

            $body['action'] = 'write';
            $options['body'] = json_encode($WPRequestBody);
            $options['headers']['Content-Type'] = 'application/json;charset=UTF-8';
            $options['headers']['access_token'] = $accessToken->getToken();

            $request = $provider->getAuthenticatedRequest(
                'POST',
                $blogObject->getDomainName() . self::WP_RESOURCE_PATH,
                $accessToken->getToken(),
                $options
            );
            $response = $provider->getResponse($request);

            if(!isset($response['code'])){
                $blogObject->setLastPostDate(new \DateTime);
            }else{
                $blogObject->setLastErrorMessage($response['code']);
            }

            $blogObject->setLocked(false);
            $this->blogModel->upsert($blogObject);

            return true;

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