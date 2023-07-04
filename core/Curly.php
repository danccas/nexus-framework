<?php

namespace Core;

class Curly
{
public static function http($tipo, &$url, $header = null, $data = null, $cookie = null, &$info = null, $curl = null, $use_tor = false) {
  $data = is_array($data) ? http_build_query($data) : $data;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36');
  curl_setopt($ch, CURLOPT_HEADER, 1);
//  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($tipo));

  if($tipo === CURLY_POST) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  } else {
    $url .= !empty($data) ? '?' . $data : '';
  }
	curl_setopt($ch, CURLOPT_URL, $url);
	if (!is_null($header)) {
		array_walk($header, function (&$n, $k) {
      $n = $k . ": " . $n;
    });
    $header = array_values($header);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  }
  if(!is_null($curl) && is_array($curl)) {
    curl_setopt_array($ch, $curl);
  }
  if(!is_null($cookie)) {
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
  }
  // Proxy Tor
  if(!empty($use_tor)) {
    curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:9050");
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
  }
  $response = curl_exec($ch);
  $info = curl_getinfo($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header   = substr($response, 0, $header_size);
  $response = substr($response, $header_size);
  //$info['header_response'] = http_parse_headers($header);
  //$info['header'] = curl_getinfo($ch, CURLINFO_HEADER_OUT);
  //$info['header'] .= "";;
  $url  = $info['url'];
  //curl_close($ch);
  return $response;
}
}
