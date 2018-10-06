<?php

class Logger
{
    const LEVEL_EMERG = "emerg";
    const LEVEL_ALERT = "alert";
    const LEVEL_CRIT = "crit";
    const LEVEL_ERR = "err";
    const LEVEL_WARNING = "warning";
    const LEVEL_NOTICE = "notice";
    const LEVEL_INFO = "info";
    const LEVEL_DEBUG = "debug";

    private static $identifier;

    /**
     * @param string $identifier
     */
    public static function init( $identifier )
    {
        self::$identifier = strtolower( $identifier );
    }

    /**
     * @param string $message
     */
    public static function fatal( $message )
    {
        self::log( Logger::LEVEL_EMERG, $message );
        exit( 1 );
    }

    /**
     * @param string $level
     * @param string $message
     */
    public static function log( $level, $message )
    {
        //debug_print_backtrace();

        switch( $level )
        {
            case Logger::LEVEL_DEBUG:
                return;
        }

        echo $level . ": " . $message . "\n";
        //$cmd = "echo \"" . addcslashes( $message, '"`' ) . "\" | systemd-cat -p \"" . $level . "\" -t \"" . self::$identifier . "\"";
        //exec( $cmd );
    }

    public static function errorHandler( $errno, $errstr, $errfile, $errline )
    {
        if( !( error_reporting() & $errno ) )
        {
            // This error code is not included in error_reporting
            return true;
        }

        self::log( Logger::LEVEL_ERR, "[" . $errno . "] " . $errstr . " in " . $errfile . " on line " . $errline );

        /* Don't execute PHP internal error handler */
        return true;
    }

    public static function exceptionHandler( $exception )
    {
        self::log( Logger::LEVEL_CRIT, "[" . $exception->getCode() . "] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() );
    }

    public static function fatalHandler( $error )
    {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];

        self::log( Logger::LEVEL_CRIT, "[" . $errno . "] " . $errstr . " in " . $errfile . " on line " . $errline );
    }
}
