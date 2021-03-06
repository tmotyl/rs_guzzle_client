<?php
// Copyright 2011 Ryan J. Geyer
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at

// http://www.apache.org/licenses/LICENSE-2.0

// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

namespace Guzzle\Rs;

use Guzzle\Http\Plugin\ExponentialBackoffPlugin;

use Guzzle\Http\Plugin\CookiePlugin;

use Guzzle\Service\Inspector;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Client;
use Guzzle\Service\Description\XmlDescriptionBuilder;

use Guzzle\Rs\IndiscriminateArrayCookieJar;

class RightScaleClient extends Client {
	
	protected $acct_num;
	
	protected $email;
	
	protected $password;
	
	protected $version;
	
	protected $cookieJar;
	
	/**
	 * Factory method to create a new RightScaleClient
	 *
	 * @param array|Collection $config Configuration data. Array keys:
     *     base_url - * Base URL of web service
     *     acct_num - * RightScale account number
     *     email - Email
     *     password - RS password
     *     version - * API version
	 *
	 * @return RightScaleClient
	 *
	 * @TODO update factory method and docblock for parameters
	 */
	public static function factory($config) {
		$default = array ('base_url' => 'https://my.rightscale.com/', 'version' => '1.0');
		$required = array ('acct_num', 'base_url', 'version');
		$config = Inspector::prepareConfig ( $config, $default, $required );
		
		$client = new self ( $config->get( 'base_url' ),
			$config->get('acct_num'),
			$config->get('email'),
			$config->get('password'),
			$config->get('version')
		);
		$client->setConfig ( $config );
		
		// Add the XML service description to the client		
        $client->setDescription(XmlDescriptionBuilder::build(__DIR__ . DIRECTORY_SEPARATOR . 'rs_guzzle_client_v'. $client->getVersion() . '.xml'));

		// Keep them cookies
		$client->cookieJar = new IndiscriminateArrayCookieJar(); 
        $client->getEventDispatcher()->addSubscriber(new CookiePlugin($client->cookieJar));
		
		// Retry 50x responses
        $client->getEventDispatcher()->addSubscriber(new ExponentialBackoffPlugin());

		return $client;
	}
	
	/**
     * @param string $baseUrl
     * @param string $acctNum
     * @param string $email
     * @param string $password
     * @param string $version
	 */
    public function __construct($baseUrl, $acctNum, $email, $password, $version)
    {
        parent::__construct($baseUrl);
        $this->acct_num = $acctNum;
		$this->email 		= $email;
		$this->password = $password;
		$this->version 	= $version;
	}
	
	public function getVersion() {
		return $this->version;
	}
	
	/**
	 * @return IndiscriminateArrayCookieJar
	 */
	public function getCookieJar() {
		return $this->cookieJar;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Guzzle\Service.Client::getCommand()
	 * 
	 * @return CommandInterface
	 */
	public function getCommand($name, array $args = array()) {
		$cookies = $this->cookieJar->getCookies(null,null,null,false,true);
		
		// No login cookies, or they're expired
		if(count($cookies) == 0) {
			if($this->version == "1.0") {
				$request = $this->get('/api/acct/{{acct_num}}/login');
				$request->setAuth($this->email, $this->password);
			} else {
				$request = $this->post('/api/session', null, array('email' => $this->email, 'password' => $this->password, 'account_href' => '/api/accounts/' . $this->acct_num));
			}
			$request->setHeader('X-API-VERSION', $this->version);
			$request->send();						
		}

		return parent::getCommand($name, $args);
	}
}