<?php

namespace AppBundle\Controller\FrontApi;

use Rbl\CouchbaseBundle\Entity\CbSatConfig;
use Rbl\CouchbaseBundle\Model\SatConfigModel;
use AppBundle\Extension\ApiResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Rbl\CouchbaseBundle\CouchbaseService;


class SatConfigController extends Controller
{

    private $cb;
    private $satConfigModel;

    //here is temporarly solution, we need to realize if config will be connected to user
    const SAT_CONFIG = 'satconfig-1';

    public function __construct(CouchbaseService $cb)
    {
        $this->cb = $cb;
        $this->satConfigModel = new SatConfigModel($this->cb);
    }

    /**
     * @Route("/config/save", name="frontapi_config_save")
     * @param Request $request
     * @Method("POST")
     * @return ApiResponse
     */
    public function saveSatConfig(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        try {
            $object = $this->satConfigModel->get(self::SAT_CONFIG);
            $object->setAdditionalKeywords($data['additionalKeywords']);
            $this->satConfigModel->upsert($object);

            return new ApiResponse(true);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/config/show", name="frontapi_config_show")
     * @Method("GET")
     * @return ApiResponse
     */
    public function showSatConfig()
    {
        try {
            $satConfigObject = $this->satConfigModel->get(self::SAT_CONFIG);

            if (!$satConfigObject) {
                return ApiResponse::resultNotFound();
            }

            $config = array(
                'additionalKeywords' => $satConfigObject->getAdditionalKeywords(),
            );

            return new ApiResponse($config);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }/////////////////////////////////////////////////////////////////////////////////

}
