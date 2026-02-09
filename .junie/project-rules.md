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
- **Strict Rule:** `@codeCoverageIgnore` must never be used. All code paths must be tested.

### Code Quality & Static Analysis
- **PHPStan (Static Analysis):**
  `docker exec vis-bundle-web-1 composer bin-phpstan`
- **Easy Coding Standard (ECS) - Fix issues:**
  `docker exec vis-bundle-web-1 composer bin-ecs-fix`
- **Rector (Automated Refactoring):**
  `docker exec vis-bundle-web-1 composer bin-rector-process`

## Code Style & Comments
- **Minimal Commenting**: All comments `//` that are not strictly necessary for Code Quality (e.g., PHPStan types) must be removed.
- **No Unnecessary Explanations**: Code should be self-explanatory. DocBlocks that only repeat method names or trivial logic are forbidden.
- **Cleanup Command**: If comments have been added, they can be cleaned up using `composer bin-ecs-fix` (if configured) or manually.

## Project Structure Highlights
- `.developer/`: Additional development documentation.
- `.junie/`: AI-specific configuration and documentation.
- `src/Core`: Core services such as the `PluginService`.
- `src/Command`: CLI tools for project initialization and management.
- `src/Plugin`: Base classes and interfaces for the plugin system (`AbstractPlugin`).
- `src/Entity`: Doctrine entities for users, roles, and other persistent data.
- `src/Model`: UI models for sidebar, topbar, and other components.
- `src/Resources`: Contains skeletons for code generation, among other things.
- `src/DependencyInjection`: Configuration of bundle extensions and compiler passes.
- `tests/`: Comprehensive test suite for core and plugin functionalities.


