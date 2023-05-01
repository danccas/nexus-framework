<?php
namespace Core;

class HttpClient {
 // You can set the address when creating the Request object, or using the
  // setAddress() method.
  private $address;

  // Variables used for the request.
  public $userAgent = 'Mozilla/5.0 (compatible)';
  public $connectTimeout = 10;
  public $timeout = 15;

  // Variables used for cookie support.
  private $cookiesEnabled = FALSE;
  private $cookiePath;

  // Enable or disable SSL/TLS.
  private $ssl = FALSE;

  private $referer = null;
  private $headers = [];

  // Request type.
  private $requestType;
  // If the $requestType is POST, you can also add post fields.
  private $postFields;

  // Userpwd value used for basic HTTP authentication.
  private $userpwd;
  // Latency, in ms.
  private $latency;
  // HTTP response body.
  private $responseBody;
  // HTTP response header.
  private $responseHeader;
  // HTTP response status code.
  private $httpCode;
  // cURL error.
  private $error;

  /**
   * Called when the Request object is created.
   */
  public function __construct($address = null) {
    if(!empty($address)) {
        $this->address = $address;
    }
  }

  /**
   * Set the address for the request.
   *
   * @param string $address
   *   The URI or IP address to request.
   */
  public function setAddress($address) {
    $this->address = $address;
  }

  public function setHeader($key, $value) {
    $this->headers[$key] = $value;
  }
  public function setReferer($address) {
    $this->referer = $address;
  }
  public function setUserAgent($name) {
    $this->userAgent = $name;
  }
  /**
   * Set the username and password for HTTP basic authentication.
   *
   * @param string $username
   *   Username for basic authentication.
   * @param string $password
   *   Password for basic authentication.
   */
  public function setBasicAuthCredentials($username, $password) {
    $this->userpwd = $username . ':' . $password;
  }

  /**
   * Enable cookies.
   *
   * @param string $cookie_path
   *   Absolute path to a txt file where cookie information will be stored.
   */

  public function setCookieFile($file) {
    $this->cookiePath = $file;
  }
  public function enableCookies($cookie_path) {
    $this->cookiesEnabled = TRUE;
    $this->cookiePath = $cookie_path;
  }

  /**
   * Disable cookies.
   */
  public function disableCookies() {
    $this->cookiesEnabled = FALSE;
    $this->cookiePath = '';
  }

  /**
   * Enable SSL.
   */
  public function enableSSL() {
    $this->ssl = TRUE;
  }

  /**
   * Disable SSL.
   */
  public function disableSSL() {
    $this->ssl = FALSE;
  }

  /**
   * Set timeout.
   *
   * @param int $timeout
   *   Timeout value in seconds.
   */
  public function setTimeout($timeout = 15) {
    $this->timeout = $timeout;
  }

  /**
   * Get timeout.
   *
   * @return int
   *   Timeout value in seconds.
   */
  public function getTimeout() {
    return $this->timeout;
  }

  /**
   * Set connect timeout.
   *
   * @param int $connect_timeout
   *   Timeout value in seconds.
   */
  public function setConnectTimeout($connectTimeout = 10) {
    $this->connectTimeout = $connectTimeout;
  }

  /**
   * Get connect timeout.
   *
   * @return int
   *   Timeout value in seconds.
   */
  public function getConnectTimeout() {
    return $this->connectTimeout;
  }

  /**
   * Set a request type (by default, cURL will send a GET request).
   *
   * @param string $type
   *   GET, POST, DELETE, PUT, etc. Any standard request type will work.
   */
  public function setRequestType($type) {
    $this->requestType = $type;
    return $this;
  }

  /**
   * Set the POST fields (only used if $this->requestType is 'POST').
   *
   * @param array $fields
   *   An array of fields that will be sent with the POST request.
   */
  public function setPostFields($fields = array()) {
    $this->postFields = $fields;
    return $this;
  }

  /**
   * Get the response body.
   *
   * @return string
   *   Response body.
   */
  public function getResponse() {
    return $this->responseBody;
  }

  /**
   * Get the response header.
   *
   * @return string
   *   Response header.
   */
  public function getHeader() {
    return $this->responseHeader;
  }

  /**
   * Get the HTTP status code for the response.
   *
   * @return int
   *   HTTP status code.
   *
   * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
   */
  public function getHttpCode() {
    return $this->httpCode;
  }

  /**
   * Get the latency (the total time spent waiting) for the response.
   *
   * @return int
   *   Latency, in milliseconds.
   */
  public function getLatency() {
    return $this->latency;
  }

  /**
   * Get any cURL errors generated during the execution of the request.
   *
   * @return string
   *   An error message, if any error was given. Otherwise, empty.
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Check for content in the HTTP response body.
   *
   * This method should not be called until after execute(), and will only check
   * for the content if the response code is 200 OK.
   *
   * @param string $content
   *   String for which the response will be checked.
   *
   * @return bool
   *   TRUE if $content was found in the response, FALSE otherwise.
   */
  public function checkResponseForContent($content = '') {
    if ($this->httpCode == 200 && !empty($this->responseBody)) {
      if (strpos($this->responseBody, $content) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check a given address with cURL.
   *
   * After this method is completed, the response body, headers, latency, etc.
   * will be populated, and can be accessed with the appropriate methods.
   */
  public function execute($url = null, $data = null) {
    // Set a default latency value.
    $latency = 0;

    // Set up cURL options.
    $ch = \curl_init();
    if(!empty($url)) {
        $this->address = $url;
    }

    curl_setopt($ch, CURLOPT_URL, $this->address);

    // If there are basic authentication credentials, use them.
    if (isset($this->userpwd)) {
      curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
    }
    // If cookies are enabled, use them.
    if ($this->cookiesEnabled) {
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
    }
    // Send a custom request if set (instead of standard GET).
    if (isset($this->requestType)) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->requestType);
      // If POST fields are given, and this is a POST request, add fields.
      if ($this->requestType == 'POST' && isset($this->postFields)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postFields);
      }
    }
    if(!empty($this->headers)) {
        $hs = [];
        foreach($this->headers as $key => $val) {
            $hs[] = $key . ': ' . $val;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hs);
    }
    // Don't print the response; return it from curl_exec().
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
    // Follow redirects (maximum of 5).
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

    if(!empty($this->referer)) {
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
    }
    // SSL support.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl);
    // Set a custom UA string so people can identify our requests.
    curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
    // Output the header in the response.
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);


    // Set the header, response, error and http code.
    $this->responseHeader = substr($response, 0, $header_size);
    $this->responseHeader = $this->read_headers_request($this->responseHeader);
    #echo ">>>>>>>>>>";print_r($this->responseHeader);echo "<<<<<<<";echo "\n\n\n\n\n\n";

    $this->responseBody = substr($response, $header_size);
    $this->error = $error;
    $this->httpCode = $http_code;
    // Convert the latency to ms.
    $this->latency = round($time * 1000);
    return $this->responseBody;
  }
    private function read_headers_request($headerContent) {
        $headers = array();

        $arrRequests = explode("\r\n\r\n", $headerContent);
        for ($index = 0; $index < count($arrRequests) -1; $index++) {
            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line)
            {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else
                {   
                    list ($key, $value) = explode(': ', $line);
                    $key = strtolower($key);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return end($headers);
    }
}