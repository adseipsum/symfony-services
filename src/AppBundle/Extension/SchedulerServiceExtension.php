<?php
namespace AppBundle\Extension;

use CouchbaseBundle\CouchbaseService;
use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;

class SchedulerServiceExtension
{
    protected $cb;
    protected $model;

    const THIS_SERVICE_KEY = 'tsk';

    const GENERATION_ROUTING_KEY = 'srv.txtgen.v1';
    const POSTING_ROUTING_KEY = 'srv.posting.v1';
    const RESPONCE_ROUTING_KEY = 'srv.taskmanager.v1';

    const MESSAGE_GENERATION_KEY = 'generation';

    /**
     * @param object $cb
     * @param object $amqp
     * @return void
     */
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
                    $this->sendGenerationMessage($task);
                    $this->updateTaskStatus($task->getObjectId(), CbTask::STATUS_SCHEDULED_FOR_GENERATION);
                    return "Task {$task->getObjectId()} status updated: " . CbTask::STATUS_SCHEDULED_FOR_GENERATION;
                }
                break;

            case CbTask::STATUS_GENERATED :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_GENERATED);
                if($task) {
                    $this->sendPostingMessage($task);
                    $this->updateTaskStatus($task->getObjectId(), CbTask::STATUS_SCHEDULED_FOR_POSTING);
                    return "Task {$task->getObjectId()} status updated: " . CbTask::STATUS_SCHEDULED_FOR_POSTING;
                }
                break;

            case CbTask::STATUS_POSTED :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_POSTED);
                if($task) {
                    $this->updateTaskStatus($task->getObjectId(), CbTask::STATUS_COMPLETED);
                    return "Task {$task->getObjectId()} status updated: " . CbTask::STATUS_COMPLETED;
                }
                break;

            default:
                throw new \Exception('Wrong status provided');
        }
    }

    /**
     * @param object $msg
     * @return void
     */
    public function processMessage($msg){
        $message = json_decode($msg->getBody());
        $idString = explode('::', $message->taskId);

        if(strstr($message->resultKey, CbTask::STATUS_SCHEDULED_FOR_GENERATION)){
            $this->updateTaskStatus($idString[1], CbTask::STATUS_GENERATED);
        }elseif(strstr($message->resultKey, CbTask::STATUS_POSTED)){
            $this->updateTaskStatus($idString[1], CbTask::STATUS_POSTED);
        }
    }

    /**
     * @param object $task
     * @return void
     */
    private function sendGenerationMessage(CbTask $task){
        $generatedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $task->getObjectId()));
        $generatedResultKeyId = implode('::', array(self::THIS_SERVICE_KEY, $task->getObjectId(), self::MESSAGE_GENERATION_KEY));
        $msg = array(
            'taskId' => $generatedTaskId,
            'resultKey' => $generatedResultKeyId,
            'responceRoutingKey' => self::RESPONCE_ROUTING_KEY
        );
        $this->amqp->publish(json_encode($msg), self::GENERATION_ROUTING_KEY);
    }

    /**
     * @param object $task
     * @param string $key
     * @return void
     */
    private function sendPostingMessage(CbTask $task){
        $generatedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $task->getObjectId()));

        $msg = array(
            'taskId' => $generatedTaskId,
            'responceRoutingKey' => self::RESPONCE_ROUTING_KEY
        );
        $this->amqp->publish(json_encode($msg), self::POSTING_ROUTING_KEY);
    }

    /**
     * @param string $taskId
     * @param string $status
     * @return void
     */
    private function updateTaskStatus(string $taskId, string $status){
        //update task with status
        try {
                $object = $this->model->get($taskId);

                if ($object != null) {
                    $object->setStatus($status);
                }

            $this->model->upsert($object);

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


}