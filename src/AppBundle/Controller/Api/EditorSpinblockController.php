<?php

namespace AppBundle\Controller\Api;

use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\EditorExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class EditorSpinblockController extends Controller
{

    /**
     * @Route("/editor/spinblock/{template}", name="api_spinblock_get", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     *
     * @method ("GET")
     */
    public function getSpinblockData($template)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }
        $extEditor = new EditorExtension($this->getParameter('generator_user_dir'), $username, $template);

        return ApiResponse::resultValue($extEditor->getSpinblockData());
    }

    /**
     * @Route("/editor/spinblock/{template}", name="api_editor_spinblock_set", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     *
     * @method ("POST")
     */
    public function setSpinblockData(Request $request, $template)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }
        $extEditor = new EditorExtension($this->getParameter('generator_user_dir'), $username, $template);

        $data = json_decode($request->getContent(), true);
        /*
         * {
         * "meta": {}
         * "data": {spinblocks}
         * }
         */

        $extEditor->setSpinblockData($data['data']);
        return ApiResponse::resultOk();
    }
}
