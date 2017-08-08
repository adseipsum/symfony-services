<?php

namespace AppBundle\Controller\Web;

use AppBundle\Extension\EditorExtension;
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

        $username = $this->getUser()->getUsernameCanonical();
        $template = 'default';
        $userdir = $this->getParameter('generator_user_dir');

        $extEditor = new EditorExtension($userdir, $username, $template);
        $dict = $extEditor->getGlobalDictonary();


        $blocks = [];

        // Datablock
        $block = [];
        $block['name'] = 'Database block';
        $block['type'] = EditorExtension::BLOCK_DATABASE;
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


        // New Block


        /*

        // spin-sentence

        $block = [];
        $block['name'] = 'Title - Prozac thread depression';
        $block['type'] = EditorExtension::BLOCK_SPINSENTENCES;
        $block['readonly'] = false;
        $block['values'] = implode("\n", ['bad', 'poor', 'creepy']);
        $block['index'] = 1;
        $block['indexmodifible'] = true;
        $blocks[] = $block;
        // static
        $block = [];
        $block['name'] = 'intro';
        $block['type'] = EditorExtension::BLOCK_STATICTEXT;
        $block['readonly'] = false;
        $block['value'] = implode("\n", ['bad', 'poor', 'creepy']);
        $block['index'] = 2;
        $block['indexmodifible'] = true;
        $blocks[] = $block;

        // static
        $block = [];
        $block['name'] = 'intro 2';
        $block['type'] = EditorExtension::BLOCK_STATICTEXT;
        $block['readonly'] = false;
        $block['value'] = implode("\n", ['bad', 'poor', 'creepy']);
        $block['index'] = 3;
        $block['indexmodifible'] = true;
        $blocks[] = $block;

        */

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

        $params['dictonary_json'] = json_encode($dict);
        $params['blocklist_json'] = json_encode($blocks);
        $params['rawtext'] = $extEditor->getRawtext();


        $params['blocklist'] = $blocks;


        return $this->render('editor/index.html.twig', $params);
    }

}
