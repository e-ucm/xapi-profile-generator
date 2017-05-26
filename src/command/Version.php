<?php


namespace es\eucm\xapi\command;

use SebastianBergmann\Version as VersionId;

class Version
{

    private static $pharVersion;

    private static $version;

    public static function id()
    {
        if (self::$pharVersion !== null) {
            return self::$pharVersion;
        }
        if (self::$version === null) {
            $version       = new VersionId('0.5.1', \dirname(\dirname(__DIR__)));
            self::$version = $version->getVersion();
        }
        return self::$version;
    }

    /**
     * @return string
     */
    public static function getVersionString()
    {
        return 'PHPUnit ' . self::id() . ' by e-UCM Team';
    }

    /**
     * @return string
     */
    public static function getReleaseChannel()
    {
        if (\strpos(self::$pharVersion, '-') !== false) {
            return '-nightly';
        }
        return '';
    }
}
