<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;
use JBSNewMedia\VisBundle\Entity\User;
use JBSNewMedia\VisBundle\Model\Setting\Setting;
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonDarkmode;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonSidebar;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownLocale;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownProfile;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchClients;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchTools;
use JBSNewMedia\VisBundle\Service\ClientMembershipManager;
use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getPluginId(): ?string
    {
        foreach ($this->getAttributes() as $instance) {
            if (!empty($instance->plugin)) {
                return $instance->plugin;
            }
        }

        return null;
    }

    public function getPriority(): int
    {
        foreach ($this->getAttributes() as $instance) {
            return $instance->priority;
        }

        return 100;
    }

    /**
     * @return VisPlugin[]
     */
    protected function getAttributes(): array
    {
        $reflection = new \ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(VisPlugin::class);
        $instances = [];

        foreach ($attributes as $attribute) {
            $instances[] = $attribute->newInstance();
        }

        return $instances;
    }

    public function createTool(): Tool
    {
        return new Tool((string) $this->getPluginId(), (int) $this->getPriority());
    }

    /**
     * Adds the client switcher for this plugin's tool. Only clients linked to
     * the tool via ClientToTool AND managed by the logged-in user are listed.
     * Adds nothing when the resulting list is empty (e.g. unknown user).
     */
    public function addClientSwitcher(
        Vis $vis,
        TranslatorInterface $translator,
        ClientMembershipManager $clientMembershipManager,
        ?Security $security = null,
    ): void {
        $user = $security?->getUser();
        $clientData = $user instanceof User
            ? $clientMembershipManager->filterManaged(
                $user,
                $clientMembershipManager->getClientsForTool((string) $this->getPluginId())
            )
            : [];

        $vis->setClients($clientData);

        if ([] === $clientData) {
            return;
        }

        $item = new TopbarLiveSearchClients((string) $this->getPluginId());
        $item->setLabel($translator->trans('main.livesearch.clients', domain: 'vis'));
        $item->setLabelSearch($translator->trans('main.livesearch.placeholder', domain: 'vis'));
        $item->setVis($vis);
        $vis->addTopbar($item);
    }

    /**
     * Adds the standard topbar items shared by all plugins:
     * tools livesearch, darkmode toggle, sidebar toggles, locale dropdown,
     * theme dropdown and profile dropdown.
     */
    public function addDefaultTopbar(
        Vis $vis,
        TranslatorInterface $translator,
        ?RequestStack $requestStack = null,
    ): void {
        $item = new TopbarLiveSearchTools((string) $this->getPluginId());
        $item->setLabel($translator->trans('main.livesearch.tools', domain: 'vis'));
        $item->setLabelSearch($translator->trans('main.livesearch.placeholder', domain: 'vis'));
        $item->setVis($vis);
        $vis->addTopbar($item);

        $item = new TopbarButtonDarkmode((string) $this->getPluginId());
        $item->setLabel($translator->trans('main.toggle.darkmode', domain: 'vis'));
        $vis->addTopbar($item);

        $item = new TopbarButtonSidebar((string) $this->getPluginId());
        $item->setLabel($translator->trans('main.toggle.sidebar', domain: 'vis'));
        $vis->addTopbar($item);

        $item = new TopbarButtonSidebar((string) $this->getPluginId(), 'toggle_sidebar_end', 'end', ['display' => 'large']);
        $item->setLabel($translator->trans('main.toggle.sidebar', domain: 'vis'));
        $vis->addTopbar($item);

        $locale = $vis->getTranslator()->getLocale();
        $item = new TopbarDropdownLocale((string) $this->getPluginId());
        $item->setLabel($translator->trans('main.locale', domain: 'vis'));
        $item->setContentFilter('raw');
        $item->setContent('<i class="fa-solid fa-globe fa-fw"></i>');
        $item->setDataKey($locale);
        $data = [];
        foreach ($vis->getLocales() as $l) {
            $data[$l] = [
                'route' => 'vis_api_locale',
                'routeparameters' => ['_locale' => $l, 'timestamp' => '__TIMESTAMP__'],
                'label' => $translator->trans('locale.'.$l, domain: 'vis'),
                'icon' => '',
            ];
        }
        $item->setData($data);
        $vis->addTopbar($item);

        $this->addThemeDropdown($vis);

        $item = new TopbarDropdownProfile((string) $this->getPluginId());
        $item->setLabel($translator->trans('main.profile.default', domain: 'vis'));
        $item->setDropdownIcon(false);

        $route = $requestStack?->getCurrentRequest()?->attributes->get('_route', '');
        $currentRoute = is_string($route) ? $route : '';
        $profileRoutes = ['vis_profile_settings', 'vis_profile_password', 'vis_profile_client', 'vis_profile_locale', 'vis_profile_theme'];
        if (in_array($currentRoute, $profileRoutes, true)) {
            $item->setDataKey('settings');
        }

        $item->setData([
            'settings' => [
                'route' => 'vis_profile_settings',
                'routeparameters' => [],
                'label' => $translator->trans('main.profile.settings', domain: 'vis'),
                'icon' => '<i class="fa-solid fa-gear fa-fw"></i>',
            ],
            'divider' => [
                'route' => '',
                'routeparameters' => [],
                'label' => '---',
            ],
            'logout' => [
                'route' => 'vis_logout',
                'routeparameters' => [],
                'label' => $translator->trans('main.profile.logout', domain: 'vis'),
                'icon' => '<i class="fa-solid fa-right-from-bracket fa-fw"></i>',
            ],
        ]);
        $vis->addTopbar($item);
    }

    /**
     * Adds the standard profile settings entries (password, client, locale,
     * theme). The client setting is only added when at least one client is
     * available for the current user.
     */
    public function addDefaultSettings(Vis $vis, TranslatorInterface $translator): void
    {
        $setting = new Setting((string) $this->getPluginId(), 'profile_password', $translator->trans('profile.password.title', domain: 'vis'));
        $setting->setRoute('vis_profile_password');
        $setting->generateIcon('fa-fw fa-solid fa-key');
        $setting->setDescription($translator->trans('profile.password.description', domain: 'vis'));
        $setting->setOrder(10);
        $vis->addSetting($setting);

        if ([] !== $vis->getClients()) {
            $setting = new Setting((string) $this->getPluginId(), 'profile_client', $translator->trans('profile.client.title', domain: 'vis'));
            $setting->setRoute('vis_profile_client');
            $setting->generateIcon('fa-fw fa-solid fa-building');
            $setting->setDescription($translator->trans('profile.client.description', domain: 'vis'));
            $setting->setOrder(20);
            $vis->addSetting($setting);
        }

        $setting = new Setting((string) $this->getPluginId(), 'profile_locale', $translator->trans('profile.locale.title', domain: 'vis'));
        $setting->setRoute('vis_profile_locale');
        $setting->generateIcon('fa-fw fa-solid fa-globe');
        $setting->setDescription($translator->trans('profile.locale.description', domain: 'vis'));
        $setting->setOrder(30);
        $vis->addSetting($setting);

        $setting = new Setting((string) $this->getPluginId(), 'profile_theme', $translator->trans('profile.theme.title', domain: 'vis'));
        $setting->setRoute('vis_profile_theme');
        $setting->generateIcon('fa-fw fa-solid fa-palette');
        $setting->setDescription($translator->trans('profile.theme.description', domain: 'vis'));
        $setting->setOrder(40);
        $vis->addSetting($setting);
    }

    private function addThemeDropdown(Vis $vis): void
    {
        $currentTheme = $vis->getTheme();

        $data = [
            'default' => [
                'route' => 'vis_api_theme',
                'routeparameters' => ['slug' => 'default', 'timestamp' => '__TIMESTAMP__'],
                'label' => 'VIS',
                'icon' => '',
            ],
        ];

        foreach ($vis->getThemes() as $theme) {
            if ('vis' === $theme['id']) {
                continue;
            }

            $data[$theme['id']] = [
                'route' => 'vis_api_theme',
                'routeparameters' => ['slug' => $theme['id'], 'timestamp' => '__TIMESTAMP__'],
                'label' => $theme['name'],
                'icon' => '',
            ];
        }

        $item = new TopbarDropdownLocale((string) $this->getPluginId(), 'theme');
        $item->setLabel('Theme');
        $item->setContentFilter('raw');
        $item->setContent('<i class="fa-solid fa-palette fa-fw"></i>');
        $item->setDataKey('vis' === $currentTheme ? 'default' : $currentTheme);
        $item->setData($data);
        $vis->addTopbar($item);
    }

    public function init(): void
    {
    }

    public function setTopBar(): void
    {
    }

    public function setNavigation(): void
    {
    }
}
