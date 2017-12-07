<?php

namespace AppBundle\Command;

use AppBundle\Entity\CbGeneratedText;
use AppBundle\Extension\EditorExtension;
use AppBundle\Extension\PythonToolsExtension;
use AppBundle\Extension\TextGeneratorExtension;
use AppBundle\Extension\UtilsExtension;
use AppBundle\Repository\GeneratedTextModel;
use AppBundle\Repository\TemplateModel;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
                'Number of text to generate')
            ->addArgument(
                'Ngram',
                InputArgument::REQUIRED,
                'Process with NGRAM')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $cb = $this->getContainer()->get('couchbase.connector');
            $templateId = $input->getArgument('TemplateId');
            $amount =  $input->getArgument('Amount');
            $doNgram = $input->getArgument('Ngram');

            $mdGeneratedText = new GeneratedTextModel($cb);
            $mdGeneratedText->warmup();
            $mdTemplate = new TemplateModel($cb);


            $cbTemplate = $mdTemplate->get($templateId);
            $templateName = $cbTemplate->getName();
            $currentTextNumber = $mdGeneratedText->getGeneratedTextFromTemplateCount($templateId);
            $currentTextNumber += 1;
            $finalTextNumber = $currentTextNumber + $amount;


            $appdir = $this->getContainer()->get('kernel')->getRootDir();
            $filename = UtilsExtension::filter_filename($templateName);
            $outfile = "$appdir/../data/generated/$filename-$currentTextNumber-$finalTextNumber.txt";


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
            $ng_frame_size = 4;
            $ng_frame_ppb = 0.6;
            $ng_mode = 'insert';

            $extEditor = new EditorExtension(
                $countainer->getParameter('generator_user_dir'),
                $username,
                'globalTemplate'
            );

            $extPython = new PythonToolsExtension($countainer, $username);


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
                $text_to_save = $text;


                if($doNgram)
                {
                    $output->write("done..applying Ngramm...");
                    $text = $extPython->transformTextNGMC($text, $ng_frame_size, $ng_frame_ppb, $ng_mode, 'cb', false);
                    $output->writeln("done");
                }
                else {
                    $output->writeln("done");
                }

                if($first_row == true)
                {
                    $result_text = $text;
                    $first_row = false;
                }
                else {
                    $result_text = $result_text."\n".$separator."\n".$text;
                }
                UtilsExtension::forceFilePutContents($outfile, $result_text);


                $cbtext = new CbGeneratedText();
                $cbtext->setText($text_to_save);
                $cbtext->setTemplateId($templateId);
                $mdGeneratedText->upsert($cbtext);

            }


        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
