<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Entity\CbTemplate;
use AppBundle\Repository\TemplateModel;

class AddTemplateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('template:add')
            ->setDescription('Add template');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        try {
            $cb = $this->getContainer()->get('couchbase.connector');
            $model = new TemplateModel($cb);

            $template = new CbTemplate();
            $template->setName('test name');
            $template->setTemplate('test text');

            $model->insert($template);

        }
        catch(Exception $e)
        {
            $output->println($e->getMessage());
        }
    }


}
