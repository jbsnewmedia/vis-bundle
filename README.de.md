# VisBundle

[![Packagist Version](https://img.shields.io/packagist/v/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dt/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![PHP Version Require](https://img.shields.io/packagist/php-v/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.4-673ab7?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/packagist/l/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![Tests](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml)
[![PHP CS Fixer](https://img.shields.io/badge/php--cs--fixer-geprüft-brightgreen)](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/phpstan-analysiert-brightgreen)](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml)
[![Rector](https://img.shields.io/badge/rector-geprüft-brightgreen)](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/jbsnewmedia/vis-bundle/branch/main/graph/badge.svg)](https://codecov.io/gh/jbsnewmedia/vis-bundle)

**VisBundle** ist ein umfassendes Symfony-Bundle, das als vollständiges Admin-Interface konzipiert wurde. Es integriert Benutzer- und Rollenverwaltung mit dynamischen Sidebar- und Topbar-Komponenten zur Erstellung robuster Administrations-Panels.

## 🚀 Features

- **Benutzer- & Rollenverwaltung** (UUID-basiert) mit CLI-Befehlen
- **Dynamische Sidebar/Topbar** Komponenten
- **Plugin-Architektur** mit Composer Paketen oder JSON-basiertem laden als Projekt
- **Lokalisierungs-Unterstützung** mit Session-basiertem Umschalten
- **Sicherheits-Integration** mit Symfony Authenticator
- **Twig-Erweiterungen** für verbesserte Template-Funktionalität
- **Multi-Tool-Unterstützung** mit Tool-Switching-Interface
- **Responsive Design** via AvalynX SimpleAdmin

---

## ⚙️ Anforderungen

- PHP 8.2 oder höher
- Symfony Framework 7.4 oder höher

---

## 📦 Installation

Verwende [Composer](https://getcomposer.org/), um das Bundle zu installieren:

```bash
composer require jbsnewmedia/vis-bundle
```

---

## 🛠 Setup & Konfiguration

### 1. Projekt-Initialisierung (Optional)

Wenn Du ein neues Projekt startest, kannst Du den Befehl zur Projekterstellung verwenden, um die Grundstruktur einschließlich Kernel-Modifikationen und Skeleton-Dateien einzurichten:

```bash
php bin/console vis:project:create
```

### 2. Core-Installations-Setup

Führe den Setup-Befehl aus, um die wesentlichen Controller und Konfigurationen zu erstellen:

```bash
php bin/console vis:core:create
```

Dieser Befehl wird:
- Den MainController für die Tool-Verwaltung erstellen
- Den SecurityController für die Authentifizierung erstellen
- Optional den RegistrationController erstellen
- Den LocaleController für das Session-basierte Umschalten der Sprache erstellen
- Die Konfigurationsdateien `security.yaml` und `vis.yaml` aktualisieren

### 3. Ersten Admin-Benutzer erstellen

```bash
# Neuen Benutzer erstellen (UUID-basiert)
php bin/console vis:user:create

# Rollen zu Benutzer hinzufügen
php bin/console vis:user:add-role

# Rollen von Benutzer entfernen
php bin/console vis:user:remove-role
```

### 4. Plugin-Management

Du kannst neue Plugins mit dem folgenden Befehl erstellen:

```bash
php bin/console vis:plugin:create
```

---

## 📋 Anwendungsbeispiele

### Ein Tool erstellen

```php
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Service\Vis;

class YourController extends VisAbstractController
{
    public function __construct(private Vis $vis)
    {
        $tool = new Tool('dashboard');
        $tool->setTitle('Dashboard');
        $tool->addRole('ROLE_ADMIN');
        
        $this->vis->addTool($tool);
        $this->vis->setTool('dashboard');
    }
}
```

### Sidebar-Navigation hinzufügen

```php
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarHeader;

// Header-Sektion hinzufügen
$header = new SidebarHeader('dashboard', 'main_section', 'Hauptnavigation');
$this->vis->addSidebar($header);

// Navigationspunkt hinzufügen
$item = new SidebarItem('dashboard', 'users', 'Benutzer', 'admin_users_list');
$item->setIcon('<i class="fa-solid fa-users fa-fw"></i>');
$item->setOrder(10);
$item->addRole('ROLE_ADMIN');
$this->vis->addSidebar($item);
```

### Topbar-Elemente hinzufügen

```php
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButton;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdown;

// Benutzerdefinierter Button
$button = new TopbarButton('dashboard', 'custom_action');
$button->setClass('btn btn-primary');
$button->setContent('<i class="fa-solid fa-plus fa-fw"></i> Neu hinzufügen');
$button->setOnClick('showModal()');
$this->vis->addTopbar($button);

// Dropdown-Menü
$dropdown = new TopbarDropdown('dashboard', 'reports_menu');
$dropdown->setLabel('Berichte');
$dropdown->setData([
    'monthly' => [
        'route' => 'reports_monthly',
        'routeParameters' => [],
        'icon' => '<i class="fa-solid fa-chart-bar fa-fw"></i>',
        'label' => 'Monatlicher Bericht'
    ]
]);
$this->vis->addTopbar($dropdown);
```

### Plugin-Entwicklung

```php
use JBSNewMedia\VisBundle\Plugin\AbstractPlugin;

#[AsTaggedItem('vis.plugin')]
class CustomPlugin extends AbstractPlugin
{
    public function init(): void
    {
        // Plugin-Initialisierungslogik
    }
    
    public function setNavigation(): void
    {
        $item = new SidebarItem('tools', 'custom_feature', 'Eigene Funktion');
        $item->setRoute('custom_feature_index');
        $this->vis->addSidebar($item);
    }
    
    public function setTopBar(): void
    {
        // Benutzerdefinierte Topbar-Elemente hinzufügen
    }
}
```

---

## 🎨 Template-Integration

### Basis-Template-Verwendung

```twig
{% extends '@Vis/tool/base.html.twig' %}

{% block vis_container %}
    <div class="container-fluid p-4">
        <h1>Dein Admin-Inhalt</h1>
        <!-- Inhalt Deines Admin-Interfaces -->
    </div>
{% endblock %}
```

### Benutzerdefinierte Sidebar-Templates

```twig
{# templates/custom_sidebar_item.html.twig #}
<li class="avalynx-simpleadmin-sidenav-item custom-item">
    <h2 class="avalynx-simpleadmin-sidenav-header">
        <a href="{{ path(item.route) }}" class="avalynx-simpleadmin-sidenav-link">
            {{ item.icon|raw }}
            <span class="title">{{ item.label }}</span>
        </a>
    </h2>
</li>
```

---

## 📁 Architektur-Überblick

### Kern-Komponenten

```
src/
├── Command/          # CLI-Befehle für Projekt-/Benutzer-/Plugin-Management
├── Controller/       # Abstrakte Controller & Kern-Controller
├── Entity/           # Benutzer, Mandant, Tool (UUID-basiert)
├── Model/            # Sidebar-, Topbar-, Tool-Modelle
├── Plugin/           # Plugin-Interface, Lifecycle & Loader
├── Security/         # Symfony Authentifizierung & Locale-Handling
├── Service/          # Kern-Vis-Service & Plugin-Manager
├── Twig/             # Erweiterungen für dynamisches Filtern & Übersetzung
└── Trait/            # Wiederverwendbare Traits (Rollen, Zeitstempel, etc.)
```

### Modell-Hierarchie

- **Tool**: Basis-Container für Admin-Bereiche
- **Sidebar**: Navigations-Komponenten (Header, Item, mit Verschachtelung)
- **Topbar**: Header-Komponenten (Button, Dropdown, LiveSearch)
- **Plugin**: Modulare Erweiterungen via Service Locator

---

## 🔧 Fortgeschrittene Konfiguration

### Sicherheits-Konfiguration (automatisch generiert)

```yaml
# config/packages/security.yaml
security:
    providers:
        vis_user_provider:
            entity:
                class: JBSNewMedia\VisBundle\Entity\User
                property: email
                
    firewalls:
        vis:
            lazy: true
            provider: vis_user_provider
            custom_authenticator: JBSNewMedia\VisBundle\Security\VisAuthenticator
            logout:
                path: vis_logout
                target: vis
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800

    access_control:
        - { path: ^/vis/login, roles: PUBLIC_ACCESS }
        - { path: ^/vis/logout, roles: PUBLIC_ACCESS }
        - { path: ^/vis, roles: ROLE_USER }
```

### Asset-Management-Integration

```twig
{# Mit AssetComposerBundle-Integration #}
{% do addAssetComposer('avalynx/avalynx-simpleadmin/src/css/avalynx-simpleadmin.css') %}
{% do addAssetComposer('avalynx/avalynx-simpleadmin/src/js/avalynx-simpleadmin.js') %}
```

---

## 🧪 Entwickler-Werkzeuge

Die folgenden Befehle stehen für die Entwicklung zur Verfügung:

```bash
# Installation der Werkzeuge
composer bin-ecs-install
composer bin-phpstan-install
composer bin-phpunit-install
composer bin-rector-install

# Code-Qualitätsprüfungen
composer bin-ecs           # PHP-CS-Fixer Prüfung
composer bin-phpstan       # Statische Analyse
composer bin-rector        # Code-Transformation (Dry-run)
composer test              # PHPUnit Tests (ohne Coverage)

# Automatische Korrekturen
composer bin-ecs-fix       # Coding-Standards korrigieren
composer bin-rector-process # Code-Transformation anwenden

# CI-Pipelines
composer ci                # Alle Prüfungen ausführen
composer ci-fix            # Alle Prüfungen ausführen und Korrekturen anwenden
```

---

## 📜 Lizenz

Dieses Bundle ist unter der MIT-Lizenz lizenziert. Weitere Details findest Du in der Datei [LICENSE](LICENSE).

Entwickelt von Jürgen Schwind und weiteren Mitwirkenden.

---

## 🤝 Mitwirken

Beiträge sind willkommen! Wenn Du etwas beitragen möchtest, kontaktiere uns oder erstelle einen Fork des Repositories und sende einen Pull-Request mit Deinen Änderungen oder Verbesserungen.

---

## 📫 Kontakt

Wenn Du Fragen, Feature-Anfragen oder Probleme hast, eröffne bitte ein Issue in unserem [GitHub-Repository](https://github.com/jbsnewmedia/vis-bundle) oder sende einen Pull-Request.

---

*Enterprise-ready Admin-Interface. Modular. Erweiterbar. Security-first.*
