<?php

namespace kuaidi100;

/**
 * Logistics service implemented with Kuaidi100's api
 */
class Client
{
    public function request($url, $post_data)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $content = curl_exec($curl);

        curl_close($curl);

        return $content;
    }

}