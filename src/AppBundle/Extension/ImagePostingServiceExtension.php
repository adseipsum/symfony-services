<?php
namespace AppBundle\Extension;

use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use AppBundle\Entity\CbBlog;
use AppBundle\Repository\BlogModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Krombox\OAuth2\Client\Provider\Wordpress;

class ImagePostingServiceExtension
{
    protected $cb;
    protected $taskModel;
    protected $taskObject;
    protected $blogModel;
    protected $blogObject;

    const THIS_SERVICE_KEY = 'pst';

    const WP_RESOURCE_PATH = '/wp-json/wp/v2/media/';
    const WP_BACKLINK = 'oob';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->taskModel = new TaskModel($this->cb);
        $this->blogModel = new BlogModel($this->cb);
        $this->amqp = $amqp;
    }

    /**
     * @return bool
     */
    public function postToBlog(){

        $WPRequestBody = array(
            'title' => 'test post',
            'content' => 'test text',
            'status' => 'draft',
            'type' => 'post',
            'meta' => array(
                'og:description' => 'test descriptions',
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
            }else{
                $this->blogObject->setLastErrorMessage($response['code']);
            }

            $this->blogModel->upsert($this->blogObject);

            return true;

        } catch (IdentityProviderException $e) {
            echo $e->getMessage();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function postImageToBlog(){

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

            //$file = file_get_contents( getcwd() . '/logo.png' );
//            $handle                    = fopen(getcwd() . '/logo.png', 'r');
//            $fdata                     = fread($handle, filesize(getcwd() . '/logo.png'));
            //$options['body'] = $file;
            $options['body'] = file_get_contents(getcwd() . '/colorful-pills-650.jpg');

//                array(
//                    'name'     => 'file',
//                    'contents' => $fdata,
//                    'filename' => getcwd() . '/logo.png',
//            );
            //$options['headers']['cache-control'] = 'no-cache';

            $options['headers']['content-type'] = 'image/jpg';
            $options['headers']['content-disposition'] = 'attachment; filename="' . getcwd() . '/colorful-pills-650.jpg"' ;
            $options['headers']['access_token'] = $accessToken->getToken();

            $request = $provider->getAuthenticatedRequest(
                'POST',
                $this->blogObject->getDomainName() . self::WP_RESOURCE_PATH,
                $accessToken->getToken(),
                $options
            );

            $response = $provider->getResponse($request);

            if(isset($response['code'])){
                $this->blogObject->setLastErrorMessage($response['code']);
                $this->blogModel->upsert($this->blogObject);
            }

            if(isset($response['id'])) {
                return $response['id'];
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
            $this->blogObject = $this->blogModel->get('blog-2');
            $imageId = $this->postImageToBlog();
            if($imageId) {
                $this->sendCompleteImagePostingMessage($this->taskObject->getObjectId(), $imageId, $message->responseRoutingKey);
            }
        }
    }

    /**
     * @param string $taskId
     * @return void
     */
    private function sendCompleteImagePostingMessage($taskId, $imageId, $responseRoutingKey){
        $msg = array(
            'taskId' => implode( '::', array(self::THIS_SERVICE_KEY, $taskId, CbTask::STATUS_IMAGE_POST)),
            'imageId' => $imageId
        );

        $this->amqp->publish(json_encode($msg), $responseRoutingKey);
    }

}