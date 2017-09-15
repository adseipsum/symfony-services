<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\CbTemplate;
use AppBundle\Extension\StringDistanceExtension;
use AppBundle\Entity\CbGeneratedText;
use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\EditorExtension;
use AppBundle\Repository\TemplateModel;
use AppBundle\Repository\GeneratedTextModel;
use AppBundle\Extension\UtilsExtension;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TemplateGeneratorController extends Controller
{

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const GENERATE_TEXT_COUNT = 50;
    const GENERATE_TEXT_DEVIATION = 100;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/template/generate/{templateId}", name="api_editor_generate_template",
     *     requirements={"template": "[a-zA-Z0-9\-\-]+"}
     * )
     *
     * @method ("POST")
     */
    public function generateTemplate(Request $request, string $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $templateModel = new TemplateModel($cb);
            /* @var $cbTemplate CbTemplate */
            $cbTemplate = $templateModel->get($templateId);

            if ($cbTemplate != null) {
                $data = json_decode($request->getContent(), true);
                /*
                 * {
                 * "id": "id"
                 * "name" "name"
                 * "template" "template"
                 * }
                 */

                $drugName = null;

                if (isset($data['drugName'])) {
                    $drugName = $data['drugName'];
                }

                $removeStopwords = true;
                if (isset($data['removeStopwords'])) {
                    $removeStopwords = $data['removeStopwords'];
                }

                $useStemmer = true;
                if (isset($data['useStemmer'])) {
                    $useStemmer = $data['useStemmer'];
                }

                $generateLoop = TemplateGeneratorController::GENERATE_TEXT_COUNT;
                if (isset($data['generateLoop'])) {
                    $generateLoop = $data['generateLoop'];
                }

                $deviation = TemplateGeneratorController::GENERATE_TEXT_DEVIATION;
                if (isset($data['deviation'])) {
                    $deviation = $data['deviation'];
                }

                $extEditor = new EditorExtension(
                    $this->getParameter('generator_user_dir'),
                    $username,
                    'globalTemplate'
                );

                $result = self::generateForTemplate(
                    $extEditor,
                    $templateId . '.tpl',
                    $cbTemplate->getTemplate(),
                    $cbTemplate->isValidate(),
                    $cb,
                    $templateId,
                    $drugName,
                    $removeStopwords,
                    $useStemmer,
                    $generateLoop,
                    $deviation
                );

                if (!$cbTemplate->isValidate() and $result['validation_status']) {
                    $cbTemplate->setValidate(true);
                    $templateModel->upsert($cbTemplate);
                }

                return ApiResponse::resultValue($result);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function generateForTemplate(
        EditorExtension $ext,
        $templateFile,
        $content,
        bool $validate_ok,
        $cb,
        string $templateId,
        $drugName,
        bool $removeStopwords,
        bool $useStemmer,
        int $generateLoop,
        int $deviation
    ) {
        $templateName = $ext->getTemplateName();

        $pPython = $this->getParameter('python_bin');
        $pScript = $this->getParameter('generator_home');

        $userDir = $this->getParameter('generator_user_dir');
        $baseTemplate = $this->getParameter('generator_quickcheck_base');

        $username = $this->getUser()->getUsernameCanonical();

        $tmpDir = "$userDir/$username/tmp";
        $templateDir = "$userDir/$username/template";

        $templateBaseFilePath = $baseTemplate;
        $templateFilePath = "$userDir/$username/template/globalTemplate/" . $templateFile;

        $base_template_content = file_get_contents($templateBaseFilePath);

        $newContent = $base_template_content . PHP_EOL . $content;
        $oldContent = '';

        $template_lines = [];
        $first_line = $ext->getLineCount($base_template_content);
        $out_validate_text = '';

        if (!file_exists($templateFilePath) or sha1($newContent) != sha1($oldContent)) {
            UtilsExtension::forceFilePutContents($templateFilePath, $newContent);
        }

        if (!$validate_ok) {
            $command_validate = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir " .
                "-v -t $templateName -f $templateFile";

            exec($command_validate, $output_validate);

            $validate_ok = true;

            $template_text_lines = preg_split("/\\n/", $content);
            $count = 1;

            foreach ($template_text_lines as $line) {
                $elem['linenum'] = $first_line + $count;
                $elem['text'] = $line;
                $elem['is_valid'] = true;
                $template_lines[] = $elem;
                $count++;
            }

            foreach ($output_validate as $line) {
                if (strpos($line, 'TemplateRenderException:') === false) {
                    // do nothing, wierd logic when match at 0 position != false is true, but === false is false
                } else {
                    $validate_ok = false;
                    preg_match_all('/\(([0-9\:\~\?]+?)\)/', $line, $errors);

                    foreach ($errors as $error) {
                        $pos = preg_split("/\:/", $error[0]);
                        $linenum = intval(str_replace('(', '', $pos[0]));

                        $linenum--;

                        foreach ($template_lines as &$tline) {
                            if ($tline['linenum'] == $linenum) {
                                $tline['is_valid'] = false;
                            }
                        }
                    }
                    $out_validate_text = $line . "\n";
                }
            }
        }

        $params = [
            'generated' => '',
            'generate_info' => [],
            'content' => $content,
            'start_line' => $first_line,
            'validation_lines' => $template_lines,
            'validation_text' => $out_validate_text,
            'validation_status' => $validate_ok
        ];

        if ($validate_ok) {
            $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -t $templateName " .
                "-f $templateFile -op \"(( \" -os \" ))\"";

            if ($drugName != null) {
                $drugName = strtolower($drugName);
                $command .= " -dn $drugName";
            }

            $generated = TemplateGeneratorController::generateText(
                $command,
                TemplateGeneratorController::getOldGeneratedTexts($cb, $templateId, $removeStopwords, $useStemmer),
                $removeStopwords,
                $useStemmer,
                $generateLoop,
                $deviation
            );

            $params['generated'] = $generated['text'];
            $params['generate_info'] = $generated['generate_info'];
        }

        return $params;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function getOldGeneratedTexts(
        $cb,
        string $templateId,
        bool $removeStopwords,
        bool $useStemmer
    ) : array {
        $generatedTextModel = new GeneratedTextModel($cb);
        /* @var $cbGeneratedTexts CbGeneratedText[] */
        $cbGeneratedTexts = $generatedTextModel->listObjectsByTemplateId($templateId);

        $ret = [];
        if (isset($cbGeneratedTexts)) {
            $preparedTextParamsHashNew = UtilsExtension::md5Multiple(
                StringDistanceExtension::prepareTextForDistanceCalcVersion(),
                $removeStopwords,
                $useStemmer
            );

            foreach ($cbGeneratedTexts as $cbGeneratedText) {
                $preparedTextParamsHashOld = $cbGeneratedText->getPreparedTextParamsHash();

                if ($preparedTextParamsHashOld === $preparedTextParamsHashNew) {
                    $preparedText = $cbGeneratedText->getPreparedText();
                } else {
                    $preparedText = StringDistanceExtension::prepareTextForDistanceCalc(
                        $cbGeneratedText->getText(),
                        $removeStopwords,
                        $useStemmer
                    );
                    $cbGeneratedText->setPreparedText($preparedText);
                    $cbGeneratedText->setPreparedTextParamsHash($preparedTextParamsHashNew);
                    $generatedTextModel->upsert($cbGeneratedText);
                }

                if (isset($preparedText)) {
                    $ret[$cbGeneratedText->getObjectId()] = $preparedText;
                }
            }
        }

        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function generateTextFromCommand(string $command) : string
    {
        $generated = '';

        $output = [];
        exec($command, $output);

        foreach ($output as $line) {
            $generated .= $line . "\n";
        }

        return $generated;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function generateText(
        string $command,
        array $oldGeneratedTexts,
        bool $removeStopwords,
        bool $useStemmer,
        int $generateLoop,
        int $deviation
    ) {
        $ret = [
            'text' => '',
            'generate_info' => [],
        ];

        if (empty($oldGeneratedTexts)) {
            $ret['text'] = TemplateGeneratorController::generateTextFromCommand($command);
        } else {
            $current_distance = INF;

            for ($i = 0; $i < $generateLoop; $i++) {
                $generatedText = TemplateGeneratorController::generateTextFromCommand($command);

                $preparedText = StringDistanceExtension::prepareTextForDistanceCalc(
                    $generatedText,
                    $removeStopwords,
                    $useStemmer
                );

                $generate_info = StringDistanceExtension::calcDistanceMetricForTexts(
                    $preparedText,
                    $oldGeneratedTexts,
                    $deviation,
                    $current_distance
                );

                if ($generate_info['isSkip']) {
                    continue;
                }

                $current_distance = $generate_info['max'];
                $ret['text'] = $generatedText;
                $ret['generate_info'] = $generate_info['distances'];
            }
        }
        
        usort($ret['generate_info'], function ($item1, $item2) {
            return -1 * strnatcasecmp($item1['id'], $item2['id']);
        });

        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route(
     *     "/template/similarity/{templateId}",
     *     name="api_editor_recalc_similarity",
     *     requirements={"template": "[a-zA-Z0-9\-\-]+"}
     * )
     *
     * @method ("POST")
     */
    public function recalcSimilarity(Request $request, string $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $templateModel = new TemplateModel($cb);
            /* @var $cbTemplate CbTemplate */
            $cbTemplate = $templateModel->get($templateId);

            if ($cbTemplate != null) {
                $data = json_decode($request->getContent(), true);

                $text = '';
                if (isset($data['text'])) {
                    $text = $data['text'];
                }

                $removeStopwords = true;
                if (isset($data['removeStopwords'])) {
                    $removeStopwords = $data['removeStopwords'];
                }

                $useStemmer = true;
                if (isset($data['useStemmer'])) {
                    $useStemmer = $data['useStemmer'];
                }

                $deviation = TemplateGeneratorController::GENERATE_TEXT_DEVIATION;
                if (isset($data['deviation'])) {
                    $deviation = $data['deviation'];
                }

                $oldGeneratedTexts = TemplateGeneratorController::getOldGeneratedTexts(
                    $cb,
                    $templateId,
                    $removeStopwords,
                    $useStemmer
                );

                $result = [
                    'generate_info' => []
                ];

                if (!empty($oldGeneratedTexts)) {
                    $preparedText = StringDistanceExtension::prepareTextForDistanceCalc(
                        $text,
                        $removeStopwords,
                        $useStemmer
                    );

                    $generate_info = StringDistanceExtension::calcDistanceMetricForTexts(
                        $preparedText,
                        $oldGeneratedTexts,
                        $deviation
                    );

                    $generate_info = $generate_info['distances'];
                    
                    usort($generate_info, function ($item1, $item2) {
                        return -1 * strnatcasecmp($item1['id'], $item2['id']);
                    });

                    $result['generate_info'] = $generate_info;
                }

                return ApiResponse::resultValue($result);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
