# VisBundle

**VisBundle** is a comprehensive Symfony bundle designed as a complete admin interface, integrating user and role management with dynamic sidebar and topbar components for building robust administration panels.

## 🚀 Features

- **User & Role Management** with CLI commands
- **Dynamic Sidebar/Topbar** components
- **Plugin Architecture** for modular extensions
- **Security Integration** with Symfony Authenticator
- **Twig Extensions** for enhanced template functionality
- **Multi-Tool Support** with tool-switching interface
- **Responsive Design** via AvalynX SimpleAdmin

---

## ⚙️ Requirements

- PHP 8.1 or higher
- Symfony Framework 6.4 or 7.0

---

## 📦 Installation

Use [Composer](https://getcomposer.org/) to install the bundle:

```bash
composer require jbsnewmedia/vis-bundle
```

---

## 🛠 Setup & Configuration

### 1. Core Installation Setup

Run the setup command to create essential controllers and configuration:

```bash
php bin/console vis:core:create
```

This command will:
- Create MainController for tool management
- Create SecurityController for authentication
- Optionally create RegistrationController
- Update security.yaml configuration

### 2. Import Services Configuration

```yaml
# config/services.yaml
imports:
    - { resource: '@VisBundle/config/services.yaml' }
```

### 3. Create Your First Admin User

```bash
# Create a new user
php bin/console vis:user:create

# Add roles to user
php bin/console vis:user:add-role

# Remove roles from user
php bin/console vis:user:remove-role
```

---

## 📋 Usage Examples

### Creating a Tool

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

### Adding Sidebar Navigation

```php
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarHeader;

// Add header section
$header = new SidebarHeader('dashboard', 'main_section', 'Main Navigation');
$this->vis->addSidebar($header);

// Add navigation item
$item = new SidebarItem('dashboard', 'users', 'Users', 'admin_users_list');
$item->setIcon('<i class="fa-solid fa-users"></i>');
$item->setOrder(10);
$item->addRole('ROLE_ADMIN');
$this->vis->addSidebar($item);
```

### Adding Topbar Elements

```php
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButton;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdown;

// Custom button
$button = new TopbarButton('dashboard', 'custom_action');
$button->setClass('btn btn-primary');
$button->setContent('<i class="fa-solid fa-plus"></i> Add New');
$button->setOnClick('showModal()');
$this->vis->addTopbar($button);

// Dropdown menu
$dropdown = new TopbarDropdown('dashboard', 'reports_menu');
$dropdown->setLabel('Reports');
$dropdown->setData([
    'monthly' => [
        'route' => 'reports_monthly',
        'routeParameters' => [],
        'icon' => '<i class="fa-solid fa-chart-bar"></i>',
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

### Base Template Usage

```twig
{% extends '@Vis/tool/base.html.twig' %}

{% block vis_container %}
    <div class="container-fluid p-4">
        <h1>Your Admin Content</h1>
        <!-- Your admin interface content -->
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
├── Command/          # CLI Commands for User/Role Management
├── Controller/       # Abstract Controller Base
├── Entity/           # User, Client, Tool Entities
├── Model/            # Sidebar, Topbar, Tool Models
├── Plugin/           # Plugin Interface & Base Classes
├── Security/         # Symfony Authentication Integration
├── Service/          # Core Vis Service & Plugin Manager
├── Twig/             # Extensions for Dynamic Filtering & Translation
└── Trait/            # Reusable Traits (Roles, etc.)
```

### Model Hierarchy

- **Tool**: Base container for admin areas
- **Sidebar**: Navigation components (Header, Item, with nesting)
- **Topbar**: Header components (Button, Dropdown, LiveSearch)
- **Plugin**: Modular extensions via Service Locator

---

## 🔧 Advanced Configuration

### Security Configuration (Auto-generated)

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
{# With AssetComposerBundle Integration #}
{% do addAssetComposer('avalynx/avalynx-simpleadmin/src/css/avalynx-simpleadmin.css') %}
{% do addAssetComposer('avalynx/avalynx-simpleadmin/src/js/avalynx-simpleadmin.js') %}
```

---

## 🧪 Development Tools

All development tools use the same toolkit as Asset-Composer-Bundle:

```bash
# Install development dependencies
composer bin-ecs-install
composer bin-phpstan-install
composer bin-rector-install

# Code quality checks
composer bin-ecs          # PHP-CS-Fixer check
composer bin-phpstan       # Static analysis  
composer bin-rector        # Code transformation

# Fix code style issues
composer bin-ecs-fix       # Auto-fix coding standards
```

---

## 📜 License

This bundle is licensed under the MIT license. See the [LICENSE](LICENSE) file for more details.

Developed by Juergen Schwind and other contributors.

---

## 🤝 Contributing

Contributions are welcome! If you'd like to contribute, please fork the repository and submit a pull request with your changes or improvements. We're looking for contributions in the following areas:

- **Plugin development** for common admin use cases
- **UI/UX improvements** for better user experience
- **Performance optimizations** for large-scale applications
- **Documentation improvements** and usage examples
- **Test coverage** expansion
- **Accessibility enhancements** following WCAG guidelines

Before submitting your pull request, please ensure your changes are well-documented and follow the existing coding style of the project.

---

## 📫 Contact

If you have any questions, feature requests, or issues, please open an issue on our [GitHub repository](https://github.com/jbsnewmedia/vis-bundle) or submit a pull request.

---

*Enterprise-ready admin interface. Modular. Extensible. Security-first.*
