# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic
Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- [PR-371](https://github.com/itk-dev/os2loop/pull/371)
  Added GitHub action to build release
  Added os2forms auto deployment

- [PR-370](https://github.com/itk-dev/os2loop/pull/370)
  Added woodpecker to test site deployment

## [1.2.2]

- [PR-369](https://github.com/itk-dev/os2loop/pull/369) Update drupal core 10.4.5

## [1.2.1]

- DevOps: Update docker setup to ensure real IPs in logs
- [PR-363](https://github.com/itk-dev/os2loop/pull/367)
  Security update of Drupal core
  Fixed code error in switch cases

## [1.2.0]

- [PR-366](https://github.com/itk-dev/os2loop/pull/366)
  - Security update
    - Drupal Core 10.3.6 => 10.4.3
    - XLS serialization 1.4.0 => 2.0.0

## [1.1.4]

- [PR-363](https://github.com/itk-dev/os2loop/pull/363)
  Security update

## [1.1.3]

- [PR-363](https://github.com/itk-dev/os2loop/pull/363)
  Security update

## [1.1.2]

- [PR-362](https://github.com/itk-dev/os2loop/pull/362)
  - Change Share with a friend form
  - Add chosen lib
  - Use chosen lib for profession and expertise fields
- [PR-361](https://github.com/itk-dev/os2loop/pull/361)
  Added local OIDC server-mock

## [1.1.1]

- [PR-360](https://github.com/itk-dev/os2loop/pull/360)
  Fix bug in toc

## [1.1.0]

- Update config to mach drupal version.
- [PR-358](https://github.com/itk-dev/os2loop/pull/358)
  Update pdf css to ensure display of all paragraphs

## [1.0.0]

- Upgrade drupal core (10.3.1)
- Switch to dompdf
- Upgrade drupal core (9.5.10)
- Upgrade contrib module
- Change code analysis tool
  drupal-check -> phpstan for more configuration options
- Update custom modules with phpcs and phpstan tools.
- Update configuration to match drupal upgrade.
- Upgrade docker setup to use php 8.1
- [PR-354](https://github.com/itk-dev/os2loop/pull/354)
  1712: Added “Expand all steps” link on step by step
- [PR-353](https://github.com/itk-dev/os2loop/pull/353):
  Security update
- [PR-352](https://github.com/itk-dev/os2loop/pull/352):
  Fix spacing
- [PR-351](https://github.com/itk-dev/os2loop/pull/351):
  Updated tasks
- [PR-341](https://github.com/itk-dev/os2loop/pull/341):
  Notify of collection changed when document is changed.
- [LOOP-862](https://jira.itkdev.dk/browse/LOOP-862): Added documentation for
modules and hooks.
- [LOOP-947](https://jira.itkdev.dk/browse/LOOP-947): Styling user profile page
- [LOOP-948](https://jira.itkdev.dk/browse/LOOP-948): Fix position of user
dropdown menu, that extends outside the viewport on narrow screens.
- [LOOP-950](https://jira.itkdev.dk/browse/LOOP-950): Styling of messages list
- [LOOP-949](https://jira.itkdev.dk/browse/LOOP-949): Styling of subscriptions
page
- [LOOP-732](https://jira.itkdev.dk/browse/LOOP-732),
  [LOOP-733](https://jira.itkdev.dk/browse/LOOP-733) and
  [LOOP-734](https://jira.itkdev.dk/browse/LOOP-734): Drupal, SAML and OpenID
  Connect login
- [LOOP-874](https://jira.itkdev.dk/browse/LOOP-874): Fine-grained
  administrator permissions.
- [LOOP-934](https://jira.itkdev.dk/browse/LOOP-934): Several changes to design
- [LOOP-809](https://jira.itkdev.dk/browse/LOOP-809): Changed search settings,
Add tagging to media library, fix bugged file reference in display
- [LOOP-968](https://jira.itkdev.dk/browse/LOOP-968): Remove Connected accounts
tab
- [LOOP-2056](https://leantime.itkdev.dk/dashboard/home#/tickets/showTicket/2056):
Create new frontpage with fixtures.
