<?php
/* Actualizado 23/11/2020 */

function infobip_send($number, $text, &$info = null)
{
    $url = 'https://3v8k6n.api.infobip.com/sms/2/text/advanced';
    $header = array(
        'Authorization' => 'App 1f26d79e1c143657c6358ed8517b717f-99cf3922-dc82-44b0-a49b-1c39c253b701',
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
    );
    $data = array(
        'messages' => array(
            'from' => 'Sutran PITS 2',
            'destinations' => array(
                array(
                    'to'   => $number,
                ),
            ),
            'text' => $text,
            'flash' => true,
        ),
    );
    return Curly(CURLY_POST, $url, $header, json_encode($data), null, $info, array(
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ));
}
