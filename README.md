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
; readline = true
```
For the full list of extensions, see [config.full.ini](https://github.com/ZmotriN/php-static-autobuilder/blob/main/config.full.ini)

### Usage
```shell
> phpsab <configname> [test | clean]
```
- configname: the filename of the configuration file without .ini
- test: (optional) to test your configuration without compiling PHP
- clean: (optional) to create a clean build


## Costum matrix file

## Troubleshooting

## Create standalone application

## Collaboration

## TODO
- Include phar file by resource

## About
