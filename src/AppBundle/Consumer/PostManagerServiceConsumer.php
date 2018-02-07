<?php

namespace AppBundle\Consumer;

use AppBundle\Extension\PostManagerServiceExtension;
use AppBundle\Extension\SchedulerServiceExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;


class PostManagerServiceConsumer implements ConsumerInterface
{
    private $container;

    public function __construct(\appDevDebugProjectContainer $container) {
        $this->container = $container;
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $cb = $this->container->get('couchbase.connector');
        $amqp = $this->container->get('old_sound_rabbit_mq.post_manager_service_producer');

        $postManager = new PostManagerServiceExtension($cb, $amqp);

        $postManager->processMessage($msg);
    }
}