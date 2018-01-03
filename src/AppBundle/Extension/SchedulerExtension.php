<?php
namespace AppBundle\Extension;


use AppBundle\Controller\Api\ApiController;
use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;

class SchedulerExtension
{
    protected $cb;

    protected $modelTask;

    const MESSAGE_GENERATION = 'generation';

    const MESSAGE_POSTING = 'posting';

    public function __construct(CouchbaseService $cb, EncoderFactoryInterface $encoder)
    {
        $this->cb = $cb;
        $this->modelTask = new UserModel($cb);
    }

    /**
     * @return TaskModel
     */
    public function processTasks(){
        $newTasks = $this->modelTask->getTasksByStatus(CbTask::STATUS_NEW);

        foreach($newTasks as $task){
            // $this->sendMessage($taskId, MESSAGE_GENERATION);
        }

        $this->modelTask->getTasksByStatus(CbTask::STATUS_GENERATED);

        foreach($newTasks as $task){
            // $this->sendMessage($taskId, MESSAGE_POSTING);
        }
    }

    /**
     * @param string $taskId
     *
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
     */
    private function updateTaskStatus(string $taskId, string $status){
        //update task with status
        try {
                /* @var $object CbTask */
                $object = $this->modelTask->get($taskId);

                if ($object != null) {
                    $object->setStatus($status);
                }

                $this->modelTask->upsert($object);

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


}