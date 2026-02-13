# Change Log
All notable changes to the Record Autonumbering module will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.1.0] - 2026-02-12
- New auto-numbering option RandomAlphanumeric (Eric Weber)

## [1.0.8] - 2026-01-06
- Improved config validation including check of custom record format against record id field validation type
- Display config validation errors in red highlight on project Module page
- Wording changes in readme and config description
- Double check for existence of first record in DAG in case of problem with `starts_with()`

## [1.0.7] - 2025-05-23
- Fix bug with save buttons nextform/exitrecord that were instead returning to record home
- Replace constructor with initialisation function called from each hook function
- Remove custom class from "Clarity" project from public repo
- Update to framework version 16

## [1.0.6] - 2023-01-13
-Bug fix in autoincrement from seed autonumber generator

## [1.0.5] - 2022-07-26
- Update to framework version 8, min REDCap version 11.1.1
- Remove use of built-in constants in constructor for PHP8 compatibility
- Prevent lock/esig for unsaved records because record id does can not necessarily be determined
- Handle record auto-numbering when creating a record via the Scheduling page
- Attempt to utilise REDCap::reserveNewRecordId() but backed out - adds to cache but returns false
- Fix Project Setup page default dialog text and auto-edit module settings on Setup button click

## [0.0.503] - 2021-04-20
### Added
- Add Taryn Stoff's user guide to README (Kyle Chesney)

## [0.0.502] - 2021-03-22
### Added
- Revise module description in config.json to align with README (Philip Chase)
- Update README based on Taryn Stoff's user guide (Kyle Chesney, Taryn Stoffs)
- Fix DAG form-save bug (mbentz)
- Fix crash of module when using "padded integer increment with prefix" caused by abstract method getRequiredDataEntryFields needing declaration (Kyle Chesney)
- Ensure module is enabled before initializing to prevent REDCap core's autonumbering enabling in *every* project (Kyle Chesney)

## [0.0.501] - 2020-12-09
### Added
- Redirect with js (Luke)

## [0.0.301] - 2019-05-23
### Added
- Update our repository with luke's 0.0.3 changes, namely, record_autonumber_v0.0.3 (Luke)

## [0.0.101] - 2019-04-19
### Summary
- This is the first release.

### Added
- Remove default value for project-setup-dialog-text (Philip Chase)
- Initial commit (Luke)
