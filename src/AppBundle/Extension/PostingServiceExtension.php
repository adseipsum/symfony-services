<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbBlog;
use AppBundle\Entity\CbTextGenerationResult;
use CouchbaseBundle\CouchbaseService;
use AppBundle\Repository\BlogModel;
use AppBundle\Repository\TextGenerationResultModel;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Krombox\OAuth2\Client\Provider\Wordpress as Wordpress;

class PostingServiceExtension
{
    protected $cb;
    protected $blogModel;
    protected $textModel;
    protected $blogObject;
    protected $textObject;

    protected $responceRoutingKey;

    const THIS_SERVICE_KEY = 'post';
    const CAMPAIGN_SERVICE_KEY = 'cmp';

    const MESSAGE_GENERATION_KEY = 'generation';
    const MESSAGE_POSTED_KEY = 'posted';


    const WP_RESOURCE_PATH = '/wp-json/wp/v2/posts/';
    const WP_BACKLINK = 'oob';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;

        $this->blogModel = new BlogModel($this->cb);

        $this->textModel = new TextGenerationResultModel($this->cb);
        $this->textModel->setBucket($this->cb->getBucketForType('TextGenerationResult'));

        $this->amqp = $amqp;
    }

    /**
     * @param CbBlog $blogObject
     * @param CbTextGenerationResult $textObject
     * @return bool
     */
    public function postToBlog(){
        $WPRequestBody = array(
            'title' => 'test post',
            'content' => $this->textObject->getText(),
            'status' => 'draft',
            'type' => 'post',
            'meta' => array(
                'og:description' => 'test desriptions',
                'og:title' => 'test title'
            )
        );

        $provider = new Wordpress([
            'clientId'                => $this->blogObject->getClientId(),
            'clientSecret'            => $this->blogObject->getClientSecret(),
            'redirectUri'             => self::WP_BACKLINK,
            'domain'                  => $this->blogObject->getDomainName()
        ]);

        try {
            $accessToken = $provider->getAccessToken('password', [
                'username' => $this->blogObject->getPostingUserLogin(),
                'password' => $this->blogObject->getPostingUserPassword()
            ]);

            $body['action'] = 'write';
            $options['body'] = json_encode($WPRequestBody);
            $options['headers']['Content-Type'] = 'application/json;charset=UTF-8';
            $options['headers']['access_token'] = $accessToken->getToken();

            $request = $provider->getAuthenticatedRequest(
                'POST',
                $this->blogObject->getDomainName() . self::WP_RESOURCE_PATH,
                $accessToken->getToken(),
                $options
            );
            $response = $provider->getResponse($request);

            if(!isset($response['code'])){
                $this->blogObject->setLastPostDate(new \DateTime);
                return true;
            }else{
                $this->blogObject->setLastErrorMessage($response['code']);
            }

        } catch (IdentityProviderException $e) {
            $this->blogObject->setLastErrorMessage($e->getMessage());
        }

        return false;
    }

    /**
     * @param object $msg
     * @return void
     */
    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        $this->responceRoutingKey = $message->responceRoutingKey;

        //select available for posting, not locked blog with lowest post value
        $blogs = (array) $message->blogs;
        asort($blogs);
        foreach($blogs as $blogId => $counter){
            $this->blogObject = $this->blogModel->get($blogId);
            if($this->blogObject && $this->blogModel->lockBlogForPosting($this->blogObject)){
                var_dump($this->blogObject->getObjectId());
                break;
            }else{
                continue;
            }
        }

        $textId = implode('::', array($message->taskId, self::MESSAGE_GENERATION_KEY));
        $this->textObject = $this->textModel->getSingle($textId);

        if($this->blogObject && $this->textObject){
            if($this->postToBlog()){
                $this->sendCompletePostingMessage($message->taskId);
            }

            $this->blogObject->setLocked(false);
            $this->blogModel->upsert($this->blogObject);
        }
    }

    /**
     * @param string $blogId
     * @param string $taskId
     * @return void
     */
    private function sendCompletePostingMessage($taskId){

        $msg = array(
            'taskId' => implode( '::', array(self::CAMPAIGN_SERVICE_KEY, self::MESSAGE_POSTED_KEY)),
            'resultKey' => implode( '::', array($taskId, self::MESSAGE_POSTED_KEY)),
            'blogId' => $this->blogObject->getObjectId()
        );
        $this->amqp->publish(json_encode($msg), $this->responceRoutingKey);
    }

}