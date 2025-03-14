# HOMM XML Sitemap Changelog

## 2.0.0 - 2025-03-14

- Craft CMS 5.x compatibility

## 1.0.3 - 2022-12-16

- Ignore disabled sites

## 1.0.2 - 2022-11-24

- Honor addTrailingSlashesToUrls also for baseUrl

## 1.0.1 - 2022-07-26

- fixed per entry indexing flag

## 1.0.0 - 2022-07-26

- Craft CMS 4 ready

## 0.0.5 - 2021-08-06

- Fixed commerce productType settings save action

## 0.0.4 - 2021-08-06

- Fixed commerce productType frequency and priority

## 0.0.3 - 2021-08-06

- Fixed commerce product query
- Changed full PHP qualifiers with imports

## 0.0.2 - 2021-08-06

- Added craft commerce support

## 0.0.1 - 2021-07-29

- Forked and renamed Project
- Added logos

# OLD: Sitemap Changelog

## 1.3.2- 2020-10-27

### Fixed

- Changed the uppercase "S" to a lowercase "s" to fix a Composer 2 compatibility issue.

## 1.3.1 - 2020-10-22

### Fixed

- Fixed error when adding a category by changing table used to `Table::CATEGORYGROUPS`
- Added empty check for linkId to prevend future type errors

## 1.3.0 - 2020-07-03

### Fixed

- Fixed compatibility with Craft CMS 3.4 +
- Added fix for deleted sites
- Fix and/or operation order

## 1.2.0 - 2019-11-01

### Fixed

- Fixed compatibility with Craft CMS 3.2 +
- Fixed showing multiple records for revisions
- Fixed bug showing url's with expired dates

## 1.1.0 - 2019-03-29

### Added

- Added Craft CMS 3.1.20 Project Config Support

## 1.0.9 - 2018-02-21

### Added

- Added support for alternate language support for Google for multi language websites

## 1.0.8 - 2018-02-21

### Fixed

- Fixed a bug causing the settings routes and section not available in with Craft CMS 3.0.0-RC11

### Changed

- The required minimal Craft version and checked the compatibility

## 1.0.7 - 2018-01-18

### Fixed

- Fixed bug with multi-site setup configured in de config files

### Changed

- Make more use of the Craft framework for future compatibility

## 1.0.6 - 2018-01-18

### Changed

- Use the siteUrl from the general config as prefered base URL
- Checked compatibility with Craft RC6

## 1.0.5 - 2017-12-18

### Fixed

- Fixed bug if there are no sections or categories in the website

## 1.0.4 - 2017-12-18

### Added

- Added <lastmod> column to the XML
- Added category section to include in sitemap
- Register crawler visits

### Changed

- Changed the documentation

## 1.0.1 - 2017-12-14

### Changed

- Documentation update

### Fixed

- A bug that requires the user to login into the CP to view the sitemap.xml content.

## 1.0.0 - 2017-12-13

### Added

- Initial release
