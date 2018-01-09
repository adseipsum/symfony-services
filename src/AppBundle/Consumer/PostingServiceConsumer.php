<?php

namespace AppBundle\Consumer;

use AppBundle\Extension\PostingServiceExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;


class PostingServiceConsumer implements ConsumerInterface
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
        $amqp = $this->container->get('old_sound_rabbit_mq.posting_service_producer');

        $postingService = new PostingServiceExtension($cb, $amqp);

        $postingService->processMessage($msg);
    }
}