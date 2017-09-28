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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/template/list", name="api_template_list")
     *
     * @method ("GET")
     *
     * @return ApiResponse
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/template/content/{templateId}", name="api_template_get", requirements={"template": "[a-zA-Z0-9\-\:]+"})
     *
     * @method("GET")
     *
     * @param string $templateId
     * @return ApiResponse
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/save/{templateId}",
     *     name="api_template_update",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method("POST")
     *
     * @param Request $request
     * @param string $templateId
     * @return ApiResponse
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/plus/{templateId}",
     *     name="api_template_usage_plus",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method("POST")
     *
     * @param Request $request
     * @param string $templateId
     * @return ApiResponse
     */
    public function usagePlusCount(Request $request, string $templateId)
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/generated-text/list/{templateId}",
     *     name="api_generated_text_list",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method("GET")
     *
     * @param string $templateId
     * @return ApiResponse
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/generated-text/remove/{generateTextId}",
     *     name="api_generated_text_remove",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method("GET")
     *
     * @param string $generateTextId
     * @return ApiResponse
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/remove/{templateId}",
     *     name="api_template_remove",
     *     requirements={"template": "[a-zA-Z0-9\-\:]+"}
     * )
     *
     * @method("GET")
     *
     * @param string $templateId
     * @return ApiResponse
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $text
     * @param string $spin_regexp
     * @return array
     */
    private static function findAllSpinInTempate(string $text, string $spin_regexp) : array
    {
        $ret = [];

        preg_match_all($spin_regexp, $text, $matches, PREG_OFFSET_CAPTURE, 0);
        foreach ($matches[2] as $val) {
            $finded_val = $val[0];

            $ret_temp = [];

            $balance = 0;
            $begin_left_spin = $val[1] - 1;
            if ($text[$begin_left_spin] === '|') {
                $begin_left_spin--;

                for ($i = $begin_left_spin; $i >= 0; $i--) {
                    $c = $text[$i];
                    if ($c === '}' || $c === ']') {
                        $balance++;
                        continue;
                    }
                    if (($c === '[' || $c === '|') && $balance === 0) {
                        $str = substr($text, $i + 1, $begin_left_spin - $i - 1);
                        $str = trim($str);
                        if (!empty($str)) {
                            array_push($ret_temp, $str);
                        }
                        $begin_left_spin = $i;
                    }
                    if ($c === '{' || $c === '[') {
                        $balance--;
                        if ($balance < 0) {
                            break;
                        }
                    }
                }

            }

            $ret_temp = array_reverse($ret_temp);
            array_push($ret_temp, trim($finded_val));

            $text_len = strlen($text);
            $end_spin = $val[1] + strlen($finded_val);
            $balance = 0;
            if ($end_spin < $text_len && $text[$end_spin] === '|') {
                $end_spin++;

                for ($i = $end_spin; $i < $text_len; $i++) {
                    $c = $text[$i];
                    if ($c === '{' || $c === '[') {
                        $balance++;
                        continue;
                    }
                    if (($c === ']' || $c === '|') && $balance === 0) {
                        $str = substr($text, $end_spin, $i - $end_spin);
                        $str = trim($str);
                        if (!empty($str)) {
                            array_push($ret_temp, $str);
                        }
                        $end_spin = $i + 1;
                    }
                    if ($c === '}' || $c === ']') {
                        $balance--;
                        if ($balance < 0) {
                            break;
                        }
                    }
                }

            }

            array_push($ret, $ret_temp);
        }

        return $ret;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/find_all_spin",
     *     name="api_template_find_all_spin"
     * )
     *
     * @method("POST")
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function findAllSpin(Request $request)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $spin = json_decode($request->getContent(), true);
            $spin = trim($spin);

            $ret = array(
                'all' => [],
                'arrays' => []
            );

            if (!empty($spin)) {
                $spin_regexp = '/(\$\[|\|)([\t\n ]*' . preg_quote($spin) . '[\t\n ]*)(\]|\|)/iu';
                $cb = $this->get('couchbase.connector');
                $model = new TemplateModel($cb);
                $model->warmup();
                /* @var $objects CbTemplate[] */
                $objects = $model->getAllObjects();

                $all_array = $ret['all'];
                $arrays = $ret['arrays'];

                foreach ($objects as $object) {
                    $ret_arrays = self::findAllSpinInTempate($object->getTemplate(), $spin_regexp);

                    foreach ($ret_arrays as $val) {
                        usort($val, function ($item1, $item2) {
                            return strnatcasecmp($item1, $item2);
                        });
                        $all_array = array_merge($all_array, $val);
                        array_push($arrays, $val);
                    }
                }

                $all_array = array_unique($all_array);
                usort($all_array, function ($item1, $item2) {
                    return strnatcasecmp($item1, $item2);
                });
                $ret['all'] = $all_array;

                $arrays = array_unique($arrays, SORT_REGULAR);
                $ret['arrays'] = $arrays;
            }

            return ApiResponse::resultValue($ret);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
