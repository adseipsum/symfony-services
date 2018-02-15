<?php

namespace AppBundle\Command;

use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use AppBundle\Extension\SchedulerServiceExtension;
use AppBundle\Repository\CampaignModel;
use AppBundle\Entity\CbCampaign;
use AppBundle\Repository\BlogModel;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCampaignSchedulerCommand extends ContainerAwareCommand
{
    const THIS_SERVICE_KEY = 'csd';
    const RESPONCE_ROUTING_KEY = 'srv.cmpmanager.v1';
    const POST_MANAGER_ROUTING_KEY = 'srv.postmanager.v1';

    protected function configure()
    {
        $this->setName('campaign-scheduler:run')->setDescription('Process campaigns for posting');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //check if any campaigns ready for posting
        //search from list of the campaign blogs for one we can post on and lock it
        //send message to Post Manager with new task
        try {
            $cb = $this->getContainer()->get('couchbase.connector');

            $campaignModel = new CampaignModel($cb);
            $campaignObject = $campaignModel->getCampaignsByStatus(CbCampaign::STATUS_READY);

            //stop if none of campaigns ready for posting
            if(!$campaignObject){
                return false;
            }

            if(time() > $campaignObject->getNextPostTime()->getTimestamp()){
                $blogModel = new BlogModel($cb);
                $blogs = $campaignObject->getBlogs();

                //make sure we will start from blogs with lesser amount of posts
                asort($blogs);
                var_dump($blogs);
                foreach($blogs as $blogId => $counter){
                    $blogObject = $blogModel->get($blogId);
                    if($blogObject && $blogModel->lockBlogForPosting($blogObject)){
                        break;
                    }else{
                        continue;
                    }
                }
                var_dump($blogObject->getObjectId());
                if(!isset($blogObject)){
                    $output->writeln('No blogs ready for posting for campaign ' . $campaignObject->getObjectId());
                    return false;
                }

                $taskModel = new TaskModel($cb);
                $taskId = $taskModel->createTask($blogObject->getObjectId(), $campaignObject->getObjectId());

                $generatedTaskId = implode('::', array(self::THIS_SERVICE_KEY, $taskId, CbTask::STATUS_NEW));

                $msg = array(
                    'taskId' => $generatedTaskId,
                    'responseRoutingKey' => self::RESPONCE_ROUTING_KEY
                );

                //send message to post manager service
                $amqp = $this->getContainer()->get('old_sound_rabbit_mq.campaign_scheduler_producer');
                $amqp->publish(json_encode($msg), self::POST_MANAGER_ROUTING_KEY);

                $campaignObject->setStatus(CbCampaign::STATUS_PROCESSING);
                $campaignModel->upsert($campaignObject);
            }

            $output->writeln($campaignObject->getObjectId() . ' processed');
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
