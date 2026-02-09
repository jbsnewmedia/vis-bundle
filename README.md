# VisBundle

[![Packagist Version](https://img.shields.io/packagist/v/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dt/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![PHP Version Require](https://img.shields.io/packagist/php-v/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![License](https://img.shields.io/packagist/l/jbsnewmedia/vis-bundle)](https://packagist.org/packages/jbsnewmedia/vis-bundle)
[![Tests](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml/badge.svg)](https://github.com/jbsnewmedia/vis-bundle/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/jbsnewmedia/vis-bundle/branch/main/graph/badge.svg)](https://codecov.io/gh/jbsnewmedia/vis-bundle)

**VisBundle** is a comprehensive Symfony bundle designed as a complete admin interface. It integrates user and role management with dynamic sidebar and topbar components for creating robust administration panels.

## 🚀 Features

- **User & Role Management** (UUID-based) with CLI commands
- **Dynamic Sidebar/Topbar** components
- **Plugin Architecture** with Composer packages or JSON-based loading as a project
- **Localization Support** with session-based switching
- **Security Integration** with Symfony Authenticator
- **Twig Extensions** for enhanced template functionality
- **Multi-Tool Support** with tool-switching interface
- **Responsive Design** via AvalynX SimpleAdmin

---

## ⚙️ Requirements

- PHP 8.2 or higher
- Symfony Framework 7.4 or higher

---

## 📦 Installation

Use [Composer](https://getcomposer.org/) to install the bundle:

```bash
composer require jbsnewmedia/vis-bundle
```

---

## 🛠 Setup & Configuration

### 1. Project Initialization (Optional)

If you are starting a new project, you can use the project creation command to set up the basic structure, including kernel modifications and skeleton files:

```bash
php bin/console vis:project:create
```

### 2. Core Installation Setup

Run the setup command to create the essential controllers and configurations:

```bash
php bin/console vis:core:create
```

This command will:
- Create the MainController for tool management
- Create the SecurityController for authentication
- Optionally create the RegistrationController
- Create the LocaleController for session-based language switching
- Update the configuration files `security.yaml` and `vis.yaml`

### 3. Create First Admin User

```bash
# Create new user (UUID-based)
php bin/console vis:user:create

# Add roles to user
php bin/console vis:user:add-role

# Remove roles from user
php bin/console vis:user:remove-role
```

### 4. Plugin Management

You can create new plugins with the following command:

```bash
php bin/console vis:plugin:create
```

---

## 📋 Usage Examples

### Create a Tool

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

### Add Sidebar Navigation

```php
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarHeader;

// Add header section
$header = new SidebarHeader('dashboard', 'main_section', 'Main Navigation');
$this->vis->addSidebar($header);

// Add navigation item
$item = new SidebarItem('dashboard', 'users', 'Users', 'admin_users_list');
$item->setIcon('<i class="fa-solid fa-users fa-fw"></i>');
$item->setOrder(10);
$item->addRole('ROLE_ADMIN');
$this->vis->addSidebar($item);
```

### Add Topbar Elements

```php
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButton;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdown;

// Custom button
$button = new TopbarButton('dashboard', 'custom_action');
$button->setClass('btn btn-primary');
$button->setContent('<i class="fa-solid fa-plus fa-fw"></i> Add New');
$button->setOnClick('showModal()');
$this->vis->addTopbar($button);

// Dropdown menu
$dropdown = new TopbarDropdown('dashboard', 'reports_menu');
$dropdown->setLabel('Reports');
$dropdown->setData([
    'monthly' => [
        'route' => 'reports_monthly',
        'routeParameters' => [],
        'icon' => '<i class="fa-solid fa-chart-bar fa-fw"></i>',
        'label' => 'Monthly Report'
    ]
]);
$this->vis->addTopbar($dropdown);
```

### Plugin Development

```php
use JBSNewMedia\VisBundle\Plugin\AbstractPlugin;

#[AsTaggedItem('vis.plugin')]
class CustomPlugin extends AbstractPlugin
{
    public function init(): void
    {
        // Plugin initialization logic
    }
    
    public function setNavigation(): void
    {
        $item = new SidebarItem('tools', 'custom_feature', 'Custom Feature');
        $item->setRoute('custom_feature_index');
        $this->vis->addSidebar($item);
    }
    
    public function setTopBar(): void
    {
        // Add custom topbar elements
    }
}
```

---

## 🎨 Template Integration

### Basic Template Usage

```twig
{% extends '@Vis/tool/base.html.twig' %}

{% block vis_container %}
    <div class="container-fluid p-4">
        <h1>Your Admin Content</h1>
        <!-- Content of your admin interface -->
    </div>
{% endblock %}
```

### Custom Sidebar Templates

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

## 📁 Architecture Overview

### Core Components

```
src/
├── Command/          # CLI commands for project/user/plugin management
├── Controller/       # Abstract controllers & core controllers
├── Entity/           # User, Tenant, Tool (UUID-based)
├── Model/            # Sidebar, Topbar, Tool models
├── Plugin/           # Plugin interface, lifecycle & loader
├── Security/         # Symfony authentication & locale handling
├── Service/          # Core Vis service & plugin manager
├── Twig/             # Extensions for dynamic filtering & translation
└── Trait/            # Reusable traits (roles, timestamps, etc.)
```

### Model Hierarchy

- **Tool**: Base container for admin areas
- **Sidebar**: Navigation components (header, item, with nesting)
- **Topbar**: Header components (button, dropdown, LiveSearch)
- **Plugin**: Modular extensions via service locator

---

## 🔧 Advanced Configuration

### Security Configuration (automatically generated)

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

### Asset Management Integration

```twig
{# With AssetComposerBundle integration #}
{% do addAssetComposer('avalynx/avalynx-simpleadmin/src/css/avalynx-simpleadmin.css') %}
{% do addAssetComposer('avalynx/avalynx-simpleadmin/src/js/avalynx-simpleadmin.js') %}
```

---

## 🧪 Developer Tools

The following commands are available for development:

```bash
# Install tools
composer bin-ecs-install
composer bin-phpstan-install
composer bin-phpunit-install
composer bin-rector-install

# Code quality checks
composer bin-ecs           # PHP-CS-Fixer check
composer bin-phpstan       # Static analysis
composer bin-rector        # Code transformation (dry-run)
composer test              # PHPUnit tests (without coverage)

# Automatic fixes
composer bin-ecs-fix       # Fix coding standards
composer bin-rector-process # Apply code transformations

# CI pipelines
composer ci                # Run all checks
composer ci-fix            # Run all checks and apply fixes
```

---

## 📜 License

This bundle is licensed under the MIT License. For more details, see the [LICENSE](LICENSE) file.

Developed by Jürgen Schwind and other contributors.

---

## 🤝 Contributing

Contributions are welcome! If you would like to contribute, please contact us or create a fork of the repository and submit a pull request with your changes or improvements.

---

## 📫 Contact

If you have questions, feature requests, or issues, please open an issue in our [GitHub repository](https://github.com/jbsnewmedia/vis-bundle) or submit a pull request.

---

*Enterprise-ready admin interface. Modular. Extensible. Security-first.*
