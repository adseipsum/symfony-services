<?php
namespace AppBundle\Extension;

class EditorExtension
{
    const BLOCK_DATABASE = 'database';
    const BLOCK_NEWBLOCK = 'new';
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
}