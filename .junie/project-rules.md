# Project Development Guide

This document provides essential information for AI and developers working on the VisBundle project.

## Project Context
VisBundle is a Symfony bundle for building administration interfaces. It features a plugin architecture, user/role management, and dynamic UI components.

## Development Commands
All commands should be executed within the Docker container.

### Testing
- **Run PHPUnit tests:**
  `docker exec vis-bundle-web-1 composer test`
- **Goal:** Maintain 100% code coverage.

### Code Quality & Static Analysis
- **PHPStan (Static Analysis):**
  `docker exec vis-bundle-web-1 composer bin-phpstan`
- **Easy Coding Standard (ECS) - Fix issues:**
  `docker exec vis-bundle-web-1 composer bin-ecs-fix`
- **Rector (Automated Refactoring):**
  `docker exec vis-bundle-web-1 composer bin-rector-process`

## Project Structure Highlights
- `.developer/`: Additional development documentation.
- `.junie/`: AI-specific configuration and documentation.
- `src/Core`: Core services like `PluginService`.
- `src/Command`: CLI tools for project initialization and management.


