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

        $blocks = $extEditor->getSpinblockData();

        $params = [];
        $params['base_dir'] = realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR;

        $params['dictonary_json'] = json_encode($dict);
        $params['blocklist_json'] = json_encode($blocks);
        $params['rawtext'] = $extEditor->getRawtext();

        $params['blocklist'] = $blocks;

        return $this->render('editor/index.html.twig', $params);
    }
}
