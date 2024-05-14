<?php

const DIR = "";
const SELF = "";

const RN = "\r\n";
const R = "\r";
const N = "\n";

const Red    = 4;  // Red color
const Green  = 2;  // Green color
const Blue   = 1;  // Blue color
const Yellow = 14; // Yellow color
const Purple = 5;  // Purple color
const Aqua   = 3;  // Aqua color
const White  = 7;  // White color
const Black  = 0;  // Black color
const Grey   = 8;  // Grey color
const Bright = 8;  // Bright Flag

/**
 * Execute a WMI query and return results as an array
 *
 * @param string $query The WMI query to execute
 *
 * @return array|bool
 */
function wb_wmi_query($query): array|bool {}


/**
 * Search filename in environment path.
 *
 * @param string $filename Filename to search for.
 *
 * @return string|bool The full path of the file, otherwise false.
 */
function wcli_where(string $filename): string|bool {}


/**
 * Retrieve console buffer size in characters.
 * Return an array [w, h]
 *
 * @return array|bool Return the buffer size or FALSE.
 */
function wcli_get_buffer_size(): array|bool {}


/**
 * Echo string with colors. Foreground and background colors are optionals.
 *
 * @param string $str String to be echo.
 * @param int $fore Foreground color constant
 * @param int $back Background color constant
 *
 * @return bool True if success, else false.
 */
function wcli_echo(string $str, int $fore = null, int $back = null): bool {}


/**
 * Pause process and wait for a keyboard input.
 *
 * @return int|bool The input character otherwise false.
 */
function wcli_get_key(): int|bool {}


/**
 * Retrieve cursor position.
 * Return an array [x, y]
 *
 * @return array|bool The cursor position or false.
 */
function wcli_get_cursor_position(): array|bool {}


/**
 * Print string with colors at the x-y position without changing cursor position.
 *
 * @param string $str String to be print
 * @param int $x X position in characters
 * @param int $y Y position in characters
 * @param int $fore Foreground color constant
 * @param int $back Background color constant
 *
 * @return bool True if success, else false.
 */
function wcli_print(string $str, int $x = null, int $y = null, int $fore = null, int $back = null): bool {}


/**
 * Method res_set
 *
 * @param string $file
 * @param string $type
 * @param string $name
 * @param string $data
 * @param int $lang
 *
 * @return bool
 */
function res_set( string $file, string $type, string $name, string $data, int $lang = null): bool {}


/**
 * Flash console window.
 *
 * @param bool $invert If TRUE, the window is flashed from one state to the other.
 *
 * @return bool True if success, else false.
 */
function wcli_flash(bool $invert = false): bool {}