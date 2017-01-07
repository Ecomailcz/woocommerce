<?php

namespace Ecomail;

use EcomailPlugin;

class Helper
{

    /**
     * @var EcomailPlugin
     */
    protected $plugin;

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    public function getConfigValue($key)
    {
        return $this->plugin->getPluginOption($key);
    }

    public function getAPI()
    {

        $obj = new API();
        $obj->setAPIKey(
            $this->getConfigValue('api_key')
        );

        return $obj;
    }

    public function getCookieNameTrackStructEvent()
    {
        return 'Ecomail';
    }

}