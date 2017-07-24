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
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/quickcheck/result", name="app_quickcheck_result")
     */
    public function resultAction(Request $request)
    {
        $template = $request->request->has('template')? $request->request->get('template') : null;

        $pPython = $this->getParameter('python_bin');
        $pScript = $this->getParameter('generator_home');

        $tmpDir = $this->getParameter('generator_tmp_dir');
        $templateDir = $this->getParameter('generator_template_dir');


        $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -v -t quickcheck";

        print_r($command);
        exit();



        $params = [];
        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR;

        return $this->render('quickcheck/result.html.twig', $params);
    }
}
