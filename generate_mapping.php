<?php
if ( ! function_exists('glob_recursive'))
{
    // Does not support flag GLOB_BRACE
    
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
}

if (!is_dir('./zend-framework/') && !is_dir('./Zend/')) {
    die("You should run `git submodule init` and then `git submodule update` in order to download Zend framework first\n");
} else if (!is_dir('./Zend/')) {
    echo "Moving stuff where they should be\n";
    exec("mv ./zend-framework/library/Zend/ ./Zend");
    echo "Removing stuff you'll never use \n";
    exec("rm -rf ./zend-framework");
    echo "Done!\n";
}

echo "Generating bootstrap file\n";

$mapping = '<?php'."\n/* Auto generated */\nreturn array(\n";
$files = glob_recursive('Zend/*.php');

if (!empty($files)) {
    foreach($files as $file) {
        $file = preg_replace('#zend\-framework\/library\/#i','', $file);
	$class = preg_replace('#^(.*)\.php$#i', '$1', $file);
	$namespaces = explode('/', $class);
	$class = implode('\\\\', $namespaces);
	 $mapping .= "\t'{$class}'  =>  __DIR__.'/{$file}',\n";
    }
}
$mapping .= ");\n".'?>'."\n";
$done = file_put_contents('bootstrap.php', $mapping);
echo $done ? "Bootstrap file generated successfully!\n" : "Something went wrong. Bootstrap file not created\n";
?>
