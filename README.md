# VisBundle

A Symfony bundle designed as a comprehensive admin interface, integrating user and role management with dynamic sidebar and topbar components for building robust administration panels.

## Requirements

- PHP 8.0 or higher
- Symfony Framework Bundle 6.0 or higher

## Installation

To install VisBundle, you need to use [Composer](https://getcomposer.org/). Run the following command in your project root:

```bash
composer require jbsnewmedia/vis-bundle
```

## Usage

VisBundle streamlines the creation of admin interfaces within your Symfony project. It provides tools for managing users and roles, as well as customizable sidebars, topbars, and other interactive elements essential for effective administration panels.

### Commands

The bundle includes several CLI commands to simplify core admin tasks, such as creating users and managing roles:

- **Create User**: `php bin/console vis:user:create`
- **Add Role to User**: `php bin/console vis:user:add-role`
- **Remove Role from User**: `php bin/console vis:user:remove-role`
- **Create Core Item**: `php bin/console vis:core:create`

### Configuration

After installation, configure VisBundle services by importing its service configurations:

```yaml
# config/services.yaml
imports:
    - { resource: '@VisBundle/config/services.yaml' }
```

### Templates and Customization

VisBundle includes pre-defined Twig templates for essential admin interface components, including sidebars and topbars. These templates are easily extendable for further customization.

#### Base Layout Example

To start using VisBundle’s layout, extend from the provided base template:

```twig
{% extends '@Vis/base.html.twig' %}
```

#### Customizing Components

You can add or customize sidebar and topbar elements by including the respective template blocks:

```twig
{% block sidebar %}
    {% include '@Vis/sidebar/item.html.twig' %}
{% endblock %}

{% block topbar %}
    {% include '@Vis/topbar/dropdown.html.twig' %}
{% endblock %}
```

### Services

The `Vis` service manages core admin functionalities, while `PluginManager` handles plugin loading for dynamic, on-demand features within the admin panel.

### Autoloading

Ensure PSR-4 autoloading by running:

```bash
composer dump-autoload
```

## Translation

Predefined translations for English and German are located in the `translations` directory. Update or expand these as necessary.

## License

This bundle is licensed under the MIT license. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome, especially in:

- Bug fixes
- Feature enhancements
- Documentation improvements

To contribute, fork the repository, implement your changes, and submit a pull request!

## Contact

For questions, feature requests, or issues, please open an issue on our [GitHub repository](https://github.com/jbsnewmedia/vis-bundle).
