<?php

namespace AppBundle\Controller\Web;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DemoController extends Controller
{
    /**
     * @Route("/demo/main", name="app_demo_index")
     */
    public function indexAction(Request $request)
    {

        $params = [];
        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR;
        $params['drug_name'] = '';
        $params['drug_id'] = '';

        return $this->render('demo/index.html.twig', $params);
    }

    /**
     * @Route("/demo/result", name="app_demo_result")
     */
    public function resultAction(Request $request)
    {
        $drug_name = $request->request->has('drug_name')? $request->request->get('drug_name') : null;
        $drug_id = $request->request->has('drug_id')? $request->request->get('drug_id') : null;

        $pPython = $this->getParameter('python_bin');
        $pScript = $this->getParameter('generator_home');

        $command = "cd $pScript && $pPython $pScript/render.py -t drug_info_2";

        if($drug_name != null)
        {
            $command = $command.' -dn '.$drug_name;
        }
        else if($drug_id != null)
        {
            $command = $command.' -di '.$drug_id;
        }

        $out_finished = '';
        $cycles = 0;
        while(true)
        {
            $cycles++;
            $out_finished = '';
            $output = '';

            exec("$command", $output);

            $brCount = 0;
            foreach($output as $line) {
                $out_finished .= $line . "<br>";
                $brCount++;
            }

            if (strpos($out_finished, "!!! Error input params !!!") !== false) {
                break;
            }

            if ((strlen($out_finished) > 1000 + ($brCount * 3)) || $cycles > 30) {
                break;
            }
        }

        $params = [];
        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR;
        $params['drug_name'] = $drug_name != null ? $drug_name  : '';
        $params['drug_id'] = $drug_id != null ? $drug_id  : '';
        $params['result'] = $out_finished;


        return $this->render('demo/result.html.twig', $params);
    }
}
