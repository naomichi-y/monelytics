<?php
class OAuthCredential {
  private $credential_type;
  private $params;
  private $service;
  public $access_token;

  public function __construct($credential_type, array $params = array())
  {
    switch ($credential_type) {
      case UserCredential::CREDENTIAL_TYPE_FACEBOOK:
        if (!isset($params['code'])) {
          throw new InvalidArgumentException('"code" parameter is required.');
        }

        $this->service = OAuth::consumer('Facebook');
        $token = $this->service->requestAccessToken($params['code']);
        $this->access_token = $token->getAccessToken();

        break;
    }

    $this->credential_type = $credential_type;
    $this->params = $params;
  }

  public function getProfile()
  {
    $data = false;

    switch ($this->credential_type) {
      case UserCredential::CREDENTIAL_TYPE_FACEBOOK:
        $result = json_decode($this->service->request('/me'), true);
        $data = array(
          'nickname' => $result['name'],
          'email' => $result['email']
        );

        break;
    }

    return $data;
  }
}