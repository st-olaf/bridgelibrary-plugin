# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] — 2022-05
- Bugfix: fix some type errors

## [1.1.0] — 2022-05
- Feature: use academic institution as parent department ID
- Feature: add user interest feeds
- Feature: add the ability to import assets for a single guide
- Feature: add ability to copy resources to another course
- Chore: update dependencies

## [1.0.6] — 2020-02
- Feature: update some Primo API settings and fields to comply with their changes
- Feature: add course-level librarians
- Bugfix: prevent reading lists from overwriting related resources
- Bugfix: prevent multiple courses from appearing on single course template

## [1.0.5] — 2019-12-20
- Feature: add course term to resource/course mapping field
- Bugfix: prepend resource URLs with `http` if no protocol is specified
- Bugfix: update course section field type to accommodate text

## [1.0.4] — 2019-12-13
- Bugfix: search course code and number in “Related Courses” ACF field

## [1.0.3] — 2019-12-13
- Feature: reorganize a bit of code to a more logical place
- Bugfix: fix some PHP errors
- Bugfix: fix ACF JSON storage directory
- Bugfix: fix St. Olaf taxonomy typo

## [1.0.2] — 2019-12-04
- Bugfix: fix typo in institution taxonomy term slug
- Feature: display course code in admin course list
- Feature: include course code and number in search queries

## [1.0.1] — 2019-12-02
- Bugfix: when possible, fall back to `wp_postmeta` if ACF custom DB tables query fails.

## [1.0.0] - 2019-09-11
- Initial release
