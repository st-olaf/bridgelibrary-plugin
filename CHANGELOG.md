# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] — 2023-07-25

- Feature: add fields for additional librarian data

## [1.3.5] — 2023-06-29

- Tweak: allow theme to access Google Analytics ID

## [1.3.4] — 2023-06-09

- Feature: add course code to admin columns
- Bugfix: fix reading list citation Primo URL
- Bugfix: fix pluralization inconsistency

## [1.3.3] — 2023-05-08

- Bugfix: check for resource array keys before using them

## [1.3.2] — 2023-03-24

- Change: add debug logging for background processes
- Chore: update PHP dependencies

## [1.3.1] — 2023-03-19

- Bugfix: fix fallback content for deleted favorites
- Bugfix: fix Primo URL format

## [1.3.0] — 2023-03-01

- Feature: add ajax endpoints for managing user favorites
- Feature: sync instructor names to courses
- Feature: add wp-cli commands to sync Alma courses
- Feature: associate LibGuides subject guides with the relevant academic department(s)
- Feature: add fallback resources for librarians and guides
- Feature: hide unpublished/private LibGuides guides
- Feature: add ability to hide all or specific courses for academic departments
- Bugfix: fix Alma requests
- Bugfix: fix Google Analytics event tracking
- Bugfix: fix various code quality issues

## [1.2.3] — 2022-11-18
- Bugfix: fix issue when retrieving Primo URL for resources
- Bugfix: fix issue when adding academic department for courses
- Bugfix: fix user cleanup routine

## [1.2.2] — 2022-11-17
- Bugfix: fix custom term count handling

## [1.2.1] — 2022-11-17
- Feature: add daily cron job logging

## [1.2.0] — 2022-11-17
- Feature: merge academic department-level resources into course-level core resources
- Change: remove UI to import LibGuides assets to a course
- Change: remove academic department institution nesting
- Bugfix: fix librarian display
- Bugfix: fix other miscellaneous small bugs

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
