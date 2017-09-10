<?php

namespace AppBundle\Controller\Api;

use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\EditorExtension;
use AppBundle\Repository\TemplateModel;
use AppBundle\Utils;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TemplateGeneratorController extends Controller
{

    /**
     * @Route("/template/generate/{templateId}", name="api_editor_generate_template", requirements={"template": "[a-zA-Z0-9\-\-]+"})
     *
     * @method ("POST")
     */
    public function generateTemplate(Request $request, $templateId)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new TemplateModel($cb);
            $object = $model->get($templateId);

            if ($object != null) {
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

                $extEditor = new EditorExtension($this->getParameter('generator_user_dir'), $username, 'globalTemplate');
                $result = self::_generateForTemplate($extEditor, $templateId . '.tpl', $object->getTemplate(), $drugName);

                return ApiResponse::resultValue($result);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    function _generateForTemplate($ext, $templateFile, $content, $drugName)
    {
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

        if (file_exists($templateFilePath) == false || sha1($newContent) != sha1($oldContent)) {
            Utils::forceFilePutContents($templateFilePath, $newContent);
        }

        $command_validate = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -v -t $templateName -f $templateFile";

        exec($command_validate, $output_validate);

        $out_validate_text = '';
        $validate_ok = true;

        $template_text_lines = preg_split("/\\n/", $content);
        $template_lines = [];
        $first_line = $ext->getLineCount($base_template_content);
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

        $generated = '';

        if ($validate_ok == true) {
            $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -t $templateName -f $templateFile -op \"(( \" -os \" ))\"";

            if ($drugName != null) {
                $drugName = strtolower($drugName);
                $command .= " -dn $drugName";
            }

            exec($command, $output);

            $brCount = 0;
            foreach ($output as $line) {
                $generated .= $line . "\n";
                $brCount++;
            }
        } else {
            $generated = 'ERROR';
        }

        $params = [];
        $params['generated'] = $generated;
        $params['content'] = $content;
        $params['start_line'] = $first_line;
        $params['validation_lines'] = $template_lines;
        $params['validation_text'] = $out_validate_text;
        $params['validation_status'] = $validate_ok;
        $params['command_validate'] = $command_validate;

        return $params;
    }
}
