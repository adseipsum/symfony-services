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

    const SERVICE = 'scheduler';

    const MESSAGE_GENERATION = 'generation';
    const MESSAGE_POSTING = 'posting';

    public function __construct(CouchbaseService $cb)
    {
        $this->cb = $cb;
        $this->model = new TaskModel($this->cb);
    }

    /**
     * @param string $status
     */
    public function processTasks(string $status){

        switch($status){
            case CbTask::STATUS_NEW :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_NEW);
                if($task) {
                    // $this->sendMessage(implode('::' array(SERVICE, $task->getOdjectId())), MESSAGE_GENERATION);
                    $this->updateTaskStatus($task->getObjectId(), CbTask::STATUS_SCHEDULED_FOR_GENERATION);
                    return "Task {$task->getObjectId()} status updated";
                }
                break;

            case CbTask::STATUS_GENERATED :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_GENERATED);
                if($task) {
                    // $this->sendMessage(implode('::' array(SERVICE, $task->getOdjectId())), MESSAGE_POSTING);
                    $this->updateTaskStatus($task->getObjectId(), CbTask::STATUS_SCHEDULED_FOR_POSTING);
                    return "Task {$task->getObjectId()} status updated";
                }
                break;

            case CbTask::STATUS_POSTED :
                $task = $this->model->getTasksByStatus(CbTask::STATUS_POSTED);
                if($task) {
                    $this->updateTaskStatus($task->getObjectId(), CbTask::STATUS_COMPLETED);
                    return "Task {$task->getObjectId()} status updated";
                }
                break;

            default:
                throw new \Exception('Wrong status provided');
        }
    }

    /**
     * @param string $taskId
     * @param string $message
     */
    private function sendMessage(string $taskId, string $message){
        //send rabbitMQ message regarding task
    }

    private function checkMessages(){
        //check messages and make needed tasks update
        //generated => change task status to "generated", send "posting" message
        //posted => change task status to "posted"

        // $this->updateTaskStatus($taskId, CbTask::STATUS_GENERATED);
        // $this->updateTaskStatus($taskId, CbTask::STATUS_POSTED);
    }

    /**
     * @param string $taskId
     * @param string $status
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