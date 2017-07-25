<?php

namespace AppBundle\Controller\Web;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class QuickCheckController extends Controller
{
    /**
     * @Route("/quickcheck/main", name="app_quickcheck_index")
     */
    public function indexAction(Request $request)
    {

        return $this->render('quickcheck/index.html.twig', [
            'template' => '',
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/quickcheck/result", name="app_quickcheck_result")
     */
    public function resultAction(Request $request)
    {
        $template = $request->request->has('template')? $request->request->get('template') : '';

        $pPython = $this->getParameter('python_bin');
        $pScript = $this->getParameter('generator_home');

        $userDir = $this->getParameter('generator_user_dir');
        $baseTemplate = $this->getParameter('generator_quickcheck_base');

        $username = $this->getUser()->getUsernameCanonical();

        $tmpDir = "$userDir/$username/tmp";
        $templateDir = "$userDir/$username/template";
        $templateFile = "$userDir/$username/template/quickcheck/main.tpl";


        $base_template_content = file_get_contents($baseTemplate);

        file_put_contents($templateFile, $base_template_content."\n".$template);

        $combined_template_content = file_get_contents($templateFile);

        $command_validate = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -v -t quickcheck";


        exec($command_validate, $output_validate);

        $validate_ok = true;

        $out_validate_finished = '';

        $template_text_lines = preg_split("/\\n/", $template);
        $template_lines = [];
        $first_line = $this->getLineCount($base_template_content);
        $count = 0;

        foreach ($template_text_lines as $line)
        {
            $elem['linenum'] = $first_line+$count;
            $elem['text'] = $line;
            $elem['is_valid'] = true;
            $template_lines[] = $elem;
            $count++;
        }

        $out_validate_text = '';

        foreach($output_validate as $line) {
            if(strpos($line, 'TemplateRenderException:') === false)
            {
                // do nothing, wierd logic when match at 0 position != false is true, but === false is false
            }
            else {
                $validate_ok = false;
                preg_match_all('/\(([0-9\:\~\?]+?)\)/', $line, $errors);

                foreach ($errors as $error)
                {
                    $pos = preg_split("/\:/",$error[0]);
                    $linenum = intval(str_replace('(','',$pos[0]));

                    $linenum--;

                    foreach ($template_lines as &$tline)
                    {
                        if($tline['linenum']== $linenum)
                        {
                            $tline['is_valid'] = false;
                        }
                    }
                }
                $out_validate_text = $line."\n";
            }
        }

        if($validate_ok == true)
        {
            $out_finished = '';
            $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -t quickcheck";

            exec($command, $output);

            $brCount = 0;
            foreach($output as $line) {
                $out_finished .= $line . "\n";
                $brCount++;
            }
        }
        else {
            $out_finished = 'ERROR';
        }

        $params = [];
        $params['template'] = $template;
        $params['result'] = $out_finished;
        $params['validation_lines'] = $template_lines;
        $params['validation_text'] = $out_validate_text;
        $params['validation_status'] = $validate_ok;



        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR;

        return $this->render('quickcheck/result.html.twig', $params);
    }


    // Helpers


    function getLineNumber($line)
    {
        $bpos = strpos($line, '(')+1;
        $epos = strpos($line, ':', $bpos);

        $sub = substr($line,$bpos,$epos-$bpos);

        return intval($sub);
    }

    function getStripedError($line)
    {
        $bpos = 0;
        $epos = strpos($line, ' in file ', $bpos);

        $sub = substr($line,$bpos,$epos-$bpos);

        return $sub;
    }


    function getLine($text, $linenumber)
    {
        $lines = preg_split("/\\n/",$text);
        return $lines[$linenumber-1];
    }

    function getLineCount($text)
    {
        $lines = preg_split("/\\n/",$text);
        return count($lines);
    }
}
