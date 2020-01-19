<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\services;

use craft\helpers\FileHelper;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use MaxMind\Db\Reader\InvalidDatabaseException;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Component;
use superbig\countryredirect\models\Link;
use superbig\countryredirect\models\Settings;
use yii\base\ErrorException;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property null                                                           $bannerCookie
 * @property null|string|int                                                $countryCode
 * @property null                                                           $ipAddress
 * @property mixed                                                          $overrideLocaleParam
 * @property \superbig\countryredirect\services\CountryRedirect_BannerModel $banner
 * @property string                                                         $countryFromIpAddress
 * @property array                                                          $links
 * @property null                                                           $countryCookie
 * @property null                                                           $info
 * @property mixed                                                          $browserLanguages
 * @property Settings                                                       $settings
 */
class DatabaseService extends Component
{
    // Public Methods
    // =========================================================================

    protected $urls;
    protected $config;
    protected $localDatabaseFilename;
    protected $localDatabasePath;
    protected $unpackedDatabasePath;
    protected $localDatabasePathWithoutFilename;
    protected $settings;

    public function init()
    {
        parent::init();

        $this->settings = CountryRedirect::$plugin->getSettings();
    }

    public function getCountryFromIp($ipAddress)
    {
        // This creates the Reader object, which should be reused across lookups.
        try {
            $reader = new Reader($this->settings->getCountryDbPath());

            return $reader->country($ipAddress);
        } catch (InvalidDatabaseException $e) {
            return null;
        } catch (AddressNotFoundException $e) {
            return null;
        }
    }

    public function checkLicenseKey()
    {
        if (!$this->settings->hasValidLicenseKey()) {
            $error = $this->formatErrorMessage('Invalid MaxMind license key. Generate one at {url}', [
                'url' => $this->settings->accountAreaUrl,
            ]);

            $this->logError($error);

            return [
                'error' => $error,
            ];
        }
    }

    /**
     * @return array
     * @throws \yii\base\ErrorException
     */
    public function downloadDatabase()
    {
        $settings      = $this->settings;
        $dbPath        = $settings->getDbPath(null, true);
        $tempPath      = $settings->getTempPath();
        $countryDbPath = $settings->getCountryDbPath();

        if (!FileHelper::isWritable($dbPath)) {
            $error = $this
                ->formatErrorMessage('Database folder is not writeable: {path}', [
                    'path' => $dbPath,
                ]);

            return $this->logError($error, __METHOD__);
        }

        $tempFile = $settings->getCountryDbPath($isTemp = true);

        $this->logInfo('Downloading database to: {path}' . $countryDbPath, __METHOD__);

        try {
            (new Client())
                ->get($settings->getCountryDownloadUrl(), [
                    'sink' => $tempFile,
                ]);
            //@unlink($tempFile);
        } catch (ConnectException $e) {
            $error = $this->formatErrorMessage('Failed to connect to {url}: {error}', [
                'url'   => $settings->getCountryDownloadUrl(),
                'error' => $e->getMessage(),
            ]);

            return $this->logError($error);
        } catch (ClientException $e) {
            $error = $this->formatErrorMessage('Failed to download {url}: {error}', [
                'url'   => $settings->getCountryDownloadUrl(),
                'error' => $e->getMessage(),
            ]);

            return $this->logError($error);
        } catch (\Exception $e) {
            $error = $this->formatErrorMessage('Failed to get country database {url}: {error}', [
                'url'   => $settings->getCountryDownloadUrl(),
                'error' => $e->getMessage(),
            ]);

            return $this->logError($error);
        }

        return [
            'success' => true,
        ];
    }

    /**
     * @return array
     */
    public function unpackDatabase()
    {
        $settings       = $this->settings;
        $checksumUrl    = $settings->getCountryChecksumDownloadUrl();
        $countryDbPath  = $settings->getCountryDbPath($temp = true);
        $remoteChecksum = null;

        try {
            $guzzle   = new Client();
            $response = $guzzle
                ->get($checksumUrl);

            $remoteChecksum = (string)$response->getBody();

            // Verify checksum
            if (md5(file_get_contents($countryDbPath)) !== $remoteChecksum) {
                $error = $this->formatErrorMessage('Remote checksum for Country database doesn\'t match downloaded database. Please try again or contact support.',);

                return $this->logError($error, __METHOD__);
            }
        } catch (\Exception $e) {
            $error = $this
                ->formatErrorMessage('Was not able to get checksum from GeoLite url: {url}', [
                    'url' => $checksumUrl,
                ]);

            return $this->logError($error, __METHOD__);
        }

        try {
            $this->findAndWriteCountryDatabase($remoteChecksum);
        } catch (\Exception $e) {
            return $this->logError($e->getMessage(), __METHOD__);
        }

        return [
            'success' => true,
        ];
    }

    /**
     * @return bool
     */
    public function checkValidDb()
    {
        return @file_exists(CountryRedirect::$plugin->getSettings()->getCountryDbPath());
    }

    public function getLastUpdateTime()
    {
        if (!$this->checkValidDb()) {
            return null;
        }

        $time = FileHelper::lastModifiedTime(CountryRedirect::$plugin->getSettings()->getCountryDbPath());

        return new \DateTime("@{$time}");
    }

    private function logError(string $error, $category = 'country-redirect')
    {
        Craft::error($error, $category);

        return [
            'error' => $error,
        ];
    }

    private function logInfo(string $message, $category = 'country-redirect')
    {
        Craft::info($message, $category);
    }

    private function formatErrorMessage($error, $vars = [])
    {
        return Craft::t('country-redirect', $error, $vars);
    }

    private function findAndWriteCountryDatabase()
    {
        $settings      = $this->settings;
        $countryDbPath = $settings->getCountryDbPath();
        $tempFile      = $settings->getCountryDbPath($isTemp = true);
        $found         = false;
        $archive       = new \PharData($tempFile);

        foreach (new \RecursiveIteratorIterator($archive) as $file) {
            $fileInfo = pathinfo($file);

            if (!empty($fileInfo['extension']) && 'mmdb' === $fileInfo['extension']) {
                $found  = true;
                $result = $file->getContent();

                try {
                    FileHelper::writeToFile($countryDbPath, $result);

                    @unlink($tempFile);
                } catch (ErrorException $e) {
                    $error = $this->formatErrorMessage('Failed to write country database to {path}', [
                        'path' => $countryDbPath,
                    ]);

                    throw new \Exception($error);
                }
            }
        }

        if (!$found) {
            $error = $this->formatErrorMessage('Did not find database in archive', [
            ]);

            throw new \Exception($error);
        }
    }
}
