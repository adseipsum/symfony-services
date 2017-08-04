<?php

namespace AppBundle\Controller\Api;

use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\DictionaryExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class EditorDictionaryController extends Controller
{
    /**
     * @Route("/editor/dictionary/{template}", name="api_editor_dictonary_get", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     * @Method("GET")
     */
    public function getDictonary($template)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if($username == null)
        {
            return ApiResponse::resultUnauthorized();
        }


        $extDict = new DictionaryExtension($this->getParameter('generator_user_dir'), $username, $template);

        return ApiResponse::resultValues($extDict->getGlobalDictonary());

    }

    /**
     * @Route("/editor/dictionary/{template}", name="api_editor_dictonary_get", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     * @Method("POST")
     */
    public function setDictonary(Request $request, $template)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if($username == null)
        {
            return ApiResponse::resultUnauthorized();
        }
        $extDict = new DictionaryExtension($this->getParameter('generator_user_dir'), $username, $template);

        $data = json_decode($request->getContent(), true);
        /*
         * {
         *    "meta": {}
         *    "data": {
         *           "value": "value1\nvalue2\nvalue3\n
         *           "valueBig": "valueBig1\nvalueBig2\nvalue3
         *           }
         * }
         */

        $extDict->setGlobalDictonary($data['data']);
        return ApiResponse::resultOk();

    }

}
