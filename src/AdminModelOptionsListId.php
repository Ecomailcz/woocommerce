<?php

namespace Ecomail;

class AdminModelOptionsListId
{

    /**
     * @var Helper
     */
    protected $helper;

    public function setHelper($helper)
    {
        $this->helper = $helper;
    }

    public function getOptions()
    {

        $options = array();

        if ($this->helper->getConfigValue('api_key')) {
            $listsCollection = $this->helper->getAPI()
                ->getListsCollection();


            foreach ($listsCollection as $list) {
                $options[] = array(
                    'value' => $list->id,
                    'label' => $list->name
                );
            }
        }

        return $options;

    }

}