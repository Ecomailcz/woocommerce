<?php

namespace Ecomail;

class API
{

    protected $APIKey;

    public function setAPIKey($arg)
    {
        $this->APIKey = $arg;

        return $this;
    }

    public function getListsCollection()
    {

        return $this->call('lists');

    }

    public function subscribeToList($listId, $customerData)
    {

        return $this->call(
            sprintf(
                'lists/%d/subscribe',
                $listId
            ),
            'POST',
            array(
                'subscriber_data' => $customerData
            )
        );

    }

    public function createTransaction($data)
    {

        return $this->call(
            'tracker/transaction',
            'POST',
            $data
        );

    }

    protected function call($url, $method = 'GET', $data = null)
    {
        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            "http://api2.ecomailapp.cz/" . $url
        );
        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            TRUE
        );
        curl_setopt(
            $ch,
            CURLOPT_HEADER,
            FALSE
        );
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                'Key: ' . $this->APIKey
            )
        );

        if (in_array(
            $method,
            array(
                'POST',
                'PUT'
            )
        )) {

            curl_setopt(
                $ch,
                CURLOPT_CUSTOMREQUEST,
                $method
            );

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($data)
            );

        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

}