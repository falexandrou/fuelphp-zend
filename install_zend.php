<?php

if (!function_exists('glob_recursive')) {

    // Does not support flag GLOB_BRACE

    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }

}

// Get the install path
$install_path = "./";
if (is_dir('fuel') && is_dir('fuel/packages/')) {
    $install_path .= "fuel/packages/";
}

echo "Zend install path: $install_path";

$handle = fopen ("php://stdin","r");
$line = trim(fgets($handle));

$install_path .= $line;

if (!is_dir($install_path)){
    echo "Path doesn't exist! \nNow exiting\n";
    exit;
}

$install_path = preg_replace('#^(.*)\/$#i', '$1', $install_path);
$install_path .= '/zend';

if (!is_dir($install_path.'/.zend')) {
    if (!is_dir('./.git')){
        echo "This should be run inside a git repository \n";
        exit;
    }
    echo "Installing Zend framework under path '".realpath(str_replace('/zend', '', $install_path))."/.zend'\n";
    exec('git submodule add https://github.com/zendframework/zf2.git '.$install_path.'/.zend');
}else{
    echo "Zend framework already found under {$install_path}/.zend\n";
    echo "Updating Zend framework\n";
    exec('git submodule update --init --recursive');
}

if (!is_dir($install_path.'/.zend/')) {
    die("Something went wrong. Are you actually privileged to do this?\n");
} else if (!is_link($install_path.'./Zend')) {
    echo "Creating symbolic link to library\n";
    exec("ln -s {$install_path}/.zend/library/Zend/ {$install_path}/Zend");
    echo "Done!\n";
}

echo "Generating bootstrap file\n";

$files = glob_recursive($install_path.'/.zend/library/Zend/*.php');
if (!empty($files)) {
    
    $replace_path = $install_path .'/.zend/library/';
    
    $mapping = '<?php' . "\n/* Auto generated */\nreturn array(\n";
    
    foreach ($files as $file) {
        $file = str_replace($replace_path, '', $file);
        $class = preg_replace('#^(.*)\.php$#i', '$1', $file);
        $namespaces = explode('/', $class);
        $class = implode('\\\\', $namespaces);
        $mapping .= "\t'{$class}'  =>  __DIR__.'/{$file}',\n";
    }
    
    $mapping .= ");\n" . '?>' . "\n";
    $done = file_put_contents($install_path.'/bootstrap.php', $mapping);
    echo $done ? "Bootstrap file generated successfully!\n" : "Something went wrong. Bootstrap file not created\n";
    exit;
}

echo "Something went wrong\n";

?>
