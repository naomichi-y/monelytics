<?php
namespace App\Libraries;

use InvalidArgumentException;

use Artdarek\OAuth\OAuth;

use App\Models;

class OAuthCredential {
  private $credential_type;
  private $params;
  private $service;
  public $access_token;

  public function __construct($credential_type, array $params = array())
  {
    $this->credential_type = $credential_type;
    $this->params = $params;
  }

  public function build()
  {
    switch ($this->credential_type) {
      case Models\UserCredential::CREDENTIAL_TYPE_FACEBOOK:
        if (!isset($this->params['code'])) {
          throw new InvalidArgumentException('"code" parameter is required.');
        }

        $oauth = new OAuth();
        $this->service = $oauth->consumer('Facebook');
        $token = $this->service->requestAccessToken($this->params['code']);
        $this->access_token = $token->getAccessToken();

        break;
    }
  }

  public function getProfile()
  {
    $data = false;

    switch ($this->credential_type) {
      case Models\UserCredential::CREDENTIAL_TYPE_FACEBOOK:
        $result = json_decode($this->service->request('/me'), true);
        $data = array(
          'id' => $result['id'],
          'nickname' => $result['name'],
          'email' => $result['email']
        );

        break;
    }

    return $data;
  }
}
