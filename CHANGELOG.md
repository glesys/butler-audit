# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Accept header in audit job.

## [0.7.0] - 2023-03-01

### Changed
- **BREAKING**: Require Laravel 10.

### Added
- Laravel pint.

## [0.6.1] - 2022-04-01

### Changed
- Add type-hinting to ArrayAccess-methods in Audit to suppress deprecation notices.

## [0.6.0] - 2022-02-14

### Changed
- **BREAKING**: Require Laravel 9.

## [0.5.0] - 2021-11-22

### Added
- "correlationTrail" in the audit request body.
- A "X-Correlation-Trail" header in the http macro `withCorrelation`.

### Changed
- **BREAKING**: Renamed macro `withCorrelationId` to `withCorrelation`.
- **BREAKING**: Renamed class `WithCorrelationId` to `WithCorrelation`.
- **BREAKING**: Renamed class `SetCorrelationId` to `SetCorrelation`.

## [0.4.1] - 2021-11-12

### Changed
- Make Auditor::initiatorResolver static

## [0.4.0] - 2021-09-23

### Added
- **BREAKING:**: Handle correlation id for queued job by extending job "Dispatcher" and adding "WithCorrelationId" trait.
- **BREAKING:**: Registers a default "initiator resolver".

### Changed
- **BREAKING:**: Require PHP 8.0.

## [0.3.0] - 2021-02-04

### Changed
- **BREAKING:**: Event and initiator context value must be null or scalar.

## [0.2.0] - 2021-01-20

### Changed
- Renamed `Auditor` to `Audit`
- The facade returns the new `Auditor` class instead of `Audit`
- The correlation id is fetched from `Auditor` instead of container.
- **BREAKING**: Moved `initiatorResolver` from `Audit` to `Auditor`.

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
