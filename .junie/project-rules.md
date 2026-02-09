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
-
- **Strict Rule:** `@codeCoverageIgnore` must never be used. All code paths must be tested.

### Code Quality & Static Analysis
- **PHPStan (Static Analysis):**
  `docker exec vis-bundle-web-1 composer bin-phpstan`
- **Easy Coding Standard (ECS) - Fix issues:**
  `docker exec vis-bundle-web-1 composer bin-ecs-fix`
- **Rector (Automated Refactoring):**
  `docker exec vis-bundle-web-1 composer bin-rector-process`

## Project Structure Highlights
- `.developer/`: Zusätzliche Entwicklungsdokumentation.
- `.junie/`: KI-spezifische Konfiguration und Dokumentation.
- `src/Core`: Kern-Services wie der `PluginService`.
- `src/Command`: CLI-Tools für die Projektinitialisierung und -verwaltung.
- `src/Plugin`: Basisklassen und Interfaces für das Plugin-System (`AbstractPlugin`).
- `src/Entity`: Doctrine Entities für Benutzer, Rollen und andere persistente Daten.
- `src/Model`: UI-Modelle für Sidebar, Topbar und andere Komponenten.
- `src/Resources`: Enthält unter anderem Skeletons für die Code-Generierung.
- `src/DependencyInjection`: Konfiguration der Bundle-Erweiterungen und Compiler-Pässe.
- `tests/`: Umfassende Test-Suite für Core- und Plugin-Funktionalitäten.


