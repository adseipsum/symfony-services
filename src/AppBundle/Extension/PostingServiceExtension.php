<?php
namespace AppBundle\Extension;

use CouchbaseBundle\CouchbaseService;
use AppBundle\Entity\CbTask;
use AppBundle\Repository\TextGenerationResultModel;
use League\OAuth2\Client\Provider\GenericProvider as GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException as IdentityProviderException;
use Krombox\OAuth2\Client\Provider\Wordpress as Wordpress;

class PostingServiceExtension
{
    protected $cb;
    protected $model;

    const THIS_SERVICE_KEY = 'post';
    const TASK_SERVICE_KEY = 'tsk';

    const RESPONCE_ROUTING_KEY = 'srv.taskmanager.v1';

    const WP_RESOURCE_PATH = 'wp-json/wp/v2/posts/';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->model = new TextGenerationResultModel($this->cb);
        $this->model->setBucket($this->cb->getBucketForType('TextGenerationResult'));

        $this->amqp = $amqp;
    }

    public function postToBlog($body){

        //comes from blogs data
        $temp_clientId = 'lxkV5q0Y8OZWlpcN6ku6MI0oxMX5oE3tDPCmJ4o0';
        $temp_clientSecret = 'IBNGi39suoTe2fnzimuYuGMgKyGbKFaXUb4RWVOU';
        $username = 'admin';
        $password = 'sd45X4e9';
        $domain = 'http://188.166.89.15:8181/';

        $provider = new Wordpress([
            'clientId'                => $temp_clientId,    // The client ID assigned to you by the provider
            'clientSecret'            => $temp_clientSecret,   // The client password assigned to you by the provider
            'redirectUri'             => 'oob',
            'domain'                  => $domain
        ]);

        try {

            $accessToken = $provider->getAccessToken('password', [
                'username' => $username,
                'password' => $password
            ]);

            $body['action'] = 'write';
            $options['body'] = json_encode($body);
            $options['headers']['Content-Type'] = 'application/json;charset=UTF-8';
            $options['headers']['access_token'] = $accessToken->getToken();

            $request = $provider->getAuthenticatedRequest(
                'POST',
                $domain . self::WP_RESOURCE_PATH,
                $accessToken->getToken(),
                $options
            );
            $response = $provider->getResponse($request);

            if(!isset($response['code'])){
                return true;
            }

        } catch (IdentityProviderException $e) {
            exit($e->getMessage());
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

        $textId = implode('::', array($idString[1], CbTask::STATUS_SCHEDULED_FOR_GENERATION));
        $object = $this->model->get($textId);

        if($object){
            $data = array(
                'title' => 'test post',
                'content' => $object->getText(),
                'status' => 'publish'
            );

            if($this->postToBlog($data)){
                $this->sendCompletePostingMessage($idString[1]);
            }
        }
    }

    /**
     * @param string $taskId
     * @return void
     */
    private function sendCompletePostingMessage($taskId){
        $postedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $taskId, CbTask::STATUS_POSTED));

        $msg = array(
            'taskId' => implode( '::', array(self::TASK_SERVICE_KEY, $taskId)),
            'resultKey' => $postedTaskId
        );

        $this->amqp->publish(json_encode($msg), self::RESPONCE_ROUTING_KEY);
    }

}