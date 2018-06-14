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
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use MaxMind\Db\Reader\InvalidDatabaseException;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Component;
use superbig\countryredirect\models\Link;

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
 */
class CountryRedirect_DatabaseService extends Component
{
    // Public Methods
    // =========================================================================

    protected $urls;
    protected $config;
    protected $localDatabaseFilename;
    protected $localDatabasePath;
    protected $unpackedDatabasePath;
    protected $localDatabasePathWithoutFilename;

    public function init()
    {
        parent::init();

        $this->urls                             = [
            'city'            => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
            'country'         => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz',
            'countryChecksum' => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5',
        ];
        $this->localDatabaseFilename            = 'GeoLite2-Country.mmdb.gz';
        $this->localDatabasePathWithoutFilename = rtrim(dirname(__FILE__, 2) . '/database/', '/');
        $this->localDatabasePath                = rtrim(dirname(__FILE__, 2) . '/database/', '/') . DIRECTORY_SEPARATOR . $this->localDatabaseFilename;
        $this->unpackedDatabasePath             = str_replace('.gz', '', $this->localDatabasePath);
    }

    public function getCountryFromIp($ipAddress)
    {
        // This creates the Reader object, which should be reused across lookups.
        try {
            $reader = new Reader($this->unpackedDatabasePath);

            return $reader->country($ipAddress);
        } catch (InvalidDatabaseException $e) {
            return null;
        } catch (AddressNotFoundException $e) {
            return null;
        }
    }

    /**
     * @return array
     * @throws \yii\base\ErrorException
     */
    public function downloadDatabase()
    {
        if (!FileHelper::isWritable($this->localDatabasePathWithoutFilename)) {
            Craft::error('Database folder is not writeable: ' . $this->localDatabasePathWithoutFilename, __METHOD__);

            return [
                'error' => 'Database folder is not writeable: ' . $this->localDatabasePathWithoutFilename,
            ];
        }

        $tempPath = Craft::$app->path->getTempPath() . DIRECTORY_SEPARATOR . 'countryredirect' . DIRECTORY_SEPARATOR;
        $tempFile = $tempPath . $this->localDatabaseFilename;

        FileHelper::createDirectory($tempPath);
        Craft::info('Download database to: ' . $this->localDatabasePath, __METHOD__);

        try {
            $guzzle = new Client();

            $guzzle
                ->get($this->urls['country'], [
                    'sink' => $tempFile,
                ]);

            @unlink($this->localDatabasePath);
            FileHelper::createDirectory($this->localDatabasePathWithoutFilename);
            copy($tempFile, $this->localDatabasePath);
            @unlink($tempFile);
        } catch (\Exception $e) {
            Craft::error('Failed to write downloaded database to: ' . $this->localDatabasePath . ' ' . $e->getMessage(), __METHOD__);

            return [
                'error' => 'Failed to write downloaded database to file',
            ];
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
        try {
            $guzzle   = new Client();
            $response = $guzzle
                ->get($this->urls['countryChecksum']);

            $remoteChecksum = (string)$response->getBody();
        } catch (\Exception $e) {
            Craft::error('Was not able to get checksum from GeoLite url: ' . $this->urls['countryChecksum'], __METHOD__);

            return [
                'error' => 'Failed to get remote checksum for Country database',
            ];
        }

        $result = gzdecode(file_get_contents($this->localDatabasePath));

        if (md5($result) !== $remoteChecksum) {
            Craft::error('Remote checksum for Country database doesn\'t match downloaded database. Please try again or contact support.', __METHOD__);

            return [
                'error' => 'Remote checksum for Country database doesn\'t match downloaded database. Please try again or contact support.',
            ];
        }

        Craft::info('Unpacking database to: ' . $this->unpackedDatabasePath, __METHOD__);
        $write = file_put_contents($this->unpackedDatabasePath, $result);

        if (!$write) {
            Craft::error('Was not able to write unpacked database to: ' . $this->unpackedDatabasePath, __METHOD__);

            return [
                'error' => 'Was not able to write unpacked database to: ' . $this->unpackedDatabasePath,
            ];
        }

        @unlink($this->localDatabasePath);

        return [
            'success' => true,
        ];
    }

    /**
     * @return bool
     */
    public function checkValidDb()
    {
        return @file_exists($this->unpackedDatabasePath);
    }
}
