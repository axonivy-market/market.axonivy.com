<?php 
namespace app;

use app\util\StringUtil;

class Config {
    
    public static function initConfig()
    {
        define('CDN_HOST', 'https://download.axonivy.com');
        
        define('BASE_URL', self::getRequestedBaseUri());
        define('PERMALINK_BASE_URL', BASE_URL . '/permalink/');
        
        define('MAVEN_SUPPORTED_RELEASES_SINCE_VERSION', '6.0.0');
        
        $rootDir = '/home/axonivya/data/ivy-releases';
        if (self::isDevOrTestEnv()) {
            $rootDir = __DIR__ . '/../../src/web/releases/ivy';
        }
        define('IVY_RELEASE_DIRECTORY', StringUtil::createPath([$rootDir]));
        
        define('DOC_DIRECTORY_THIRDPARTY', '/home/axonivya/data/doc-cache');
        define('CLONE_DOC_SCRIPT', '/home/axonivya/script/clonedoc.sh');
        
        define('UNSAFE_RELEASES', [
            '6.7.1' => '7.0',
            '6.7.0' => '7.0',
            '6.6.1' => '7.0',
            '6.6.0' => '7.0',
            '6.5.0' => '7.0',
            '6.4.0' => '7.0',
            '6.3.0' => '7.0',
            '6.2.0' => '7.0',
            '6.1.0' => '7.0',
            '6.0.10' => '6.0.11',
            '6.0.9' => '6.0.11',
            '6.0.8' => '6.0.11',
            '6.0.7' => '6.0.11',
            '6.0.6' => '6.0.11',
            '6.0.5' => '6.0.11',
            '6.0.4' => '6.0.11',
            '6.0.3' => '6.0.11',
            '6.0.2' => '6.0.11',
            '6.0.1' => '6.0.11',
            '6.0.0' => '6.0.11',
        ]);
        
        define('IVY_VERSIONS', [
            // '8.x' => 'Leading Edge - LE',
            '8.0' => 'Long Term Support - LTS',
            '7.x' => 'UNSUPPORTED',
            '7.0' => 'Long Term Support - LTS',
            '6.0' => 'UNSUPPORTED',
            '6.x' => 'UNSUPPORTED',
            '5.1' => 'UNSUPPORTED',
            '5.0' => 'UNSUPPORTED',
            '4.3' => 'UNSUPPORTED',
            '4.2' => 'UNSUPPORTED',
            '3.9' => 'UNSUPPORTED'
        ]);
        
        $lts = [];
        foreach (IVY_VERSIONS as $version => $description) {
            if ($description == 'Long Term Support - LTS') {
                $lts[] = $version;
            }
        }
        
        define('LTS_VERSIONS', $lts);
        
        define('MAVEN_ARTIFACTORY_URL', 'https://repo.axonivy.rocks/');
    }
    
    private static function getRequestedBaseUri(): string
    {
        return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    }
    
    public static function isDevOrTestEnv(): bool
    {
        return file_exists(StringUtil::createPath([__DIR__, '..', '..', 'Jenkinsfile']));
    }
}
