# Mandrill CakePHP Plugin

This enables using CakeEmail from CakePHP 2.0 with Mandrill.

## Installation

1. Copy this directory to `/app/Plugin/Mandrill`.

2. Add an email configuration for the Mandrill Transport protocol. Add this to `/app/Config/email.php`. You may find it named `email.php.default`.

		public $mandrill = array(
			'transport' => 'Mandrill.Mandrill',
			'uri' => 'https://mandrillapp.com/api/1.0/',
			'key' => 'your-key-here'
		);

	Be sure to update the API Key to your own key.

3. Load the plugin in `/app/Config/bootstrap.php`.

		CakePlugin::load('Mandrill');

## Usage

Usage is based on the `CakeEmail` class and specification.

To use, import the `CakeEmail` class:

	App::uses('CakeEmail', 'Network/Email');

and use it like so when you want to send an email.

	$email = new CakeEmail();
	$email->config('mandrill');
	$email->from('noreply@yourapp.com');
	$email->to('email@domain.com');
	$email->subject('Subject for Email');
	$result = $email->send();

The `$result` object will contain information about the success or failure of the message sending. `$result` will contain the `Mandrill` key, which contains the response from Mandrill.

## Config

Mandrill has many options that can be passed with messages, which are described in the [Mandrill API Documentation](https://mandrillapp.com/api/docs/messages.html). You can pass these to this plugin in the email configuration (ex. 1) or with $email->config() (ex. 2).

##### Example 1 (global config)

		public $mandrill = array(
			'transport' => 'Mandrill.Mandrill',
			'uri' => 'https://mandrillapp.com/api/1.0/',
			'key' => 'your-key-here',
			'track_opens' => true,
			'track_clicks' => true
		);

##### Example 2 (instance config)

		$email->config(array(
			'tags' => array('password_reset'),
			'template_name' => 'password_reset',
			'auto_html' => false
		));

----------------------
© Soroush Khanlou 2013  
Modified heavily by: [J. Miller](https://github.com/jmillerdesign)