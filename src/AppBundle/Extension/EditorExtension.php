<?php

namespace AppBundle\Extension;

use AppBundle\UtilsExtension;

class EditorExtension
{

    const BLOCK_DATABASE = 'database';

    const BLOCK_NEWBLOCK = 'newblock';

    const BLOCK_STATICTEXT = 'static';

    const BLOCK_SPINSENTENCES = 'spinsentence';

    const TEMPLATE_HEADER_1 = '<%namespace name="ctx" module="robobloglab.template.extension" import="*"/>';

    const TEMPLATE_HEADER_2 = '<%namespace name="spin" module="robobloglab.template.spinner" import="*"/>';

    const TEMPLATE_INCLUDE_DB = '<%include file="database.tpl"/>';

    const TEMPLATE_INCLUDE_DICTIONARY = '<%include file="globaldict.tpl"/>';

    var $username;

    var $templatename;

    var $path;

    var $container;

    function __construct($userDir, $username, $templatename)
    {
        $this->username = $username;
        $this->templatename = $templatename;
        $this->path = "$userDir/$username/template/$templatename/";
    }

    public function getTemplateName()
    {
        return $this->templatename;
    }

    // Dictionary
    function getGlobalDictonary()
    {
        $filename = $this->path . 'globaldict.json';
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename));
        } else {
            return [];
        }
    }

    function setGlobalDictonary($value)
    {
        $filename = $this->path . 'globaldict.json';
        UtilsExtension::forceFilePutContents($filename, json_encode($value));
        $this->_genDictonaryTemplate($value);
    }

    function isDictonaryExists()
    {
        $filename = $this->path . 'globaldict.tpl';
        return file_exists($filename);
    }

    function _genDictonaryTemplate($dict)
    {
        $filename = $this->path . 'globaldict.tpl';
        $content = '';

        $content .= self::TEMPLATE_HEADER_1 . PHP_EOL;
        $content .= self::TEMPLATE_HEADER_2 . PHP_EOL;

        foreach ($dict as $key => $value) {
            $content .= $key . '{' . PHP_EOL;
            $words = explode("\n", $value);

            foreach ($words as $word) {
                $rword = preg_replace('/\s\s+/', ' ', $word);
                $content .= $rword . PHP_EOL;
            }

            $content .= '}' . PHP_EOL;
        }
        UtilsExtension::forceFilePutContents($filename, $content);
    }

    // Rawtext
    function getRawtext()
    {
        $filename = $this->path . 'rawtext.txt';
        if (file_exists($filename)) {
            return file_get_contents($filename);
        } else {
            return '';
        }
    }

    function setRawtext($value)
    {
        $filename = $this->path . 'rawtext.txt';
        UtilsExtension::forceFilePutContents($filename, $value);
    }

    // Spinblock
    function getSpinblockData()
    {
        $filename = $this->path . 'spinblock.json';
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename));
        } else {
            $blocks = [];

            $blocks[] = self::_genBlockDefinition('Database block', self::BLOCK_DATABASE, 0, true, true);
            return $blocks;
        }
    }

    function setSpinblockData($value)
    {
        $filename = $this->path . 'spinblock.json';
        UtilsExtension::forceFilePutContents($filename, json_encode($value));
    }

    function _genBlockDefinition($name, $type, $index, $fixed = false, $readonly = false)
    {
        $ret = [];
        $ret['name'] = $name;
        $ret['type'] = $type;
        $ret['readonly'] = $readonly;
        $ret['index'] = $index;
        $ret['fixed'] = $fixed;
        return $ret;
    }

    function genTemplateFileStub($filename)
    {
        $filename = $this->path . $filename;
        $content = '';
        $content .= self::TEMPLATE_HEADER_1 . PHP_EOL;
        $content .= self::TEMPLATE_HEADER_2 . PHP_EOL;
        $content .= self::TEMPLATE_INCLUDE_DB . PHP_EOL;

        if (self::getGlobalDictonary()) {
            $content .= self::TEMPLATE_INCLUDE_DICTIONARY . PHP_EOL;
        }

        UtilsExtension::forceFilePutContents($filename, $content);
    }

    function genTemplateForBlock($value)
    {
        $content = '';

        if ($value['type'] == self::BLOCK_STATICTEXT) {
            $lines = explode("\n", $value['data']);

            foreach ($lines as $line) {
                $rword = preg_replace('/\s\s+/', ' ', $line);
                $content .= $rword . PHP_EOL;
            }
        } else if ($value['type'] == self::BLOCK_SPINSENTENCES) {
            $lines = $value['data'];
            $content .= '{{' . PHP_EOL;
            foreach ($lines as $line) {
                $rword = preg_replace('/\s\s+/', ' ', $line);
                $content .= $rword . PHP_EOL;
            }
            $content .= '}}' . PHP_EOL;
        }
        return $content;
    }

    // Validation parser
    function getLineNumber($line)
    {
        $bpos = strpos($line, '(') + 1;
        $epos = strpos($line, ':', $bpos);

        $sub = substr($line, $bpos, $epos - $bpos);

        return intval($sub);
    }

    function getStripedError($line)
    {
        $bpos = 0;
        $epos = strpos($line, ' in file ', $bpos);

        $sub = substr($line, $bpos, $epos - $bpos);

        return $sub;
    }

    function getLine($text, $linenumber)
    {
        $lines = preg_split("/\\n/", $text);
        return $lines[$linenumber - 1];
    }

    function getLineCount($text)
    {
        $lines = preg_split("/\\n/", $text);
        return count($lines);
    }
}
