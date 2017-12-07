<?php
namespace AppBundle\Extension;


use AppBundle\Controller\Api\ApiController;

class PythonToolsExtension
{
    var $python_bin;
    var $ngram_mc_bin;
    var $user_dir;
    var $username;

    function __construct($parent, $username)
    {
        $this->python_bin = $parent->getParameter('python_bin');
        $this->user_dir = $parent->getParameter('generator_user_dir');
        $this->ngram_mc_bin = $parent->getParameter('ngram_mc_bin');
        $this->username = $username;
    }

    function transformTextNGMC($text, $framesize, $prob, $mode, $version='cb', $genbrackets=true)
    {
        $path = $this->user_dir.'/'.$this->username.'/'.'ngmc.tmp';
        file_put_contents($path, $text);

        $pPython = $this->python_bin;
        $pScript = $this->ngram_mc_bin;

        #if($version == 'cb')
        #{
        $pScript = str_replace("spinner.py", "spinnercb.py", $pScript);
        #}

        $command = null;
        if($genbrackets)
        {
            $command = "$pPython $pScript -f $path -FR $framesize -FP $prob -m $mode";
        }
        else {
            $command = "$pPython $pScript -f $path -FR $framesize -FP $prob -m $mode -s False";
        }
        exec($command, $output);
        $generated = '';
        foreach ($output as $line) {
            $generated .= $line . "\n";
        }

        return $generated;

    }

}