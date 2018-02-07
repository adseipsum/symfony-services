<?php

namespace AppBundle\Consumer;

use AppBundle\Extension\ImagePostingServiceExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;


class ImagePostingServiceConsumer implements ConsumerInterface
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
        $amqp = $this->container->get('old_sound_rabbit_mq.image_posting_service_producer');

        $imagePostingService = new ImagePostingServiceExtension($cb, $amqp);

        $imagePostingService->processMessage($msg);
    }
}