<?php
namespace AppBundle\Extension;

use CouchbaseBundle\CouchbaseService;
use AppBundle\Controller\Api\ApiController;
use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;

class SchedulerExtension
{
    protected $cb;
    protected $model;

    const THIS_SERVICE = 'scheduler';

    const GENERATION_ROUTING_KEY = 'srv.txtgen.v1';
    const POSTING_ROUTING_KEY = 'srv.posting.v1';
    const RESPONCE_ROUTING_KEY = 'srv.taskmanager.v1';

    const MESSAGE_GENERATION = 'generation';
    const MESSAGE_POSTING = 'posting';

    public function __construct(CouchbaseService $cb, $amqp)
    {
        $this->cb = $cb;
        $this->model = new TaskModel($this->cb);

        $this->amqp = $amqp;
    }

    /**
     * @param string $status
     * @return void
     */
    public function processTasks(string $status){

        switch($status){
            case CbTask::STATUS_NEW :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_NEW);
                if($task) {
                    $this->sendGenerationMessage($task, self::MESSAGE_GENERATION);
                    $this->updateTaskStatus($task, CbTask::STATUS_SCHEDULED_FOR_GENERATION);
                    return "Task {$task->getObjectId()} status updated: " . CbTask::STATUS_SCHEDULED_FOR_GENERATION;
                }
                break;

            case CbTask::STATUS_GENERATED :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_GENERATED);
                if($task) {
                    $this->sendPostingMessage($task, self::MESSAGE_POSTING);
                    $this->updateTaskStatus($task, CbTask::STATUS_SCHEDULED_FOR_POSTING);
                    return "Task {$task->getObjectId()} status updated: " . CbTask::STATUS_SCHEDULED_FOR_POSTING;
                }
                break;

            case CbTask::STATUS_POSTED :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_POSTED);
                if($task) {
                    $this->updateTaskStatus($task, CbTask::STATUS_COMPLETED);
                    return "Task {$task->getObjectId()} status updated: " . CbTask::STATUS_COMPLETED;
                }
                break;

            default:
                throw new \Exception('Wrong status provided');
        }
    }

    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        var_dump($message->taskId);
        //check messages and make needed tasks update
        //generated => change task status to "generated", send "posting" message
        //posted => change task status to "posted"

        // $this->updateTaskStatus($taskId, CbTask::STATUS_GENERATED);
        // $this->updateTaskStatus($taskId, CbTask::STATUS_POSTED);
    }

    /**
     * @param object $task
     * @param string $message
     * @return void
     */
    private function sendGenerationMessage(CbTask $task, string $message){
        $generatedTaskId = implode('::', array(self::THIS_SERVICE, $task->getObjectId()));

        $msg = array(
            'taskId' => $generatedTaskId,
            'resultKey' => $generatedTaskId . '::generated',
            'responceRoutingKey' => self::RESPONCE_ROUTING_KEY
        );

        $this->amqp->publish(serialize($msg), self::GENERATION_ROUTING_KEY);
    }

    /**
     * @param object $task
     * @param string $message
     * @return void
     */
    private function sendPostingMessage(CbTask $task, string $message){
        $generatedTaskId = implode('::', array(self::THIS_SERVICE, $task->getObjectId()));

        $msg = array(
            'taskId' => $generatedTaskId,
            'resultKey' => $generatedTaskId . '::posted',
            'responceRoutingKey' => self::RESPONCE_ROUTING_KEY
        );

        $this->amqp->publish(serialize($msg), self::POSTING_ROUTING_KEY);
    }

    /**
     * @param object $task
     * @param string $status
     */
    private function updateTaskStatus(CbTask $task, string $status){
        //update task with status
        try {
                $object = $this->model->get($task->getObjectId());

                if ($object != null) {
                    $object->setStatus($status);
                }

            $this->model->upsert($object);

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


}