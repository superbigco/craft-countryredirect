# Country Redirect Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased (2.1.0)

> {warning} You now have to register for a MaxMind account and obtain a license key in order to download the GeoLite2 databases. This is due to a change that was introduced December 30th 2019, [to comply with GDPR and CCPA](https://blog.maxmind.com/2019/12/18/significant-changes-to-accessing-and-using-geolite2-databases/). After upgrading to 2.1.0, you need to add the license key in the Country Redirect plugin settings to continue downloading the databases. Check the readme for detailed instructions on this.

### Added
- Added new utility, that gathers settings, logs and other info in one page.
- Added preview tool to more easily control how your site will react in different scenarios 
- Added setting for including existing query arguments when redirecting
- Added setting `dbPath` to override where the plugin stores its database. By default it will now use the storage path instead of the plugin directory. 💯 improvement.
- Converted hardcoded filenames and urls to settings: `countryDbFilename`, `cityDbFilename`, `countryDbDownloadUrl`, `cityDbDownloadUrl`
- Added log entry when there is no database
- Added `ext-zlib` as dependency to reduce environment errors

### Fixed
- Country codes set in the config file is now case insensitive
- Removed duplicate console command

### Changed
- Database files will now be stored in the storage path by default, instead of in the plugin folder. 
- Tweaked console output when updating the database 
- Moved log to a new utility page.
- A cookie will no longer be set if there is no database 

## [2.0.13] - 2019-08-19
### Added
- Added helper to append override param to a url

## [2.0.12] - 2019-06-24
### Fixed
- Fixed exception when plugin was called before the application was fully initialized.

## [2.0.11] - 2019-02-05
### Fixed
- Fixed exception when plugin was run too early

## [2.0.10] - 2019-01-29
### Fixed
- Fixed path checking when matching element route

## [2.0.9] - 2019-01-29
### Added
- Added way to pass current site and handle to info endpoint

## [2.0.8] - 2019-01-29
### Added
- Added method to get banner cookie name + param from Twig 

### Fixed
- Added param when redirecting by clicking url in banner that sets the banner cookie

## [2.0.7] - 2019-01-25
### Changed
- Country Redirect now requires Craft 3.1

### Fixed
- Aliases used in urls will now be parsed 

## [2.0.6] - 2019-01-24
### Added
- Added variable method to get banner cookie name

## [2.0.5] - 2019-01-24
### Fixed
- Fixed error when there is no matching banner

## [2.0.4] - 2019-01-24
### Added
- Added option for setting banner for matching country code

## [2.0.3] - 2019-01-24
### Added
- Added endpoint to get Geo information + redirect banner

## [2.0.2] - 2018-08-14
### Changed
- Fixed site check

## [2.0.1] - 2018-07-02
### Changed
- Fixed wrong class references

## [2.0.0] - 2018-06-15
### Added
- Initial release
