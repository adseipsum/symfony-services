<?php
namespace AppBundle\Extension;

use CouchbaseBundle\CouchbaseService;
use AppBundle\Entity\CbTask;
use AppBundle\Repository\TextGenerationResultModel;

class PostingServiceExtension
{
    protected $cb;
    protected $model;

    const THIS_SERVICE_KEY = 'post';
    const TASK_SERVICE_KEY = 'tsk';

    const RESPONCE_ROUTING_KEY = 'srv.taskmanager.v1';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->model = new TextGenerationResultModel($this->cb);
        $this->model->setBucket($this->cb->getBucketForType('TextGenerationResult'));

        $this->amqp = $amqp;
    }

    public function postToBlog($post){
        $username = 'admin';
        $password = 'sd45X4e9';

        $url = 'http://188.166.89.15:8181/wp-json/wp/v2/posts/';

        $options = array(
            'http' => array(
                'header' => array(
                    'Authorization: Basic ' . base64_encode( $username . ':' . $password ),
                    'Content-type: application/x-www-form-urlencoded'
                ),

                'method'  => 'POST',
                'content' => http_build_query($post)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result) {
            return true;
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