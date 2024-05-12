<?php

const VERSION = "0.1.0";
const TMP = DIR.'tmp\\';
const MAX_BUFFER_WIDTH = 100;



draw_header("PHP Static Autobuilder");



// phpinfo(); return;

$MATRIX = json_decode(file_get_contents(DIR.'matrix.json'));
$CONFIG = parse_ini_file(DIR.'configs\default.ini', true, INI_SCANNER_TYPED);




// TODO: CHECK NASM


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
 * Verify Patch
 */
if($patchpath = verify_deps_patch()) {
    define('PATCH_PATH', $patchpath);
    draw_status("Patch", "found", Green);
} else {
    draw_status("Patch", "missing", Red);
    exit_error();
}


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
 * Download and install PHP SDK Binary Tools
 */
define('SDK_PATH', DIR.'sdk\phpsdk-vs16-x64.bat');
if(!is_file(SDK_PATH)) {
    $sdkzip = TMP.'sdk-binary-tools.zip';
    $sdkdir = DIR.'php-sdk-binary-tools-master';
    $sdkdest = DIR.'sdk';
    if(!download_file("https://github.com/php/php-sdk-binary-tools/archive/refs/heads/master.zip", $sdkzip, "php-sdk-binary-tools")) exit_error();
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
        draw_status('Architecture tree', 'failed', Red);
        exit_error();
    }
} else {
    draw_status('Architecture tree', 'found', Green);
}


// TODO: CLONE MASTER



echo RN.RN;
draw_header("Library dependancies");





$libraries = [];


define('DEPS_PATH', ARCH_PATH.'deps\\');

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



foreach($MATRIX->libraries as $lib) {
    if(in_array($lib->name, $libraries)) {
        // print_r($lib);
        include(DIR.'master\libraries\\' . $lib->install_script);


        
    }
}
















function shell_exec_vs16($taskfile, $verbose = false)
{
    if($verbose) {
        passthru(escapeshellarg(SCRIPT_VS16) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
    } else {
        shell_exec(escapeshellarg(SCRIPT_VS16) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
    }
}



function shell_exec_vs16_phpsdk($taskfile, $verbose = false)
{
    if($verbose) {
        passthru(escapeshellarg(SCRIPT_VS16_PHPSDK) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
    } else {
        shell_exec(escapeshellarg(SCRIPT_VS16_PHPSDK) . ' ' . escapeshellarg($taskfile) . ' 2>&1');
    }
}


function draw_status(string $label, string $status, int $color, int $max = MAX_BUFFER_WIDTH)
{
    draw_line($label, $status, $color, $max);
    echo RN;
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
    $msg .= ". Press a key to exit.";
    wcli_echo(RN.$msg, Red);
    wcli_get_key();
    exit(1);
}


function download_file($url, $dest, $label) {
    $label = 'Downloading '.$label;
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


function rename_wait($src, $dst, $sec = 10) {
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
    $label = "Unzipping " . pathinfo($zipfile, PATHINFO_BASENAME);
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

    $label = "Unzipping " . pathinfo($tarfile, PATHINFO_BASENAME);
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


function zip_fist_dir($zipfile)
{
    $zip = new ZipArchive;
    if (!$zip->open($zipfile) === TRUE) return false;
    if(!$first = $zip->getNameIndex(0)) return false;
    $zip->close();
    return rtrim($first, '/');
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

