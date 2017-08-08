<?php
namespace AppBundle\Extension;

class EditorExtension
{
    const BLOCK_DATABASE = 'database';
    const BLOCK_NEWBLOCK = 'newblock';
    const BLOCK_STATICTEXT = 'static';
    const BLOCK_SPINSENTENCES = 'spinsentence';

    var $username;
    var $templatename;
    var $path;
    var $container;


    function __construct($userDir, $username, $templatename)
    {
        $this->username = $username;
        $this->templatename = $username;
        $this->path = "$userDir/$username/template/$templatename/";
    }

    // Dictionary

    function getGlobalDictonary()
    {
        $filename = $this->path.'globaldict.json';
        if(file_exists($filename))
        {
            return json_decode(file_get_contents($filename));
        }
        else {
            return [];
        }
    }

    function setGlobalDictonary($value)
    {
        $filename = $this->path.'globaldict.json';
        file_put_contents($filename, json_encode($value));
    }

    // Rawtext
    function getRawtext()
    {
        $filename = $this->path.'rawtext.txt';
        if(file_exists($filename))
        {
            return file_get_contents($filename);
        }
        else {
            return '';
        }
    }

    function setRawtext($value)
    {
        $filename = $this->path.'rawtext.txt';
        file_put_contents($filename, $value);
    }

    // Spinblock

    function getSpinblockData()
    {
        $filename = $this->path.'spinblock.json';
        if(file_exists($filename))
        {
            return json_decode(file_get_contents($filename));
        }
        else {
            $blocks=[];

            $blocks[] = self::_genBlockDefinition('Database block', self::BLOCK_DATABASE,0, true, true);
            return $blocks;
        }
    }

    function setSpinblockData($value)
    {
        $filename = $this->path.'spinblock.json';
        file_put_contents($filename, json_encode($value));
    }

    function _genBlockDefinition($name, $type, $index, $fixed=false, $readonly=false)
    {
        $ret = [];
        $ret['name'] = $name;
        $ret['type'] = $type;
        $ret['readonly'] = $readonly;
        $ret['index'] = $index;
        $ret['fixed'] = $fixed;
        return $ret;
    }



}