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

        file_put_contents($templateFile, $baseTemplate."\n".$template);

        $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -v -t quickcheck";

        exec($command, $output);
        $out_finished = '';
        $brCount = 0;
        foreach($output as $line) {
            $out_finished .= $line . "<br>";
            $brCount++;
        }


        $params = [];
        $params['template'] = $template;
        $params['result'] = $out_finished;
        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR;

        return $this->render('quickcheck/result.html.twig', $params);
    }
}
