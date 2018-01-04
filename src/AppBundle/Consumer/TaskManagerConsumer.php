<?php

namespace AppBundle\Consumer;

use AppBundle\Extension\SchedulerExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;


class TaskManagerConsumer implements ConsumerInterface
{
    /**
    * @var AMQPMessage $msg
    * @return void
    */

    private $container;

    public function __construct(\appDevDebugProjectContainer $container) {
        $this->container = $container;
    }

    public function execute(AMQPMessage $msg)
    {
        $cb = $this->container->get('couchbase.connector');
        $amqp = $this->container->get('old_sound_rabbit_mq.task_manager_producer');

        $scheduler = new SchedulerExtension($cb, $amqp);

        $scheduler->processMessage($msg);
    }
}