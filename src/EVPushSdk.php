<?php

namespace EVPushSdk;

class EVPushSdk{

	private $basePath;
	private $config;
	private $production;

	// The constructor, we set the api basepath here
	public function __construct($config = array(), $production = true){
		$this->config 		= $config;
		$this->production	= $production;
		$this->basePath 	= $production ? 'https://push.evect.net' : 'https://staging-push.evect.net';
	}

	// Message broadcast to all registered identifiers for the provided tokens
	public function messageBroadcast($payload){
		$path = $this->production ? '/message/*' : '/message/*/true';
		$response = $this->sendAuthenticatedCall($path,'POST',$payload);

		return $response;
	}

	public function messageUser($userIdentifier,$payload){
		$path = $this->production ? '/message/'.$userIdentifier : '/message/'.$userIdentifier.'/true';
		$response = $this->sendAuthenticatedCall($path,'POST',$payload);

		return $response;
	}

	public function messageUsers($userIdentifiers,$payload){
		$path = $this->production ? '/message/['.implode(',', $userIdentifiers).']' : '/message/['.implode(',', $userIdentifiers).']/true';
		$response = $this->sendAuthenticatedCall($path,'POST',$payload);

		return $response;
	}
	
	// Send an authenticated call
	private function sendAuthenticatedCall($path, $method = 'GET', $body = array(), $headers = array()){
		// We create an X-Auth header for authentication with the format
		// XAuth = '$public:$hash'
		// Where:
		// $public = public key
		// $hash = hash_hmac('sha256', $md5.$public, $secret);
		// $md5 = md5((string)$body)
		// $secret = secret for public key

		// Create authorization parameters
		$jsonBody 	= $method == 'GET' || $method == 'DELETE' ? '' : json_encode($body,true);
		$md5 		= md5($jsonBody);
		$public 	= isset($this->config['public']) ? $this->config['public'] : NULL;
		$secret 	= isset($this->config['secret']) ? $this->config['secret'] : NULL;
		$hash 		= hash_hmac('sha256', $md5.$public, $secret);

		// Create url for this request;
		$url 		= $this->basePath.$path;
		$headers	= array(
			"Accept: application/json",
			"Content-Type: application/json",
			"X-Auth: ".$public.":".$hash,
		);

		// Handle the request with curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 				$url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 	true); 
        curl_setopt($ch, CURLOPT_VERBOSE,	 		true);
        curl_setopt($ch, CURLOPT_HEADER,			true);

        // Add extra parameters for POST
        if ($method == 'POST') {
        	curl_setopt($ch, CURLOPT_POSTFIELDS, 	$jsonBody);
			curl_setopt($ch, CURLOPT_POST, 			true); 
        }

        // Add the headers
        curl_setopt($ch, CURLOPT_HTTPHEADER,		$headers);

        // Get the response
        $response = curl_exec($ch);

        // Get extra metadata
        $statusCode 	= (int)curl_getinfo($ch,		CURLINFO_HTTP_CODE);
        $header_size 	= curl_getinfo($ch, 			CURLINFO_HEADER_SIZE);
		$responseHeader = substr($response, 0, $header_size);
		$responseBody 	= substr($response, $header_size);

        // Finish
        curl_close($ch);

        // Split up the data in different kinds of information
        $responseData 					= array();
        $responseData['raw'] 			= $response;
        $responseData['statusCode'] 	= $statusCode;
        $responseData['responseHeader'] = $responseHeader;
        $responseData['responseBody']	= $responseBody;

        return $responseData;

	}
}
