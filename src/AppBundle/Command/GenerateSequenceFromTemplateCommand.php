<?php

namespace AppBundle\Command;

use AppBundle\Entity\CbGeneratedText;
use AppBundle\Extension\EditorExtension;
use AppBundle\Extension\TextGeneratorExtension;
use AppBundle\Extension\UtilsExtension;
use AppBundle\Repository\GeneratedTextModel;
use AppBundle\Repository\TemplateModel;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserBundle\Entity\CbUser;
use UserBundle\Repository\UserModel;

class GenerateSequenceFromTemplateCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('app:generator:multiple')->setDescription('Call generator to generate multiple text from template')
             ->addArgument(
                'TemplateId',
                InputArgument::REQUIRED,
                'Id of template to generate from')
            ->addArgument(
                'Amount',
                InputArgument::REQUIRED,
                'Number of text to generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $cb = $this->getContainer()->get('couchbase.connector');
            $templateId = $input->getArgument('TemplateId');
            $amount =  $input->getArgument('Amount');


            $mdGeneratedText = new GeneratedTextModel($cb);
            $mdTemplate = new TemplateModel($cb);


            $cbTemplate = $mdTemplate->get($templateId);
            $templateName = $cbTemplate->getName();
            $currentTextNumber = $mdGeneratedText->getGeneratedTextFromTemplateCount($templateId);
            $finalTextNumber = $currentTextNumber + $amount;


            $appdir = $this->getContainer()->get('kernel')->getRootDir();
            $filename = UtilsExtension::filter_filename($templateName);
            $outfile = "$appdir/data/generated/$filename-$currentTextNumber-$finalTextNumber.txt";


            $output->writeln("Working with template $templateName");
            $output->writeln("Output file $outfile");

            $drugName = null;
            $useStemmer = true;
            $removeStopwords = true;
            $generateLoop = 50;
            $deviation = 100;
            $username = 'serverside';
            $countainer = $this->getContainer();
            $separator = '*****';


            $extEditor = new EditorExtension(
                $countainer->getParameter('generator_user_dir'),
                $username,
                'globalTemplate'
            );


            $result_text = '';
            $first_row = true;

            for($i = 1; $i<$amount+1; $i++)
            {
                $output->write("Generating text $i...");
                $result  = TextGeneratorExtension::generateForTemplate(
                    $countainer,
                    $username,
                    $extEditor,
                    $templateId . '.tpl',
                    $cbTemplate->getTemplate(),
                    $cbTemplate->isValidate(),
                    $cb,
                    $templateId,
                    $drugName,
                    $removeStopwords,
                    $useStemmer,
                    $generateLoop,
                    $deviation,
                    false
                );
                $text = $result['generated'];

                $output->writeln("done");

                if($first_row == true)
                {
                    $result_text = $result_text.'\n'.$separator.$text;
                    $first_row = false;
                }
                else {
                    $result_text = $result_text.'\n'.$separator.$text;
                }
                UtilsExtension::forceFilePutContents($outfile, $result_text);


                $cbtext = new CbGeneratedText();
                $cbtext->setText($text);
                $cbtext->setTemplateId($templateId);
                $mdGeneratedText->upsert($cbtext);

            }


        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
