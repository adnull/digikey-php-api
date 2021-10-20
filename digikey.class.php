<?php

class DigiKey {
	function __construct( $digikey_client_id, $digikey_secret, $api_endpoint, $token_file, $app_url) {

		$this->endpoint = $api_endpoint;
		$this->client_id = $digikey_client_id;
		$this->secret = $digikey_secret;
		$this->token_file = $token_file;
		$this->app_url = $app_url;

		$this->last_refresh = 0;
		$this->access_token = false;
		$this->access_token_expires = 0;
		$this->refresh_token = false;
		$this->refresh_token_expires = 0;

		$this->load_tokens();

	}

	private function load_tokens() {
		if($this->token_file && file_exists($this->token_file)) {
			$tokens = json_decode(file_get_contents($this->token_file), true);
			if($tokens && isset($tokens['access_token']) && isset($tokens['refresh_token'])) {
				$this->last_refresh = $tokens['last_refresh'];
				$this->access_token = $tokens['access_token'][0];
				$this->access_token_expires = $tokens['access_token'][1];
				$this->refresh_token = $tokens['refresh_token'][0];
				$this->refresh_token_expires = $tokens['refresh_token'][1];
				return true;
			}
		}
		return false;
	}

	public function get_authorization_url() {
		return $this->endpoint."/v1/oauth2/authorize?response_type=code&client_id=".$this->client_id."&redirect_uri=".urlencode($this->app_url);
	}

	public function authorize($code) {
		$r_data = array(
				'code' => $code,
				'client_id' => $this->client_id,
				'client_secret' => $this->secret,
				'redirect_uri' => $this->app_url,
				'grant_type' => 'authorization_code'
		);
		$response = $this->http_request($this->endpoint."/v1/oauth2/token", false, $r_data);
		if($response) {
			$res_data = json_decode($response, true);
			return $this->update_tokens($res_data);
		}
		return false;
	}

	private function update_tokens($res_data) {
		if(isset($res_data['access_token']) && isset($res_data['refresh_token'])) {
			$this->last_refresh = time();
			$this->access_token = $res_data['access_token'];
			$this->access_token_expires = $this->last_refresh + $res_data['expires_in'];
			$this->refresh_token = $res_data['refresh_token'];
			$this->refresh_token_expires = $this->last_refresh + $res_data['refresh_token_expires_in'];

			$tokendata = array(
				'last_refresh' => $this->last_refresh,
				'access_token' => array($this->access_token, $this->access_token_expires),
				'refresh_token' => array($this->refresh_token, $this->refresh_token_expires)
			);
			file_put_contents($this->token_file, json_encode($tokendata));
			return true;
		} else {
			return false;
		}
	}

	private function check_token_expired() {
		return $this->access_token && $this->access_token_expires < time();
	}

	private function check_refresh_token_expired() {
		return $this->refresh_token && $this->refresh_token_expires > time();
	}

	private function refresh_token() {
		$r_data = array(
				'client_id' => $this->client_id,
				'client_secret' => $this->secret,
				'refresh_token' => $this->refresh_token,
				'grant_type' => 'refresh_token'
		);
		$response = $this->http_request($this->endpoint."/v1/oauth2/token", false, $r_data);
		if($response) {
			$res_data = json_decode($response, true);
			return $this->update_tokens($res_data);
		}
		return false;
	}

	private function http_request($url, $headers=false, $postdata = false) {
		$curl = curl_init();
//		$fp = fopen('log','w+');
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
//		curl_setopt($curl, CURLOPT_HTTPHEADER, array('User-agent: app'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($curl, CURLOPT_VERBOSE, true);
//		curl_setopt($curl, CURLOPT_STDERR, $fp);
		if($headers && is_array($headers) && count($headers) > 0) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		if($postdata) {
			if(is_array($postdata)) {
				$pdata = array();
				foreach($postdata as $k=>$v)	$pdata[]=urlencode($k)."=".urlencode($v);
				$pdata=implode("&", $pdata);
			} else {
				$pdata = $postdata;
			}
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $pdata);
		}
		$result = curl_exec($curl);
		if(curl_errno($curl) >= 400) {
			print $result;
			return false;
		}
		return $result;
	}

	public function api_request($endpoint, $request=false) {
		if($this->access_token && $this->check_token_expired()) {
			$this->refresh_token();
		}
		$headers = array(
			'Content-type: application/json',
			'X-DIGIKEY-Client-Id: '.$this->client_id,
			'Authorization: Bearer '.$this->access_token,
			'X-DIGIKEY-Locale-Site: US',
			'X-DIGIKEY-Locale-Language: en',
			'X-DIGIKEY-Locale-Currency: USD',
			'X-DIGIKEY-Locale-ShipToCountry: us',
		);
		print $this->endpoint.$endpoint;
		$response = $this->http_request($this->endpoint.$endpoint, $headers, $request);
		if($response) {
			$res_data = json_decode($response,true);
			return $res_data;
		}
		return false;
	}

}

?>
