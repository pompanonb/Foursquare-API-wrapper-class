<?php

/** 
 * Foursquare API wrapper class
 * 
 * This class gives you the ability to communicate between PHP and foursquare.
 * It features:
 * 
 * * authentication
 * * mayorships
 * * leaderboard
 * * list of a user's friends
 * * and much more
 * 
 * The class is easy to use and understand. Everything is well documented inline. Together with the example
 * in the Github readme file you should be ok. 
 * 
 * Questions and bugs can be sended through email.
 * 
 * @package foursquare
 * @license MIT <http://opensource.org/licenses/MIT>
 * @version 1.0.0
 * @author John Poelman <john@bloobz.be>
 * 
 * @todo add functions that POST to the API.
 */
class foursquare 
{	
	// current class version
	const VERSION = '1.0.0';
	
	// define the constants
	const API_URL = 'https://api.foursquare.com/v2/';
	const OAUTH_URL = 'https://foursquare.com/oauth2/authenticate';
	const TOKEN_URL = 'https://foursquare.com/oauth2/access_token';
	const API_VERSION = '20130224';
	
	/*
	 * Access token.
	 *
	 * @var string
	 */
	private $access_token;
	
	/*
	 * Curl instance.
	 *
	 * @var resource
	 */
	private $curl;
	
	/*
	 * ClientId.
	 * 
	 * @var string
	 */
	private $clientId;
	
	/*
	 * ClientSecret.
	 *
	 * @var string
	 */
	private $clientSecret;
	
	/*
	 * OAuth token.
	 *
	 * @var string
	 */
	private $oauthToken;
	
	/*
	 * RedirectUrl.
	 *
	 * @var string
	 */
	private $redirectUrl;
	
	/*
	 * The parameters
	 * 
	 * @var array
	 */
	private  $parameters;
	
	// class methods
	
	/**
	 * Default constructor method.
	 * 
	 * @param string $id Your client ID
	 * @param string $secret Your client secret
	 * @param string $url Your redirect URL
	 * @since 1.0.0
	 */
	public function __construct($id, $secret, $url)
	{
		// store known values
		$this->setClientId($id);
		$this->setClientSecret($secret);
		$this->setRedirectUrl($url);
		
		// init parameters array
		$this->parameters = array();
	}
	
	/**
	 * Default destructor method.
	 * 
	 * @since 1.0.0
	 */
	public function __destruct()
	{
		if($this->curl != null) curl_close($this->curl);
	}
	
	/**
	 * This function gets and stores the access token in to the register.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function authenticate()
	{
		if($this->oauthToken == null)
		{
			// redirect for authorisation
			if(!$_GET['code'])
			{
				header('location:' . self::OAUTH_URL . '?client_id=' . self::getClientId() . '&response_type=code&redirect_uri=' . self::getRedirectUrl());
			}
		
			// request an access token if we have received a code parameter in the URL
			else
			{
				// build parameters
				$this->parameters['client_id'] = $this->getClientId();
				$this->parameters['client_secret'] = $this->getClientSecret();
				$this->parameters['grant_type'] = 'authorization_code';
				$this->parameters['redirect_uri'] = $this->getRedirectUrl();
				$this->parameters['code'] = $_GET['code'];
			
				// build complete url
				$url = self::TOKEN_URL . '?' . $this->buildQuery($this->parameters);
			
				// cURL request
				$response = $this->doCurl($url);
				
				// return the access code
				return (string)$response->access_token;
			}
		}
	}

	/**
	 * Format the parameters as a querystring.
	 * 
	 * @since 1.0.0
	 * @param  array  $parameters The parameters to pass.
	 * @return string
	 * 
	 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
	 * @license BSD license
	 */
	private function buildQuery(array $parameters)
	{
		// no parameters?
		if(empty($parameters)) return '';
		
		// encode the keys
		$keys = self::urlencode_rfc3986(array_keys($parameters));
	
		// encode the values
		$values = self::urlencode_rfc3986(array_values($parameters));
	
		// reset the parameters
		$parameters = array_combine($keys, $values);
	
		// sort parameters by key
		uksort($parameters, 'strcmp');
	
		// loop parameters
		foreach ($parameters as $key => $value) {
			// sort by value
			if(is_array($value)) $parameters[$key] = natsort($value);
		}
	
		// process parameters
		foreach ($parameters as $key => $value) {
			$chunks[] = $key . '=' . str_replace('%25', '%', $value);
		}
	
		// return
		return implode('&', $chunks);
	}
	
	
	/**
	 * This function makes a curl call.
	 * 
	 * @since 1.0.0
	 * @param string $url The url to fetch
	 * @param string $method [optional] Use GET or POST. At this point only GET is supported.
	 * @return mixed
	 * 
	 * @todo add the ability to use POST.
	 */
	private function doCurl($url, $method = 'GET')
	{
		// set cURL options
		$options = array();
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_HTTPGET] = true;
		
		// initiate cURL
		$this->curl = curl_init($url);
		
		// add all options
		curl_setopt_array($this->curl, $options);
		
		// execute
		$response = curl_exec($this->curl);
		
		//did we get any errors?
		$errorNmb = curl_errno($this->curl);
		$errorMsg = curl_error($this->curl);
		
		// error exception
		if($errorNmb != 0) throw new Exception($errorMsg, $errorNmb);
		
		// return
		return json_decode($response);
	}
	
	/**
	 * Get the access token.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function getAccessToken()
	{
		return (string) $this->access_token;
	}
	
	/**
	 * Get a list of badges from a user.
	 *
	 * @since 1.0.0
	 * @param string $userID [optional] The id of a user you wish to retrieve the badges for.
	 * @return array
	 */
	public function getBadgesFromUser($userID = 'self')
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
	
			// build url
			$url = self::API_URL . 'users/'. $userID . '/badges?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->badges;
		}
	}
	
	/**
	 * Get the checkins of a user.
	 *
	 * @since 1.0.0
	 * @param string $userID[optional] The id of the user. Default value = self (acting user) is only supported for now.
	 * @param int $limit[optional] The amount of friends to display. Max. 250
	 * @param int $offset[optional] Used for paged views.
	 * @param string $sort[optional] Sort the results. Value: newestfirst or oldestfirst.
	 * @param int $afterTimestamp[optional] Checkins that occured after this timestamp.
	 * @param int $beforeTimestamp[optional] Checkins that occured before this timestamp.
	 * @return array
	 */
	public function getCheckinsFromUser($userID = 'self', $limit = null, $offset = null, $sort = 'newestfirst', $afterTimestamp = null, $beforeTimestamp = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
			$this->parameters['sort'] = $sort;
				
			// check for given parameters
			if(isset($limit)) $parameters['limit'] = $limit;
			if(isset($offset)) $parameters['offset'] = $offset;
			if(isset($afterTimestamp)) $parameters['afterTimestamp'] = $afterTimestamp;
			if(isset($beforeTimestamp)) $parameters['beforeTimestamp'] = $beforeTimestamp;
	
			// build url
			$url = self::API_URL . 'users/' . $userID . '/checkins?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->checkins->items;
		}
	}
	
	/**
	 * Get the clientId.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function getClientId()
	{
		return (string) $this->clientId;
	}
	
	/**
	 * Get the clientSecret.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function getClientSecret()
	{
		return (string) $this->clientSecret;
	}
	
	/**
	 * Get all pending friend requests.
	 * 
	 * @since 1.0.0
	 * @return array
	 */
		
	/**
	 * Get all friends of a user.
	 * 
	 * @since 1.0.0
	 * @param string $userID[optional] The id of the user. Default value = self (acting user)
	 * @param int $limit[optional] The amount of friends to display.
	 * @param int $offset[optional] Used for paged views.
	 * @return array
	 */
	public function getFriendsFromUser($userID = 'self', $limit = null, $offset = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
			
			// check for given parameters
			if(isset($limit)) $parameters['limit'] = $limit;
			if(isset($offset)) $parameters['offset'] = $offset;
	
			// build url
			$url = self::API_URL . 'users/' . $userID . '/friends?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->friends;
		}
	}
	
	/**
	 * Get the leaderboard.
	 * 
	 * @since 1.0.0
	 * @param int $neighbors [optional] The amount of people in the leaderboard.
	 * @return array
	 */
	public function getLeaderboard($neighbors = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
		
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
			
			// if we have neighbours, append to the parameters
			if(isset($neighbors)) $parameters['neighbors'] = $neighbors;
			
			// build url
			$url = self::API_URL . 'users/leaderboard?' . $this->buildQuery($this->parameters);

			// get the data
			$response = $this->doCurl($url);
			
			// return
			return (array)$response->response->leaderboard->items;
		}
	}
	
	/**
	 * Get a list of mayorships.
	 *
	 * @since 1.0.0
	 * @param string $userID [optional] The id of a user you wish to retrieve the mayorships for.
	 * @return array
	 */
	public function getMayorshipsFromUser($userID = 'self')
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
				
			// build url
			$url = self::API_URL . 'users/'. $userID . '/mayorships?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
				
			// return
			return (array)$response->response->mayorships->items;
		}
	}
	
	/**
	 * Get all photos of a user.
	 *
	 * @since 1.0.0
	 * @param string $userID[optional] The id of the user. Default value = self (acting user)
	 * @param int $limit[optional] The amount of photos to display.
	 * @param int $offset[optional] Used for paged views.
	 * @return array
	 */
	public function getPhotosFromUser($userID = 'self', $limit = null, $offset = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
				
			// check for given parameters
			if(isset($limit)) $parameters['limit'] = $limit;
			if(isset($offset)) $parameters['offset'] = $offset;
	
			// build url
			$url = self::API_URL . 'users/' . $userID . '/photos?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->photos;
		}
	}
	
	/**
	 * Get the url.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return (string) $this->redirectUrl;
	}
	
	/**
	 * Get the token.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	private function getoAuthToken()
	{
		return (string) $this->oauthToken;
	}
	
	/**
	 * Get all tips from a user.
	 *
	 * @since 1.0.0
	 * @param string $userID[optional] The id of the user. Default value = self (acting user) is only supported for now.
	 * @param int $limit[optional] The amount of friends to display. Max. 250
	 * @param int $offset[optional] Used for paged views.
	 * @param string $sort[optional] Sort the results. Value: recent, nearby or popular
	 * @param string $ll[optional] Cošrdinates of the user. (syntax: lattitude, longitude)
	 * @return array
	 */
	public function getTipsFromUser($userID = 'self', $limit = null, $offset = null, $sort = 'recent', $ll = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
			$this->parameters['sort'] = $sort;
	
			// check for given parameters
			if(isset($limit)) $parameters['limit'] = $limit;
			if(isset($offset)) $parameters['offset'] = $offset;
			if(isset($ll)) $parameters['ll'] = $ll;
	
			// build url
			$url = self::API_URL . 'users/' . $userID . '/tips?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->tips->items;
		}
	}
	
	/**
	 * Get all todo's from a user.
	 *
	 * @since 1.0.0
	 * @param string $userID[optional] The id of the user. Default value = self (acting user) is only supported for now.
	 * @param string $sort[optional] Sort the results. Value: recent, nearby or popular
	 * @param string $ll[optional] Cošrdinates of the user. (syntax: lattitude, longitude)
	 * @return array
	 */
	public function getTodoFromUser($userID = 'self', $sort = 'recent', $ll = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
			$this->parameters['sort'] = $sort;
	
			// check for given parameters
			if(isset($ll)) $parameters['ll'] = $ll;
	
			// build url
			$url = self::API_URL . 'users/' . $userID . '/todos?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->todos->items;
		}
	}
	
	/**
	 * Get the venuehistory of a user.
	 *
	 * @since 1.0.0
	 * @param string $userID[optional] The id of the user. Default value = self (acting user) is only supported for now.
	 * @param int $afterTimestamp[optional] Checkins that occured after this timestamp.
	 * @param int $beforeTimestamp[optional] Checkins that occured before this timestamp.
	 * @return array
	 */
	public function getVenueHistoryFromUser($userID = 'self', $afterTimestamp = null, $beforeTimestamp = null)
	{
		// not authenticated?
		if(!isset($this->access_token))
		{
			throw new Exception('You are not authenticated or you forgot to set the access token.');
		}
	
		// we are authenticated so get the data
		else
		{
			// add parameters
			$this->parameters['oauth_token'] = $this->getAccessToken();
			$this->parameters['v'] = self::API_VERSION;
	
			// check for given parameters
			if(isset($afterTimestamp)) $parameters['afterTimestamp'] = $afterTimestamp;
			if(isset($beforeTimestamp)) $parameters['beforeTimestamp'] = $beforeTimestamp;
	
			// build url
			$url = self::API_URL . 'users/' . $userID . '/venuehistory?' . $this->buildQuery($this->parameters);
	
			// get the data
			$response = $this->doCurl($url);
	
			// return
			return (array)$response->response->venues;
		}
	}
	
	/**
	 * Set the access token.
	 * 
	 * @since 1.0.0
	 * @param string $token
	 */
	public function setAccessToken($token)
	{
		$this->access_token = (string) $token;
	}
	
	/**
	 * Set the clientId.
	 * 
	 * @since 1.0.0
	 * @param string $id
	 */
	private function setClientId($id)
	{
		$this->clientId = (string) $id;
	}
	
	/**
	 * Set the clientSecret.
	 * 
	 * @since 1.0.0
	 * @param string $secret
	 */
	private function setClientSecret($secret)
	{
		$this->clientSecret = (string) $secret;
	}
	
	/**
	 * Set the oAuth token.
	 * 
	 * @since 1.0.0
	 * @param string $token
	 */
	private function setOAuthToken($token)
	{
		$this->oauthToken = (string) $token;
	}
	
	/**
	 * Set the redirectUrl.
	 * 
	 * @since 1.0.0
	 * @param string $url
	 */
	private function setRedirectUrl($url)
	{
		$this->redirectUrl = (string) $url;
	}
	
	/**
	 * URL-encode method for internal use
	 *
	 * @since 1.0.0
	 * @param  mixed  $value The value to encode.
	 * @return string
	 * 
	 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
	 * @license BSD license
	 */
	private static function urlencode_rfc3986($value)
	{
		if (is_array($value)) {
			return array_map(array(__CLASS__, 'urlencode_rfc3986'), $value);
		} else {
			return str_replace('%7E', '~', rawurlencode($value));
		}
	}
}

?>