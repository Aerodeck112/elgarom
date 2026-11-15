<?php

/*
 * Finds the command-line PHP that matches the PHP serving the website.
 *
 * On cPanel the web server runs PHP through FPM or LiteSpeed, and the plain "php" command (or
 * /usr/local/bin/php) is usually the server default, which can be an older version. A cron job set
 * up with it fails with Composer's message «Your Composer dependencies require a PHP version ">= 8.3.0"».
 *
 * Returns [path, found]: found is false when no candidate could be confirmed on this server and the
 * path is only the standard cPanel location for this PHP version.
 */
if (! function_exists('simex_cli_php')) {
    function simex_cli_php(): array
    {
        $mm = PHP_MAJOR_VERSION.PHP_MINOR_VERSION;
        $candidates = [];
        if (PHP_BINARY !== '' && ! preg_match('/fpm|lsphp|cgi|litespeed/i', basename(PHP_BINARY))) {
            $candidates[] = PHP_BINARY;
        }
        $candidates[] = PHP_BINDIR.'/php';
        $candidates[] = "/opt/cpanel/ea-php{$mm}/root/usr/bin/php";
        $candidates[] = "/opt/alt/php{$mm}/usr/bin/php";
        $candidates[] = "/usr/local/php{$mm}/bin/php";
        $candidates[] = '/usr/bin/php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        foreach ($candidates as $path) {
            if (@is_file($path) && @is_executable($path)) {
                return [$path, true];
            }
        }

        return ["/opt/cpanel/ea-php{$mm}/root/usr/bin/php", false];
    }
}
