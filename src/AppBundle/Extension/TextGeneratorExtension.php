<?php

namespace AppBundle\Extension;


use AppBundle\Repository\GeneratedTextModel;
use Symfony\Component\DependencyInjection\Container;

class TextGeneratorExtension {

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function generateForTemplate(
        Container $container,
        $username,
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
        int $deviation,
        bool $useBracket
    ) {
        $templateName = $ext->getTemplateName();

        $pPython = $container->getParameter('python_bin');
        $pScript = $container->getParameter('generator_home');

        $userDir = $container->getParameter('generator_user_dir');
        $baseTemplate = $container->getParameter('generator_quickcheck_base');

        #$username = $this->getUser()->getUsernameCanonical();

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
            if($useBracket)
            {
                $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -t $templateName " .
                    "-f $templateFile -op \"(( \" -os \" ))\"";
            }
            else {
                $command = "cd $pScript && $pPython $pScript/render.py -DW $tmpDir -DT $templateDir -t $templateName " .
                    "-f $templateFile ";
            }



            if ($drugName != null) {
                $drugName = strtolower($drugName);
                $command .= " -dn $drugName";
            }

            $generated = self::generateText(
                $command,
                self::getOldGeneratedTexts($cb, $templateId, $removeStopwords, $useStemmer),
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
        $generatedTextModel->warmup();
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
            $ret['text'] = self::generateTextFromCommand($command);
        } else {
            $current_distance = INF;

            for ($i = 0; $i < $generateLoop; $i++) {
                $generatedText = self::generateTextFromCommand($command);

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




}