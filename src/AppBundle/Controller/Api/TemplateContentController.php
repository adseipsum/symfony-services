<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\CbTemplate;
use AppBundle\Entity\CbGeneratedText;
use AppBundle\Extension\ApiResponse;
use AppBundle\Repository\TemplateModel;
use AppBundle\Repository\GeneratedTextModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class TemplateContentController extends Controller
{

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/template/list", name="api_template_list")
     *
     * @method ("GET")
     */
    public function getTemplateList()
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new TemplateModel($cb);
            $model->warmup();
            /* @var $objects CbTemplate[] */
            $objects = $model->getAllObjects();

            $ret = [];

            foreach ($objects as $object) {
                $elem = [];
                $elem['id'] = $object->getObjectId();
                $elem['name'] = $object->getName();
                $ret[] = $elem;
            }

            usort($ret, function ($item1, $item2) {
                return strnatcasecmp($item1['name'], $item2['name']);
            });

            return ApiResponse::resultValues($ret);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/template/content/{templateId}", name="api_template_get", requirements={"template": "[a-zA-Z0-9\-\:]+"})
     *
     * @method ("GET")
     */
    public function getTemplateContent(string $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new TemplateModel($cb);
            /* @var $object CbTemplate */
            $object = $model->get($templateId);

            if ($object != null) {
                $ret = [];
                $ret['id'] = $object->getObjectId();
                $ret['name'] = $object->getName();
                $ret['template'] = $object->getTemplate();

                return ApiResponse::resultValue($ret);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/save/{templateId}",
     *     name="api_template_update",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method ("POST")
     */
    public function updateTemplateContent(Request $request, string $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new TemplateModel($cb);
            $data = json_decode($request->getContent(), true);

            /*
             * {
             * "id": "id"
             * "name" "name"
             * "template" "template"
             * }
             */

            if ($templateId == 'new') {
                $object = new CbTemplate();
                $object->setName($data['name']);
                $object->setTemplate($data['template']);
                $object->setValidate(false);
                $model->upsert($object);

                $ret = [];
                $ret['id'] = $object->getObjectId();
                $ret['name'] = $object->getName();
                $ret['template'] = $object->getTemplate();

                return ApiResponse::resultValue($ret);
            } else {
                /* @var $object CbTemplate */
                $object = $model->get($templateId);

                if ($object != null) {
                    $object->setName($data['name']);
                    $object->setTemplate($data['template']);
                    $object->setValidate(false);
                    $model->upsert($object);

                    $ret = [];
                    $ret['id'] = $object->getObjectId();
                    $ret['name'] = $object->getName();
                    $ret['template'] = $object->getTemplate();

                    return ApiResponse::resultValue($ret);
                } else {
                    return ApiResponse::resultNotFound();
                }
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/plus/{templateId}",
     *     name="api_template_usage_plus",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method ("POST")
     */
    public function usagePlusCount(Request $request, $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new GeneratedTextModel($cb);
            $data = json_decode($request->getContent(), true);

            $object = new CbGeneratedText();
            $object->setText($data['text']);
            $object->setTemplateId($templateId);
            $model->upsert($object);

            $ret = [];
            return ApiResponse::resultValue($ret);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/generated-text/list/{templateId}",
     *     name="api_generated_text_list",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method ("GET")
     */
    public function getGeneratedTextList(string $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new GeneratedTextModel($cb);
            $model->warmup();
            /* @var $objects CbGeneratedText[] */
            $objects = $model->listObjectsByTemplateId($templateId);

            $ret = [];

            if (isset($objects)) {
                foreach ($objects as $object) {
                    $elem = [];
                    $elem['id'] = $object->getObjectId();
                    $elem['text'] = $object->getText();
                    $elem['addDate'] = $object->getAddTime();
                    $ret[] = $elem;
                }

                usort($ret, function ($item1, $item2) {
                    return -1 * strnatcasecmp($item1['id'], $item2['id']);
                });
            }

            return ApiResponse::resultValues($ret);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/generated-text/remove/{generateTextId}",
     *     name="api_generated_text_remove",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method ("GET")
     */
    public function removeGeneratedText(string $generateTextId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new GeneratedTextModel($cb);
            $model->warmup();
            $model->removeByKey($generateTextId);

            $ret = [];

            return ApiResponse::resultValues($ret);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    /**
     * @Route(
     *     "/template/remove/{templateId}",
     *     name="api_template_remove",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method ("GET")
     */
    public function removeTemplate(string $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new TemplateModel($cb);
            $model->warmup();
            $model->removeByKey($templateId);

            $ret = [];

            return ApiResponse::resultValues($ret);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
