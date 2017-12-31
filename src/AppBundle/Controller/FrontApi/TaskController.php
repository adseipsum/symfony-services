<?php

namespace AppBundle\Controller\FrontApi;

use AppBundle\Entity\CbTask;
use AppBundle\Extension\ApiResponse;
use AppBundle\Repository\TaskModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class TaskController extends Controller
{
    /**
     * @Route("/task/new", name="frontapi_task_new")
     * @Method("OPTIONS")
     */
    public function addNewTask(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        var_dump($request->getContent()); die;

        try {
            $cb = $this->get('couchbase.connector');
            $model = new TaskModel($cb);

            $object = new CbTemplate();
            $object->setEnabled(true);
            $object->setDomainName($data['domainName']);
            $object->setNeedPosts($data['needPosts']);
            $object->setAdditionalKeysPercentage($data['additionalKeysPercentage']);
            $object->setPostPeriodDays($data['postPeriodDays']);
            $object->setCreated();
            $object->setStatus(CbTask::STATUS_NEW);

            $model->upsert($object);

            return ApiResponse::resultValues(true);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
