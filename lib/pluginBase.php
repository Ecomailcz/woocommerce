<?php
/**
 * Author: Jan Cinert
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

abstract class LNCPluginBaseV1
{

    const PLUGIN_ID = '';
    const PLUGIN_NAME = '';

    protected $pluginKey;
    protected $pluginSettingsKey;

    public function __construct()
    {

        $this->pluginKey = str_replace(
            ' ',
            '',
            ucwords(
                str_replace(
                    '-',
                    ' ',
                    static::PLUGIN_ID
                )
            )
        );

        $this->pluginSettingsKey = $this->pluginKey . 'Settings';


        $this->includes();

        add_action(
            'plugins_loaded',
            array(
                $this,
                'hookPluginsLoaded'
            )
        );

        add_filter(
            'rwmb_meta_boxes',
            array(
                $this,
                'hookMetaBoxes'
            ),
            100,
            1
        );

        add_action(
            'admin_init',
            array(
                $this,
                'hookAdminInit'
            ),
            100
        );

        add_action(
            'admin_menu',
            array(
                $this,
                'hookAdminMenu'
            ),
            100
        );

        add_action(
            'admin_enqueue_scripts',
            array(
                $this,
                'hookAdminEnqueueScripts'
            )
        );


    }

    public function hookPluginsLoaded()
    {

        load_plugin_textdomain(
            static::PLUGIN_ID,
            false,
            static::PLUGIN_ID . '/languages'
        );

    }

    public function hookAdminInit()
    {

        register_setting(
            $this->pluginSettingsKey,
            $this->pluginSettingsKey
        );

    }

    public function hookAdminMenu()
    {

    }

    public function hookAdminEnqueueScripts()
    {

        $path = '/' . static::PLUGIN_ID . '/css/admin.css';
        if (file_exists(WP_PLUGIN_DIR . $path)) {
            $k = sprintf(
                '%s_admin_css',
                static::PLUGIN_ID
            );
            wp_register_style(
                $k,
                plugins_url(
                    $path
                ),
                false,
                '1.0.0'
            );
            wp_enqueue_style($k);
        }

        $path = '/' . static::PLUGIN_ID . '/js/admin.js';
        if (file_exists(WP_PLUGIN_DIR . $path)) {
            $k = sprintf(
                '%s_admin_js',
                static::PLUGIN_ID
            );
            wp_register_script(
                $k,
                plugins_url(
                    $path
                ),
                array('jquery'),
                '1'
            );
            wp_enqueue_script($k);
        }
    }

    public function hookMetaBoxes($meta_boxes)
    {

        $result = array();
        foreach ($meta_boxes as $k => $meta_box) {

            $result[] = $meta_box;
        }

        return $result;
    }

    public function includes()
    {

    }

    public function getPluginOption($k)
    {
        $options = get_option($this->pluginSettingsKey);
        $options = array_merge(
            $this->getDefaultOptions(),
            is_array($options)
                ? $options
                : array()
        );

        return $options[$k];
    }

    public function field_callback($arguments)
    {
        $value = $this->getPluginOption($arguments['uid']);

        switch ($arguments['type']) {
            case 'text':
                printf('<input style="width: 100%%" name="%1$s[%2$s]" id="%1$s_%2$s" type="%3$s" placeholder="%4$s" value="%5$s" />', esc_attr($this->pluginSettingsKey), esc_attr($arguments['uid']), esc_attr($arguments['type']), esc_attr($arguments['placeholder']), esc_attr($value));
                break;
            case 'textarea':
                printf('<textarea style="width: 100%%" name="%1$s[%2$s]" id="%1$s_%2$s" placeholder="%3$s" rows="5" cols="50">%4$s</textarea>', esc_attr($this->pluginSettingsKey), esc_attr($arguments['uid']), esc_attr($arguments['placeholder']), esc_attr($value));
                break;
            case 'select':
                if (is_array($arguments['options'])) {
                    $options_markup = '';
                    foreach ($arguments['options'] as $key => $label) {
                        $options_markup .= sprintf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_attr($label));
                    }
                    printf('<select style="width: 100%%" name="%1$s[%2$s]" id="%1$s_%2$s" >%3$s</select>', esc_attr($this->pluginSettingsKey), esc_attr($arguments['uid']), $options_markup);
                }
                break;
        }

        if ($helper = $arguments['helper']) {
            printf('<span class="helper"> %s</span>', $helper);
        }

        if ($supplimental = $arguments['supplemental']) {
            printf('<p class="description">%s</p>', $supplimental);
        }
    }

    protected function getDefaultOptions()
    {
        return array();
    }

    protected function getBasePath()
    {

        $server = get_site_url();

        return rtrim(
            parse_url(
                $server,
                PHP_URL_PATH
            ),
            '/'
        );

    }

}
