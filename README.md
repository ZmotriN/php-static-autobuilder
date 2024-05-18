# PHPSAB - PHP Static Autobuilder
PHP Static Autobuilder is script that let you build static single PHP Embed SAPI application for Windows. It take care of download, compile and install PHP and all of its dependancies for you.

## Warning!
Effin' Windows Defender don't like Embed SAPI. If you get a warning, ignore it. There is no Trojan Horse. All the code is open soure.

## Prerequisites
- Windows 10 or newer
- [Visual C++ 16.0 (Visual Studio 2019)](https://visualstudio.microsoft.com/vs/older-downloads/)
- [Strawberry Perl](https://strawberryperl.com/)
- [Git](https://git-scm.com/download/win)

## Install
1.  Download the latest [binary release](https://github.com/ZmotriN/php-static-autobuilder/releases).
2.  Extract phpsab.exe in a simple path like "c:\php-sdk\phpsab.exe".
3.  Open phpsab.exe by doubleclicking on it or run it by cmd.
4.  Wait until all dependancies are downloaded and compiled.
5.  See the "build" folder.

## Configuration
You can create your own configuration in the "config/" folder.\
Example:
```ini
[build]
target = "php-static.exe" ; Name of target exe file
bootstrap = "phpinfo" ; Bootstrap (see master/bootstrap folder)
clean = false ; Always performs a clean build

[extensions]

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; Windows legacy extensions ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

win32std = Yes ; Mandatory (for res:// stream protocol)
winbinder = Yes ; Windows Interface native support
wcli = Yes ; Windows CLI native support


;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; Windows built-in extensions ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

; ffi = Yes
; com_dotnet = Yes
; readline = Yes
```
For the full list of supported extensions, see [config.full.ini](https://github.com/ZmotriN/php-static-autobuilder/blob/main/config.full.ini)

### Usage
```shell
> phpsab <configname> [test | clean]
```
- configname: the filename of the configuration file without .ini
- test: (optional) to test your configuration without compiling PHP
- clean: (optional) to create a clean build


## Costum matrix file
If you want to work with a costum matrix, copy the file "master/matrix.json" in the root folder. PHPSAB will check for offline matrix before scrap it from github.


## Troubleshooting
If you get a compiling error, before opening an issue, try a clean build.
```shell
> phpsab configname clean
```
If the error persists, open an issue with the appropriate log file. Any issue without log file will be rejected.

# How does it works
After building the static embed libraries, PHPSAB use them to create a very simple program that include the embed SAPI and execute the content of the resource "res:///PHP/RUN". The res stream protocol is provided by win32std extension. So that's where we put the PHP application bootstrap or the PHAR archive. Look at the "master/bootstrap" folder for simple examples.


## Create standalone application
Once you have a functionnal static php,  you have multiple options to create your standalone single file application.

- [Phar-composer](https://github.com/clue/phar-composer) is a simple phar creation for any project managed via Composer.
- [Embeder2](https://github.com/crispy-computing-machine/embedder2) is a command line program to add resources to your .exe file.
- res_set() is a function provided by [win32std](http://wildphp.free.fr/wiki/doku.php?id=win32std:index) extension to add resources to your .exe file.
Example:
```php
$release = __DIR__ . '\php-static.exe';
$contents = file_get_contents(__DIR__ . '\test.phar');
var_dump(res_set($release, 'PHP', 'RUN', $contents));
```

## Winbinder + GD


## License
The scripts and documentation in this project are released under the [MIT License](https://github.com/ZmotriN/php-static-autobuilder/blob/main/LICENSE)
