<?php

namespace AppBundle\Controller\Api;

use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\EditorExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class EditorInfoController extends Controller
{
    /**
     * @Route("/editor/rawtext/{template}", name="api_editor_rawtext_get", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     * @Method("GET")
     */
    public function getRawtext($template)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if($username == null)
        {
            return ApiResponse::resultUnauthorized();
        }
        $extEditor = new EditorExtension($this->getParameter('generator_user_dir'), $username, $template);

        return ApiResponse::resultValue($extEditor->getRawtext());

    }

    /**
     * @Route("/editor/rawtext/{template}", name="api_editor_rawtext_set", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     * @Method("POST")
     */
    public function setRawtext(Request $request, $template)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if($username == null)
        {
            return ApiResponse::resultUnauthorized();
        }
        $extEditor = new EditorExtension($this->getParameter('generator_user_dir'), $username, $template);

        $data = json_decode($request->getContent(), true);
        /*
         * {
         *    "meta": {}
         *    "data": "somenicetext"
         * }
         */

        $extEditor->setRawtext($data['data']);
        return ApiResponse::resultOk();

    }

}
