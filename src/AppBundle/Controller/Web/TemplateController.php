<?php

namespace AppBundle\Controller\Web;

use AppBundle\Extension\EditorExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TemplateController extends Controller
{

    /**
     * @Route("/template/main", name="app_template_index")
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

        return $this->render('template/index.html.twig', $params);
    }
}
