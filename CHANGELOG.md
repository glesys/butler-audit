# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Renamed `Auditor` to `Audit`
- The facade returns the new `Auditor` class instead of `Audit`
- The correlation id is fetched from `Auditor` instead of container.

### Removed
- `AuditorFake`

## [0.1.3] - 2020-12-02

### Added
- Support for PHP 8

## [0.1.2] - 2020-11-20

### Added
- `AuditorFake` for smoother testing

## [0.1.1] - 2020-11-10

### Changed
- Moved macro "withCorrelationId" from `Http` to `PendingRequest`.

## [0.1.0] - 2020-11-06

### Added
- Initial commit
