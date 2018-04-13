<?php
namespace app\release\model;

use app\util\ArrayUtil;

class ReleaseInfoRepository
{
    public static function getLatest(): ?ReleaseInfo
    {
        $releaseInfos = self::getAvailableReleaseInfos();
        return ArrayUtil::getLastElementOrNull($releaseInfos);
    }
    
    public static function getLatestLeadingEdge(string $productName): ?ReleaseInfo
    {
        $releaseInfos = self::getAvailableReleaseInfosByProductName($productName);
        return ArrayUtil::getLastElementOrNull($releaseInfos);
    }
    
    public static function getLatestLongTermSupport(string $productName): ?ReleaseInfo
    {
        $releaseInfos = self::getAvailableReleaseInfosByProductName($productName);
        $releaseInfos = array_filter($releaseInfos, function(ReleaseInfo $releaseInfo) {
            return $releaseInfo->getVersion()->isLongTermSupportVersion();
        });
        return ArrayUtil::getLastElementOrNull($releaseInfos);
    }
    
    public static function getAvailableReleaseInfosByProductName(string $productName): array
    {
        $releaseInfos = self::getAvailableReleaseInfos();
        $releaseInfos = array_filter($releaseInfos, function(ReleaseInfo $releaseInfo) use ($productName) {
            return $releaseInfo->hasVariantWithProductName($productName);
        });
        return $releaseInfos;
    }
    
    public static function getAvailableReleaseInfos(): array
    {
        $releaseInfos = [];
        $directories = array_filter(glob(IVY_RELEASE_DIRECTORY . DIRECTORY_SEPARATOR . '*'), 'is_dir');
        foreach ($directories as $directory) {
            // drop not ready, because it is still uploading
            $releaseNotReadyFile = $directory . DIRECTORY_SEPARATOR . 'NotReady.txt';
            if (file_exists($releaseNotReadyFile)) {
                continue;
            }
            
            $versionNumber = basename($directory);
            // drop e.g. nightly or sprint
            if (!Version::isValidVersionNumber($versionNumber)) {
                continue;
            }
            // drop special folder
            if ($versionNumber == '0.0.1') {
                continue;
            }
            
            $safeVersion = '';
            if (isset(UNSAFE_RELEASES[$versionNumber])) {
                $safeVersion = UNSAFE_RELEASES[$versionNumber];
            }
            $fileNames = glob($directory . '/downloads/*.zip');
            $releaseInfos[] = new ReleaseInfo($versionNumber, $fileNames, $safeVersion);
        }
        $releaseInfos = ReleaseInfo::sortReleaseInfosByVersionOldestFirst($releaseInfos);
        return $releaseInfos;
    }
    
    public static function getNightlyArtifacts(): array
    {
        return Artifact::createArtifacts(IVY_NIGHTLY_RELEASE_DIRECTORY, CDN_HOST_NIGHTLY, PERMALINK_NIGHTLY);
    }
    
    public static function getSprintArtifacts(): array
    {
        return Artifact::createArtifacts(IVY_SPRINT_RELEASE_DIRECTORY, CDN_HOST_SPRINT, PERMALINK_SPRINT);
    }
}
