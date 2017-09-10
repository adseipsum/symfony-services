<?php

namespace AppBundle\Controller\Api;

use AppBundle\Extension\ApiResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    /**
     * @Route("/", name="api_index")
     */
    public function indexAction(Request $request)
    {
        return ApiResponse::resultOk();
    }
}
