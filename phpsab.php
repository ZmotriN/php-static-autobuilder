<?php

/**
* ██████╗ ██╗  ██╗██████╗ ███████╗ █████╗ ██████╗ 
* ██╔══██╗██║  ██║██╔══██╗██╔════╝██╔══██╗██╔══██╗
* ██████╔╝███████║██████╔╝███████╗███████║██████╔╝
* ██╔═══╝ ██╔══██║██╔═══╝ ╚════██║██╔══██║██╔══██╗
* ██║     ██║  ██║██║     ███████║██║  ██║██████╔╝
* ╚═╝     ╚═╝  ╚═╝╚═╝     ╚══════╝╚═╝  ╚═╝╚═════╝ 
*/


// TODO: constants SCRIPTS, SDK
// TODO: separate update command


const VERSION = "0.1.1";
const TMP = DIR.'tmp\\';
const LOG = DIR.'logs\\';
const BUILD = DIR.'build\\';
const MASTER = DIR.'master\\';
const CONFIG = DIR.'configs\\';
const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const MAX_BUFFER_WIDTH = 100;
const SPEED_DEV = true;


draw_header("PHP Static Autobuilder");


/**
 * Create default configuration files
 */
if(!is_dir(CONFIG) && !@mkdir(CONFIG, 0777, true)) exit_error("Can't create configs folder");
if(!is_file(CONFIG . 'default.ini')) {
    $conf['build']['target'] = 'php-static.exe';
    $conf['build']['bootstrap'] = 'default';
    $conf['build']['clean'] = false;
    $conf['extensions']['win32std'] = true;
    $conf['extensions']['winbinder'] = true;
    $conf['extensions']['wcli'] = true;
    $conf['extensions']['phar'] = true;
    file_put_contents(CONFIG . 'default.ini', generate_ini($conf));
}
if(!is_file(CONFIG . 'phpsab.ini')) {
    $conf['build']['target'] = 'phpsab.exe';
    $conf['build']['bootstrap'] = 'phpsab';
    $conf['build']['clean'] = false;
    $conf['extensions']['win32std'] = true;
    $conf['extensions']['winbinder'] = true;
    $conf['extensions']['wcli'] = true;
    $conf['extensions']['curl'] = true;
    $conf['extensions']['phar'] = true;
    $conf['extensions']['zlib'] = true;
    $conf['extensions']['zip'] = true;
    file_put_contents(CONFIG . 'phpsab.ini', generate_ini($conf));
}


/**
 * Load configuration file
 */
if($argc > 1) $configname = $argv[1];
else $configname = 'default';
if(!is_file(CONFIG . $configname . '.ini')) exit_error("Can't load \"".$configname.".ini\" config file");
if(!$CONFIG = @parse_ini_file(CONFIG . $configname . '.ini', true, INI_SCANNER_TYPED)) exit_error("Invalid \"".$configname.".ini\" config file");
if(!isset($CONFIG['build']['target'])) $CONFIG['build']['target'] = 'php-static.exe';
if(!isset($CONFIG['build']['bootstrap'])) $CONFIG['build']['bootstrap'] = 'default';
if(!isset($CONFIG['build']['clean'])) $CONFIG['build']['clean'] = false;
if(!isset($CONFIG['build']['test'])) $CONFIG['build']['test'] = false;
if(!isset($CONFIG['extensions'])) $CONFIG['extensions'] = [];
if(strtolower(pathinfo($CONFIG['build']['target'], PATHINFO_EXTENSION)) != 'exe') exit_error("Invalid target extension");
if(!empty($argv[2]) && $argv[2] == 'clean') $CONFIG['build']['clean'] = true;
if(!empty($argv[2]) && $argv[2] == 'test') $CONFIG['build']['test'] = true;


/**
 * Loading Information Matrix
 */
draw_line("Load matrix", 'running', Yellow);
if(is_file(DIR.'matrix.json')) $contents = @file_get_contents(DIR.'matrix.json');
else $contents = curl_get_contents('https://raw.githubusercontent.com/ZmotriN/php-static-autobuilder/main/matrix.json');
if(!$contents) draw_status("Load matrix", 'failed', Red, true);
if(!$MATRIX = @json_decode($contents)) draw_status("Load matrix", 'failed', Red, true);
draw_status("Load matrix", 'loaded', Green);


/**
 * Verify version availability
 */
if($MATRIX->version != VERSION) {
    wcli_echo(RN."A new version is available.".RN."Press any key to visite release site.", Green|Bright);
    wcli_flash();
    wcli_get_key();
    wb_exec('https://github.com/ZmotriN/php-static-autobuilder/releases');
    exit(0);
}


/**
 * Verify Visual Studio 2019
 */
if($vspath = verify_deps_vs16()) {
    define('VS_PATH', $vspath);
    draw_status("Visual Studio 2019", "found", Green);
} else {
    draw_status("Visual Studio 2019", "missing", Red);
    exit_error();
}


/**
 * Verify Strawberry Perl
 */
if($perlpath = verify_deps_strawberry_perl()) {
    define('PERL_PATH', $perlpath);
    draw_status("Strawberry Perl", "found", Green);
} else {
    draw_status("Strawberry Perl", "missing", Red);
    exit_error();
}


/**
 * Verify Git
 */
if($gitpath = verify_deps_git()) {
    define('GIT_PATH', $gitpath);
    draw_status("Git", "found", Green);
} else {
    draw_status("Git", "missing", Red);
    exit_error();
}


/**
 * Verify NASM
 */
if(wcli_where('nasm')) draw_status("NASM", "found", Green);
else draw_status("NASM", "missing", Red, true);


/**
 * Verify RM
 */
if(wcli_where('rm')) draw_status("RM", "found", Green);
else draw_status("RM", "missing", Red, true);


/**
 * Create temporary folder
 */
if(is_dir(TMP)) {
    draw_status("Temporary folder", "found", Green);
} else {
    if(@mkdir(TMP, 0777, true)) {
        draw_status("Temporary folder", "created", Green);
    } else {
        draw_status("Temporary folder", "failed", Red);
        exit_error();
    }
}


/**
 * Create logs folder
 */
if(is_dir(LOG)) {
    draw_status("Logs folder", "found", Green);
} else {
    if(@mkdir(LOG, 0777, true)) {
        draw_status("Logs folder", "created", Green);
    } else {
        draw_status("Logs folder", "failed", Red);
        exit_error();
    }
}


/**
 * Create build folder
 */
if(is_dir(BUILD)) {
    draw_status("Build folder", "found", Green);
} else {
    if(@mkdir(BUILD, 0777, true)) {
        draw_status("Build folder", "created", Green);
    } else {
        draw_status("Build folder", "failed", Red);
        exit_error();
    }
}


/**
 * Download and install PHP SDK Binary Tools
 */
define('SDK_PATH', DIR.'sdk\phpsdk-vs16-x64.bat');
if(!is_file(SDK_PATH)) {
    $sdkzip = TMP.'sdk-binary-tools.zip';
    $sdkdir = DIR.'php-sdk-binary-tools-master';
    $sdkdest = DIR.'sdk';
    if(!download_file($MATRIX->sdk->download_url, $sdkzip, "php-sdk-binary-tools")) exit_error();
    if(!unzip($sdkzip, DIR)) exit_error();
    if(!is_dir($sdkdir)) exit_error("Invalid archive");
    if(!rename_wait(realpath($sdkdir), $sdkdest)) exit_error("Can't rename SDK folder");
    if(!is_file(SDK_PATH)) exit_error("Invalid archive");
    @unlink($sdkzip);
    draw_status("SDK Binary Tools", "installed", Green);
} else {
    draw_status("SDK Binary Tools", "found", Green);
}


/**
 * Download and install RCEDIT
 */
define('RCEDIT_PATH', DIR . 'sdk\bin\rcedit.exe');
if(!is_file(RCEDIT_PATH)) {
    if(!download_file($MATRIX->rcedit->download_url, RCEDIT_PATH, pathinfo(RCEDIT_PATH, PATHINFO_BASENAME))) exit_error();
    draw_status("RCEDIT", "installed", Green);
} else {
    draw_status("RCEDIT", "found", Green);
}


/**
 * Create environment scripts folder
 */
define('SCRIPT_PATH', DIR.'scripts\\');
if(!is_dir(SCRIPT_PATH)) {
    if(@mkdir(SCRIPT_PATH, 0777, true)) {
        draw_status("Environment scripts folder", "created", Green);
    } else {
        draw_status("Environment scripts folder", "failed", Red);
        exit_error("Can't create environment scripts folder");
    }
} else {
    draw_status("Environment scripts folder", "found", Green);
}


/**
 * Create VS16 environment script
 */
define('SCRIPT_VS16', SCRIPT_PATH.'env-vs16.bat');
if(!is_file(SCRIPT_VS16)) {
    list($drive) = explode(':', DIR);
    $bat = '@echo off'.RN;
    $bat .= $drive . ':' . RN;
    $bat .= 'cd ' . escapeshellarg(DIR.'sdk') . RN;
    $bat .= 'call ' . escapeshellarg(VS_PATH) . RN;
    $bat .= 'call %*' . RN;
    if(!@file_put_contents(SCRIPT_VS16, $bat)) {
        draw_status("VS16 environment script", "failed", Red);
        exit_error();
    }
    draw_status("VS16 environment script", "created", Green);
} else {
    draw_status("VS16 environment script", "found", Green);
}


/**
 * Create VS16-PHPSDK environment script
 */
define('SCRIPT_VS16_PHPSDK', SCRIPT_PATH.'env-vs16-phpsdk.bat');
if(!is_file(SCRIPT_VS16_PHPSDK)) {
    list($drive) = explode(':', DIR);
    $bat = '@echo off'.RN;
    $bat .= $drive . ':' . RN;
    $bat .= 'cd ' . escapeshellarg(DIR.'sdk') . RN;
    $bat .= 'call ' . escapeshellarg(VS_PATH) . RN;
    $bat .= 'call ' . escapeshellarg(SDK_PATH) . ' -t %*' . RN;
    if(!@file_put_contents(SCRIPT_VS16_PHPSDK, $bat)) {
        draw_status("VS16-PHPSDK environment script", "failed", Red);
        exit_error();
    }
    draw_status("VS16-PHPSDK environment script", "created", Green);
} else {
    draw_status("VS16-PHPSDK environment script", "found", Green);
}


/**
 * Create architecture tree
 */
define('ARCH_PATH', DIR.'sdk\phpdev\vs16\x64\\');
if(!is_dir(ARCH_PATH)) {
    $bat = '@echo off'.RN;
    $bat .= 'phpsdk_buildtree phpdev'.RN;
    file_put_contents(TMP.'buildtree.bat', $bat);
    draw_line('Architecture tree', 'creating', Yellow);
    shell_exec_vs16_phpsdk(TMP.'buildtree.bat');
    if(is_dir(ARCH_PATH)) {
        draw_status('Architecture tree', 'created', Green);
    } else {
        draw_status('Architecture tree', 'failed', Red, true);
    }
} else {
    draw_status('Architecture tree', 'found', Green);
}


/**
 * Install master scripts
 */
if(!is_dir(MASTER)) {
    draw_line("Clone master scripts", 'running', Yellow);
    git_clone($MATRIX->master->repo, MASTER);
    if(!is_dir(MASTER)) draw_status("Cloning master scripts", 'failed', Red, true);
    else draw_status("Cloning master scripts", 'up-to-date', Green);
} elseif(is_dir(MASTER.'.git')) {
    draw_line("Update master scripts", 'running', Yellow);
    git_update(MASTER);
    draw_status("Update master scripts", 'up-to-date', Green);
}


/**
 * Library dependancies
 */
echo RN.RN;
draw_header("Library dependancies");
define('DEPS_PATH', ARCH_PATH.'deps\\');


/**
 * Load library tree
 */
$libraries = [];
foreach($MATRIX->extensions as $ext)
    if($ext->mandatory)
        $CONFIG['extensions'][$ext->name] = true;
foreach($MATRIX->extensions as $ext)
    if(isset($CONFIG['extensions'][$ext->name]))
        if($CONFIG['extensions'][$ext->name])
            foreach($ext->dependancies as $dep)
                $CONFIG['extensions'][$dep] = true;
foreach($MATRIX->extensions as $ext)
    if(isset($CONFIG['extensions'][$ext->name]))
        if($CONFIG['extensions'][$ext->name])
            foreach($ext->libraries as $lib)
                $libraries[] = $lib;
foreach($MATRIX->libraries as $lib)
    if(in_array($lib->name, $libraries))
        foreach($lib->dependancies as $dep)
            $libraries[] = $dep;
foreach($MATRIX->libraries as $lib)
    if($lib->mandatory)
        $libraries[] = $lib->name;


/**
 * Install libraries
 */
foreach($MATRIX->libraries as $lib) {
    if(in_array($lib->name, $libraries)){
        include(MASTER.'libraries\\' . $lib->install_script);
        unset($files);
    }
}
        


/**
 * Install PHP
 */
echo RN.RN;
draw_header("PHP " . $MATRIX->php->version);
include(MASTER . 'php\install_php.php');
define('PHP_PATH', $path);


/**
 * Install Extensions
 */
define('EXT_PATH', PHP_PATH . 'ext\\');
foreach($MATRIX->extensions as $ext)
    if(!$ext->builtin)
        if(isset($CONFIG['extensions'][$ext->name]) && $CONFIG['extensions'][$ext->name])
            include(MASTER . 'php\install_extension.php');


/**
 * Install Embeder
 */
define('EMBEDER_PATH', PHP_PATH . 'embeder\\');
include(MASTER.'php\install_embeder.php');


/**
 * Build PHP
 */
include(MASTER.'php\build_php.php');


/**
 * Jobs done!
 */
wcli_echo("Press any key to quit.", Yellow);
wcli_flash();
wcli_get_key();
exit(0);
// END









/*****************************************************************************************
 *****************************************************************************************
 *********************************** HELPER FUNCTIONS ************************************
 *****************************************************************************************
 *****************************************************************************************/


function shell_exec_vs16($taskfile, $verbose = false)
{
    if($verbose) {
        passthru(escapeshellarg(SCRIPT_VS16) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
        return true;
    } else {
        return shell_exec(escapeshellarg(SCRIPT_VS16) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
    }
}


function shell_exec_vs16_phpsdk($taskfile, $verbose = false)
{
    if($verbose) {
        passthru(escapeshellarg(SCRIPT_VS16_PHPSDK) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
        return true;
    } else {
        return shell_exec(escapeshellarg(SCRIPT_VS16_PHPSDK) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
    }
}


function git_clone($repo, $dir)
{
    $ret = shell_exec('git clone  --recurse-submodules ' . escapeshellarg($repo) . ' '.escapeshellarg(rtrim($dir, '\\')) . ' 2>&1');
    file_put_contents(LOG.'git.log', $ret.RN, FILE_APPEND);
    return is_dir($dir);
}


function git_update($dir)
{
    $lastdir = getcwd();
    chdir($dir);
    $ret = shell_exec('git pull --recurse-submodules 2>&1');
    file_put_contents(LOG.'git.log', $ret.RN, FILE_APPEND);
    chdir($lastdir);
    return true;
}


function draw_status(string $label, string $status, int $color, bool $throw = false, $msg = "An error occured")
{
    draw_line($label, $status, $color, MAX_BUFFER_WIDTH);
    echo RN;
    if($throw) exit_error($msg);
}


function draw_line(string $label, string $status, int $color, int $max = MAX_BUFFER_WIDTH)
{
    static $bw = null;
    if(is_null($bw)) {
        list($bw, $bh) = wcli_get_buffer_size();
        if($bw > $max) $bw = $max;
    }
    $dotsnb = $bw - strlen(trim($label)) - strlen(trim($status)) - 2;
    wcli_echo(R.$label, White);
    wcli_echo(str_repeat('.', $dotsnb), Grey);
    wcli_echo('[', White|Bright);
    wcli_echo($status, $color);
    wcli_echo(']', White|Bright);
}


function draw_header(string $name, int $max = MAX_BUFFER_WIDTH)
{
    static $bw = null;
    if(is_null($bw)) {
        list($bw, $bh) = wcli_get_buffer_size();
        if($bw > $max) $bw = $max;
    }
    wcli_echo(str_repeat('*', $bw).RN, White|Bright);
    wcli_echo('*'.str_repeat(' ', ($bw - 2)).'*'.RN, White|Bright);
    $left = floor(($bw - 2 - strlen($name)) / 2);
    wcli_echo('*'.str_repeat(' ', $left));
    wcli_echo(strtoupper($name), Aqua);
    $right = $bw - 2 - ($left + strlen($name));
    wcli_echo(str_repeat(' ', $right));
    wcli_echo('*'.RN, White|Bright);
    wcli_echo('*'.str_repeat(' ', ($bw - 2)).'*'.RN, White|Bright);
    wcli_echo(str_repeat('*', $bw).RN, White|Bright);
}


function exit_error($msg = "An error occured")
{
    $msg .= ".\r\nPress a key to exit.";
    wcli_echo(RN.RN.$msg, Red|Bright);
    wcli_flash();
    wcli_get_key();
    exit(1);
}


function download_file($url, $dest, $label)
{
    $label = 'Download '.$label;
    $lastsize = -1;
    if($result = curl_get_contents($url, $dest, function($prog, $downbytes) use($label, &$lastsize) {
        $size = sizetostr($downbytes);
        if($size != $lastsize) {
            $lastsize = $size;
            draw_line($label, $size, Yellow);
        }
    })) {
        draw_status($label, "complete", Green);
        return $dest;
    } else {
        draw_status($label, "failed", Red);
        return false;
    }
}


function curl_get_file_size($url)
{
    $result = false;
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);

    $data = curl_exec($curl);
    curl_close($curl);

    if ($data) {
        $content_length = "unknown";
        $status = "unknown";
        if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) $status = (int)$matches[1];
        if (preg_match("/Content-Length: (\d+)/", $data, $matches)) $content_length = (int)$matches[1];
        if ($status == 200 || ($status > 300 && $status <= 308)) $result = $content_length;
    }

    return $result;
}


function curl_file_exists(string $url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);

    curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $code == 200;
}


function curl_get_contents($file, $dest = null, $clb = null)
{
	$chnd = curl_init($file);
	curl_setopt_array($chnd,[
		CURLOPT_AUTOREFERER    => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_TIMEOUT        => 3600,
		CURLOPT_CONNECTTIMEOUT => 60,
		CURLOPT_ENCODING       => 'gzip,deflate',
		CURLOPT_NOPROGRESS     => ($clb ? false : true),
		CURLOPT_RETURNTRANSFER => ($dest ? false : true),
		CURLOPT_COOKIEFILE  => '',
        CURLOPT_USERAGENT => USER_AGENT,
	]);
	if($clb){
		$lastprog = -1;
		curl_setopt($chnd, CURLOPT_PROGRESSFUNCTION, function($chnd, $totalbytes, $downbytes, $expupbytes, $upbytes) use(&$lastprog, $clb) {
            if($totalbytes > 0){
				$prog = $downbytes / $totalbytes;
			} else $prog = 0;
			$prog = round($prog, 5);
            if($downbytes != $lastprog) {
				$lastprog = $downbytes;
				call_user_func($clb, $prog, $downbytes);
			}
		});
	}
	if($dest){
        if(!$fhnd = fopen($dest, "wb")) return false;
        curl_setopt($chnd, CURLOPT_WRITEFUNCTION, function($chnd, $data) use(&$fhnd) {
			return fwrite($fhnd, $data);
		});
	}
	$results = curl_exec($chnd);
    $info = curl_getinfo($chnd);

	curl_close($chnd);
	if(!empty($fhnd)) fclose($fhnd);
	if(!in_array($info['http_code'], [200, 201])) $result = null;
	return $dest ? ($results !== false) : $results;
}


function sizetostr($oct, $precision = 1, $space = true)
{
    if (($oct / pow(1024, 4)) >= 1) return number_format($oct / pow(1024, 4), $precision) . ($space ? ' ' : '') . 'TB';
    if (($oct / pow(1024, 3)) >= 1) return number_format($oct / pow(1024, 3), $precision) . ($space ? ' ' : '') . 'GB';
    if (($oct / pow(1024, 2)) >= 1) return number_format($oct / pow(1024, 2), $precision) . ($space ? ' ' : '') . 'MB';
    if (($oct / 1024) >= 1) return number_format($oct / 1024, $precision) . ($space ? ' ' : '') . 'KB';
    return $oct . ($space ? ' ' : '') . 'B';
}


function rename_wait($src, $dst, $sec = 10)
{
    if(is_dir($dst)) rm_dir($dst);
    for($i = 0; $i < $sec; $i++) {
        if(@rename($src, $dst)) {
            $success = true;
            break;
        }
        sleep(1);
    }
    return $success ?? false;
}


function unzip($zipfile, $dest)
{
    $label = "Unzip " . pathinfo($zipfile, PATHINFO_BASENAME);
    draw_line($label, 'running', Yellow);
    $zip = new ZipArchive;
    if ($zip->open($zipfile) === TRUE) {
        $zip->extractTo($dest);
        $zip->close();
        draw_status($label, 'complete', Green);
        return true;
    } else {
        draw_status($label, 'failed', Red);
        return false;
    }
}


function untar($tarfile, $dest)
{

    $label = "Unzip " . pathinfo($tarfile, PATHINFO_BASENAME);
    draw_line($label, 'running', Yellow);

    $tmpfile = preg_replace('#\.gz$#i', '', $tarfile);
    if(is_file($tmpfile)) unlink($tmpfile);
    $pd = new PharData($tarfile);
    if(!@$pd->decompress()) {
        draw_status($label, 'failed', Red);
        return false;
    }

    $phar = new PharData($tmpfile);
    if(!@$phar->extractTo($dest, null, true)) {
        draw_status($label, 'failed', Red);
        return false;
    } else {
        draw_status($label, 'complete', Green);
        return true;
    }
}


function zip_first_dir($zipfile)
{
    $zip = new ZipArchive;
    if (!$zip->open($zipfile) === TRUE) return false;
    if(!$first = $zip->getNameIndex(0)) return false;
    $zip->close();
    return rtrim($first, '/');
}


function create_build($path, $files)
{
    $path = rtrim($path, '\\') . '\\';
    foreach($files as $src => $dst) {
        if(!is_file($src)) return false;
        $dstpath = pathinfo($path . $dst, PATHINFO_DIRNAME);
        if(!is_dir($dstpath) && !@mkdir($dstpath, 0777, true)) return false;
        if(!@copy($src, $path . $dst)) return false;
    }
    return true;
}


function install_deps($path)
{
    foreach(dig($path . '*') as $file) {
        $dest = DEPS_PATH . str_replace($path, '', $file);
        $destdir = pathinfo($dest, PATHINFO_DIRNAME);
        if(!is_dir($destdir) && !@mkdir($destdir, 0777, true)) return false;
        if(!@copy($file, $dest)) return false;
    }
    return true;
}


function dig($path)
{
    $patt = pathinfo($path, PATHINFO_BASENAME);
    $path = pathinfo($path, PATHINFO_DIRNAME);
    if (!$path = realpath($path)) return;
    else $path .= '\\';
    foreach (glob($path . $patt) as $file) {
        if (is_dir($file)) continue;
        else yield $file;
    }
    foreach (glob($path . '*', GLOB_ONLYDIR) as $dir) {
        foreach (call_user_func(__FUNCTION__, $dir . '\\' . $patt) as $file) yield $file;
    }
}


function rm_dir($dir)
{
    if(!is_dir($dir)) return false;
    shell_exec('rm -rf ' . escapeshellarg(realpath($dir)) . ' 2>&1');
    return true;
}


function generate_ini(array $values)
{
    $ini = '';
    foreach($values as $k => $v) {
        $ini .= '[' . $k . ']'.RN;
        foreach($v as $name => $val) {
            $ini .= $name . ' = ';
            if(is_string($val)) $ini .= '"' . str_replace('"', '\"', $val) . '"'.RN;
            elseif(is_numeric($val)) $ini .= $val.RN;
            else $ini .= boolval($val) ? 'Yes'.RN : 'No'.RN;
        }
        $ini .= RN;
    }
    return $ini;
}


function delete_parent_deps($libname)
{
    global $MATRIX;
    foreach($MATRIX->libraries as $lib) {
        if(in_array($libname, $lib->dependancies)) {
            foreach(glob(ARCH_PATH . $lib->name . '-*', GLOB_ONLYDIR) as $dir) {
                rm_dir($dir);
            }
        }
    }
}


function verify_deps_vs16()
{
    if (!$results = wb_wmi_query("SELECT * FROM MSFT_VSInstance")) return false;
    foreach ($results as $inst) {
        if (!isset($inst['InstallLocation'])) continue;
        if (!isset($inst['Version'])) continue;
        if (version_compare($inst['Version'], '17') >= 0) continue;
        if (version_compare($inst['Version'], '16') < 0) continue;
        $vs = $inst;
        break;
    }
    if (!isset($vs)) return false;
    $path = $vs['InstallLocation'] . '\VC\Auxiliary\Build\vcvars64.bat';
    return is_file($path) ? $path : false;
}


function verify_deps_strawberry_perl()
{
    if (!$path = wcli_where("perl")) return false;
    if (!$results = shell_exec('"' . $path . '" -MConfig -MData::Dump -e "dd \%Config" 2>&1')) return false;
    if (!preg_match('#cf_by\s+=>\s+"(.*)"#i', $results, $m)) return false;
    if ($m[1] != 'strawberry-perl') return false;
    return $path;
}


function verify_deps_git()
{
    return wcli_where('git');
}


function verify_deps_patch()
{
    return wcli_where('patch');
}

