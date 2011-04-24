<?php

// SMS class - See send.php for simple usage

class way2sms
{
	private $DOMAIN = "http://site5.way2sms.com";
	private $INDEXURL;
	private $AUTHURL;
	private $SMSURL;
	private $KILLURL;
	private $FETCHACTIONURL;
	private $COOKIEFILE;
	private $action;
	private $isloggedin;
	
	//constructor that initializes all the URLs and params
	public function __construct()
	{
		$this->COOKIEFILE = tempnam("/tmp", "CURLCOOKIE");
		$this->INDEXURL = $this->DOMAIN . "/content/index.html";
		$this->AUTHURL = $this->DOMAIN . "/auth.cl";
		$this->SMSURL = $this->DOMAIN . "/FirstServletsms";
		$this->FETCHACTIONURL = $this->DOMAIN . "/jsp/InstantSMS.jsp";
		$this->KILLURL = $this->DOMAIN . "/jsp/logout.jsp";
		$this->isloggedin = false;
	}
	//Convert a associative array into URL passable string
	private function stringify($fields)
	{
		$fields_string = "";
		foreach($fields as $key=>$value)
		{
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string,'&');
		return $fields_string;
	}
	
	//generic utility function for curl requests
	private function curl_request($url, $data, $use_cookie)
	{
		$curl = curl_init();
		if($use_cookie)
			curl_setopt($curl, CURLOPT_COOKIEFILE, $this->COOKIEFILE);
		if($data)
		{
			curl_setopt($curl, CURLOPT_POST, count($data));
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->stringify($data));
		}
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->COOKIEFILE);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux i686; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.472.53 Safari/534.3");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		$data = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($http_status == 302)
		{
			if(strpos($data, "entry.jsp") !== false)
				$data = "-1";
		}
		curl_close($curl);
		return $data;
	}
	
	//simple parsing to get a hidden form field
	private function fetchaction($data)
	{
		$pattern = "<input type=\"hidden\" name=\"Action\" id=\"Action\" value=\"";
		$pos = strpos($data, $pattern);
		if($pos > -1)
			$this->action = substr($data, $pos + strlen($pattern), strpos(substr($data, $pos + strlen($pattern)), "\""));
	}
	
	//login method
	public function login($username, $password)
	{
		$fields = array('username'=>$username, 'password'=>$password, 'login'=>'Login');
		//get cookies
		$this->curl_request($this->INDEXURL, 0, false);
		//authenticate
		$auth_response = $this->curl_request($this->AUTHURL, $fields, true);
		if($auth_response == "-1") // auth failure
			return false;
		//get action value
		$data = $this->curl_request($this->FETCHACTIONURL, 0, true);
		$this->fetchaction($data);
		$this->isloggedin = true;
		return true;
	}
	
	//send sms method - split longer sms into multiple sms of size 130
	public function sendsms($to, $msg)
	{
		if($this->isloggedin)
		{
			$msg_parts = str_split($msg, 130);
			$i = 1;
			foreach($msg_parts as $msg_part)
			{
				$count_string = " [" . $i++ . "/" . count($msg_parts) . "]";
				$fields = array('HiddenAction'=>'instantsms', 'catnamedis'=>'Birthday', 'Action'=>$this->action,'chkall'=>'on', 'MobNo'=>$to, 'textArea'=>urlencode($msg_part . $count_string));
				$this->curl_request($this->SMSURL, $fields, true);
			}
			return true;
		}
		else
			return false;
	}
	
	//logout method
	public function logout()
	{
		if($this->isloggedin)
		{
			$this->curl_request($this->KILLURL, 0, true);
			unlink($this->COOKIEFILE);
		}
	}
}

?>
