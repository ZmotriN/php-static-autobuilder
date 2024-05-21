<?php

/**
 * ██╗     ██╗██████╗ ███████╗ ██████╗ ██╗     ██╗████████╗███████╗██████╗ 
 * ██║     ██║██╔══██╗██╔════╝██╔═══██╗██║     ██║╚══██╔══╝██╔════╝╚════██╗
 * ██║     ██║██████╔╝███████╗██║   ██║██║     ██║   ██║   █████╗   █████╔╝
 * ██║     ██║██╔══██╗╚════██║██║▄▄ ██║██║     ██║   ██║   ██╔══╝   ╚═══██╗
 * ███████╗██║██████╔╝███████║╚██████╔╝███████╗██║   ██║   ███████╗██████╔╝
 * ╚══════╝╚═╝╚═════╝ ╚══════╝ ╚══▀▀═╝ ╚══════╝╚═╝   ╚═╝   ╚══════╝╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$sqlite3log = LOG . 'libsqlite3.log';


// Verify if libsqlite3 is installed
if (is_dir($path) && is_file($path .  'libsqlite3_a.lib') && is_file(DEPS_PATH . 'lib\libsqlite3_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libsqlite3
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Create Makefile
$contents = file_get_contents(__FILE__, false, null, __COMPILER_HALT_OFFSET__);
file_put_contents($path . 'Makefile', $contents);


// Compile libsqlite3
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'nmake'.RN;
$batfile = TMP . 'build_sqlite3.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($sqlite3log, $ret);


// Verify if the build works
if(!is_file($path . 'libsqlite3_a.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $sqlite3log);
else draw_status($label, "complete", Green);


// Install libsqlite3
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'libsqlite3_a.lib'] = 'lib\libsqlite3_a.lib';
$files[$path . 'sqlite3.h'] = 'include\sqlite3.h';
$files[$path . 'sqlite3ext.h'] = 'include\sqlite3ext.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);


// Makefile contents
__halt_compiler();
CC=cl.exe /nologo
AR=lib.exe /nologo
LINK=link.exe /nologo

!IF "" == "$(MACHINE)"
MACHINE=x64
!ENDIF

!IF "" == "$(CRT)"
CRT=vc15
!ENDIF

!IF "" == "$(PREFIX)"
PREFIX="$(CRT)-$(MACHINE)"
!ENDIF

COMMON_CFLAGS=/D SQLITE_THREADSAFE=1 /DSQLITE_ENABLE_FTS3=1 /D SQLITE_ENABLE_FTS4=1 /D SQLITE_ENABLE_FTS5=1 /D SQLITE_ENABLE_JSON1=1 /D SQLITE_ENABLE_MATH_FUNCTIONS=1 /D SQLITE_ENABLE_GEOPOLY=1 /D SQLITE_ENABLE_COLUMN_METADATA=1 /D SQLITE_CORE=1
!IF "$(DEBUG)"=="1"
SQLITE3_STATIC_BASE=libsqlite3_a_debug
SQLITE3_DLL_BASE=libsqlite3_debug
SQLITE3_EXE_BASE=sqlite3
CFLAGS=$(COMMON_CFLAGS) /Zi /MDd /Od /W3
LDFLAGS=/DEBUG /GUARD:CF /INCREMENTAL:NO
!ELSE
SQLITE3_STATIC_BASE=libsqlite3_a
SQLITE3_DLL_BASE=libsqlite3
SQLITE3_EXE_BASE=sqlite3
CFLAGS=$(COMMON_CFLAGS) /Zi /MD /guard:cf /Zc:inline /Qspectre /Ox /W3 /GF /GL /Gw
LDFLAGS=/GUARD:CF /INCREMENTAL:NO /NXCOMPAT /DYNAMICBASE
!ENDIF


all: $(SQLITE3_STATIC_BASE).lib $(SQLITE3_EXE_BASE).exe $(SQLITE3_DLL_BASE).dll

install: all
	if not exist $(PREFIX)\bin mkdir $(PREFIX)\bin
	if not exist $(PREFIX)\include mkdir $(PREFIX)\include
	if not exist $(PREFIX)\lib mkdir $(PREFIX)\lib
	copy /Y sqlite3.h $(PREFIX)\include
	copy /Y sqlite3ext.h $(PREFIX)\include
	copy /Y $(SQLITE3_STATIC_BASE).lib $(PREFIX)\lib
	copy /Y $(SQLITE3_STATIC_BASE).pdb $(PREFIX)\lib
	copy /Y $(SQLITE3_DLL_BASE).lib $(PREFIX)\lib
	copy /Y $(SQLITE3_DLL_BASE).pdb $(PREFIX)\bin
	copy /Y $(SQLITE3_DLL_BASE).dll $(PREFIX)\bin
	copy /Y $(SQLITE3_EXE_BASE).exe $(PREFIX)\bin
	copy /Y $(SQLITE3_EXE_BASE).pdb $(PREFIX)\bin

clean:
	del *.obj *.lib *.exe *.pdb *.dll *.exp

$(SQLITE3_STATIC_BASE).lib: sqlite3.c sqlite3.h
	$(CC) $(CFLAGS) /Fd$(SQLITE3_STATIC_BASE).pdb /c sqlite3.c
	$(AR) sqlite3.obj /OUT:$(SQLITE3_STATIC_BASE).lib

$(SQLITE3_EXE_BASE).exe: shell.c sqlite3.c sqlite3.h
	$(CC) $(CFLAGS) shell.c sqlite3.c /Fd$(SQLITE3_EXE_BASE).pdb /Fe$(SQLITE3_EXE_BASE).exe

$(SQLITE3_DLL_BASE).dll: sqlite3.c sqlite3.h
	$(CC) $(CFLAGS) /DSQLITE_API=__declspec(dllexport) /Fd$(SQLITE3_DLL_BASE).pdb /c sqlite3.c
	$(LINK) /DLL /OUT:$(SQLITE3_DLL_BASE).dll sqlite3.obj

