<?php

namespace AppBundle\Controller\Web;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class EditorController extends Controller
{
    /**
     * @Route("/editor/main", name="app_editor_index")
     */
    public function indexAction(Request $request)
    {


        $dict = [];
        $dict['run'] = implode("\n", ['run','process', 'handle', 'get trough']);
        $dict['amazing'] = implode("\n", ['amazing','wonderfull', 'outstanding', 'fantastic', 'very good']);
        $dict['bad'] = implode("\n", ['bad', 'poor', 'creepy']);


        $blocks = [];

        // Datablock
        $block = [];
        $block['name'] = 'Database block';
        $block['type'] = 'database';
        $block['variables'] = [];

        $variable = [];
        $variable['name'] = 'drug';
        $variable['type'] = 'string';
        $variable['description'] = 'Название препарата';
        $block['variables'][] = $variable;

        $variable = [];
        $variable['name'] = 'disease';
        $variable['type'] = 'string';
        $variable['description'] = 'Название болезни';
        $block['variables'][] = $variable;

        $block['readonly'] = true;
        $block['index'] = 0;
        $block['indexmodifible'] = false;
        $blocks[] = $block;

        // spin-sentence

        $block = [];
        $block['name'] = 'Title - Prozac thread depression';
        $block['type'] = 'spinsentence';
        $block['readonly'] = false;
        $block['values'] = implode("\n", ['bad', 'poor', 'creepy']);
        $block['index'] = 1;
        $block['indexmodifible'] = true;
        $blocks[] = $block;
        // static
        $block = [];
        $block['name'] = 'intro';
        $block['type'] = 'static';
        $block['readonly'] = false;
        $block['value'] = implode("\n", ['bad', 'poor', 'creepy']);
        $block['index'] = 2;
        $block['indexmodifible'] = true;
        $blocks[] = $block;

        // Add new block

        $block = [];
        $block['name'] = 'Добавить новый блок';
        $block['type'] = 'newblock';
        $block['readonly'] = false;
        $block['index'] = 10000;
        $block['indexmodifible'] = false;
        $blocks[] = $block;

        $params = [];
        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR;
        $params['dictonary'] = $dict;
        $params['dictonary_json'] = json_encode($dict);

        $params['blocklist'] = $blocks;


        return $this->render('editor/index.html.twig', $params);
    }

}
