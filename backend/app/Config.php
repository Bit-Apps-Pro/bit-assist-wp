<?php

// phpcs:disable Squiz.NamingConventions.ValidVariableName

namespace BitApps\Assist;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides App configurations.
 */
class Config
{
    public const SLUG = 'bit-assist';

    public const TITLE = ' Bit Assist';

    public const VAR_PREFIX = 'bit_assist_';

    public const VERSION = '1.8.4';

    public const DB_VERSION = '1.0.3';

    public const REQUIRED_PHP_VERSION = '7.4';

    public const REQUIRED_WP_VERSION = '5.1';

    public const API_VERSION = '1.0.0';

    public const APP_BASE = '../../index.php';

    public const DEV_URL = 'http://localhost:3000';

    /**
     * Provides configuration for plugin.
     *
     * @param string $type    Type of conf
     * @param string $default Default value
     *
     * @return null|array|string
     */
    public static function get($type, $default = null)
    {
        switch ($type) {
            case 'MAIN_FILE':
                return realpath(__DIR__ . DIRECTORY_SEPARATOR . self::APP_BASE);

            case 'BASENAME':
                return plugin_basename(trim(self::get('MAIN_FILE')));

            case 'BASEDIR':
                return plugin_dir_path(self::get('MAIN_FILE')) . 'backend';

            case 'BASEDIR_ROOT':
                return plugin_dir_path(self::get('MAIN_FILE'));

            case 'SITE_URL':
                $parsedUrl = wp_parse_url(get_admin_url());
                $siteUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $siteUrl .= empty($parsedUrl['port']) ? null : ':' . $parsedUrl['port'];

                return $siteUrl;

            case 'SITE_DOMAIN':
                $parsedUrl = wp_parse_url(get_admin_url());

                return $parsedUrl['host'];

            case 'ADMIN_URL':
                return str_replace(self::get('SITE_URL'), '', get_admin_url());

            case 'API_URL':
                global $wp_rewrite;

                return [
                    'base'      => get_rest_url() . self::SLUG . '/v1',
                    'separator' => $wp_rewrite->permalink_structure ? '?' : '&',
                ];

            case 'ROOT_URI':
                return set_url_scheme(plugins_url('', self::get('MAIN_FILE')), wp_parse_url(home_url())['scheme']);

            case 'ASSET_URI':
                return self::get('ROOT_URI') . '/assets';

            case 'ASSET_JS_URI':
                return self::get('ASSET_URI') . '/js';

            case 'ASSET_CSS_URI':
                return self::get('ASSET_URI') . '/css';

            case 'PLUGIN_PAGE_LINKS':
                return self::pluginPageLinks();

            case 'SIDE_BAR_MENU':
                return self::sideBarMenu();

            case 'UPLOAD_DIR':
                return wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . self::SLUG;

            case 'DB_PREFIX':
                global $wpdb;

                return $wpdb->prefix . self::VAR_PREFIX;

            default:
                return $default;
        }
    }

    /**
     * Prefixed variable name with prefix.
     *
     * @param string $option Variable name
     *
     * @return array
     */
    public static function withPrefix($option)
    {
        return self::VAR_PREFIX . $option;
    }

    /**
     * Prefixed table name with db prefix and var prefix.
     *
     * @param mixed $table
     *
     * @return string
     */
    public static function withDBPrefix($table)
    {
        global $wpdb;

        return $wpdb->prefix . self::VAR_PREFIX . $table;
    }

    /**
     * Retrieves options from option table.
     *
     * @param string $option  Option name
     * @param bool   $default default value
     * @param bool   $wp      Whether option is default wp option
     *
     * @return mixed
     */
    public static function getOption($option, $default = false, $wp = false)
    {
        if ($wp) {
            return get_option($option, $default);
        }

        return get_option(self::withPrefix($option), $default);
    }

    /**
     * Saves option to option table.
     *
     * @param string $option   Option name
     * @param bool   $autoload Whether option will autoload
     * @param mixed  $value
     *
     * @return bool
     */
    public static function addOption($option, $value, $autoload = false)
    {
        return add_option(self::withPrefix($option), $value, '', $autoload ? 'yes' : 'no');
    }

    /**
     * Save or update option to option table.
     *
     * @param string $option   Option name
     * @param mixed  $value    Option value
     * @param bool   $autoload Whether option will autoload
     *
     * @return bool
     */
    public static function updateOption($option, $value, $autoload = null)
    {
        return update_option(self::withPrefix($option), $value, !\is_null($autoload) ? 'yes' : null);
    }

    public static function isDev()
    {
        return defined('BITAPPS_DEV') && BITAPPS_DEV;
    }

    /**
     * Provides links for plugin pages. Those links will bi displayed in
     * all plugin pages under the plugin name.
     *
     * @return array
     */
    private static function pluginPageLinks()
    {
        return [];
    }

    /**
     * Provides menus for wordpress admin sidebar.
     * should return an array of menus with the following structure:
     * [
     *   'type' => menu | submenu,
     *  'name' => 'Name of menu will shown in sidebar',
     *  'capability' => 'capability required to access menu',
     *  'slug' => 'slug of menu after ?page=',.
     *
     *  'title' => 'page title will be shown in browser title if type is menu',
     *  'callback' => 'function to call when menu is clicked',
     *  'icon' =>   'icon to display in menu if menu type is menu',
     *  'position' => 'position of menu in sidebar if menu type is menu',
     *
     * 'parent' => 'parent slug if submenu'
     * ]
     *
     * @return array
     */
    private static function sideBarMenu()
    {
        return [
            'Home' => [
                'type'       => 'menu',
                'title'      => 'Bit Assist',
                'name'       => 'Bit Assist',
                'capability' => 'manage_options',
                'slug'       => self::SLUG,
                'callback'   => 'body',
                'icon'       => 'data:image/svg+xml;base64,' . base64_encode('<svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><title>bit assist-01-svg</title><style>.s0 { fill: #808285 }.s1 { fill: #ffffff }</style><path id="Layer" class="s0" d="m18.8 12.7c-0.2 1.7-0.7 3.2-2 4.4-0.9 0.9-2 1.3-3.2 1.6q-0.9 0.1-1.9 0.2-0.9 0.1-1.9 0.1-0.9 0-1.8-0.1-1-0.1-1.9-0.3c-2.7-0.6-4.3-2.4-4.8-5.2q0-0.3-0.1-0.7c-0.2-2-0.2-4.1 0.1-6.1 0.3-1.4 0.8-2.7 1.9-3.7 0.9-0.9 2.1-1.4 3.3-1.6 2.5-0.4 5-0.4 7.5 0.1 2.5 0.6 4.1 2.3 4.6 4.8 0.5 2.2 0.4 4.3 0.2 6.5z"/><path id="Layer" fill-rule="evenodd" class="s1" d="m9.9 19.7q-0.5 0-1 0-0.5 0-1-0.1-0.5 0-1-0.1-0.4-0.1-0.9-0.2-2.3-0.5-3.7-2-1.3-1.4-1.7-3.7-0.1-0.3-0.1-0.6 0-0.1 0-0.2c-0.3-2.3-0.3-4.4 0.1-6.4 0.3-1.7 1-3.1 2.1-4.1 1-0.9 2.1-1.4 3.6-1.7 2.8-0.5 5.4-0.4 7.8 0.1q2.2 0.5 3.5 1.9 1.3 1.3 1.7 3.5c0.5 2 0.5 4.2 0.2 6.7-0.2 1.5-0.6 3.4-2.2 4.9-0.9 0.8-2.1 1.4-3.6 1.7q-0.5 0.1-0.9 0.1-0.5 0.1-1 0.1-0.4 0.1-0.9 0.1-0.5 0-1 0zm-8-7.1q0.1 0.1 0.1 0.2 0 0.3 0.1 0.5c0.4 2.5 1.8 4 4.2 4.6q0.9 0.1 1.8 0.2 0.9 0.1 1.8 0.1 0.9 0.1 1.8 0 0.9-0.1 1.7-0.3 1.8-0.3 2.9-1.3c1-0.9 1.5-2.1 1.8-4 0.2-2.3 0.2-4.4-0.2-6.2-0.5-2.3-1.8-3.7-4.1-4.2-2.2-0.6-4.7-0.6-7.2-0.1-1.2 0.2-2.2 0.6-2.9 1.3-0.9 0.8-1.4 1.8-1.6 3.3-0.4 1.8-0.4 3.7-0.2 5.9z"/><path id="Layer" class="s1" d="m7.4 9.2q0.1 0 0.3 0.1 0.1 0 0.2 0.1 0.1 0.1 0.2 0.3 0.1 0.1 0.1 0.3 0 0.1-0.1 0.3-0.1 0.1-0.2 0.2-0.1 0.2-0.2 0.2-0.2 0.1-0.3 0.1-0.2 0-0.3-0.1-0.2 0-0.3-0.2-0.1-0.1-0.2-0.2 0-0.2 0-0.3 0-0.2 0-0.3 0.1-0.2 0.2-0.3 0.1-0.1 0.3-0.1 0.1-0.1 0.3-0.1z"/><path id="Layer" class="s1" d="m12.6 9.2q0.2 0 0.3 0.1 0.1 0 0.3 0.1 0.1 0.1 0.1 0.3 0.1 0.1 0.1 0.3 0 0.1-0.1 0.3 0 0.1-0.1 0.2-0.2 0.2-0.3 0.2-0.1 0.1-0.3 0.1-0.2 0-0.3-0.1-0.1 0-0.3-0.2-0.1-0.1-0.1-0.2-0.1-0.2-0.1-0.3 0-0.2 0.1-0.3 0-0.2 0.1-0.3 0.2-0.1 0.3-0.1 0.1-0.1 0.3-0.1z"/><path id="Layer" class="s1" d="m10.8 10q0 0 0 0.1 0 0 0 0 0 0.1 0 0.1 0 0.1-0.1 0.1 0 0.1 0 0.1-0.1 0.1-0.1 0.1-0.1 0.1-0.1 0.1-0.1 0.1-0.2 0.1 0 0-0.1 0 0 0 0 0.1-0.1 0-0.1 0 0 0-0.1 0 0 0-0.1 0 0 0 0 0-0.1-0.1-0.1-0.1-0.1 0-0.1 0 0 0-0.1 0 0 0 0-0.1-0.1 0-0.1 0 0 0 0-0.1-0.1 0-0.1 0 0 0 0-0.1-0.1 0-0.1 0 0-0.1 0-0.1 0 0 0-0.1-0.1 0-0.1-0.1 0 0 0 0 0-0.1 0-0.1z"/></svg>'),
                'position'   => '20',
            ],
            'All Widgets' => [
                'parent'     => self::SLUG,
                'type'       => 'submenu',
                'name'       => __('All Widgets', 'bit-assist'),
                'capability' => 'manage_options',
                'slug'       => self::SLUG . '#/',
            ],
        ];
    }
}
