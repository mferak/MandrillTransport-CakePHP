# Mandrill CakePHP Plugin

This enables using CakeEmail from CakePHP 2.0 with Mandrill.

## Installation

1. Copy this directory to `/app/Plugin/Mandrill` (or if you want you can use as a [submodule](http://help.github.com/submodules)):

		git clone https://github.com/jmillerdesign/MandrillTransport-CakePHP.git app/Plugin/Mandrill;

2. Add an email configuration for the Mandrill Transport protocol. Add this to `/app/Config/email.php`. You may find it named `email.php.default`.

		public $mandrill = array(
			'transport' => 'Mandrill.Mandrill',
			'uri' => 'https://mandrillapp.com/api/1.0/',
			'key' => 'your-key-here'
		);

	Be sure to update the API Key to your own key.

3. Load the plugin in `/app/Config/bootstrap.php`.

		CakePlugin::load('Mandrill');

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

## Outbound Email Usage

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

## Inbound Email Usage

1. Configure [mandrillapp.com/inbound](https://mandrillapp.com/inbound) and point the Webhook to ```http://example.com/mandrill/emails/inbound``` (replace example.com with your live domain URL)
2. Create an event handler if you want to do something whenever you receive an email. In your controller:

		<?php
		/**
		 * Handle inbound email
		 *
		 * @param CakeEvent $event Event object
		 * @return void
		 */
			public function handleInboundEmail($event) {
				// Do something with the event
				CakeLog::write('debug', print_r($event->data, true));
			}

3. In Config/bootstrap.php, add the following, to send the event to your controller. Replace "YourController" with the name of the controller where you placed the handleInboundEmail method.
		
		<?php
		/**
		 * Handle inbound Mandrill email
		 * Pass it off to YourController->handleInboundEmail()
		 */
		App::uses('CakeEventManager', 'Event');
		CakeEventManager::instance()->attach('handleInboundEmail', 'Mandrill.inbound');
		function handleInboundEmail($event) {
			App::uses('YourController', 'Controller');
			$controller = new YourController();
			$controller->handleInboundEmail($event);
		}

4. Attachments are automatically saved and can be publicly accessed at: http://example.com/mandrill/attachments/{MessageID}/{AttachmentName}


----------------------
© Soroush Khanlou 2013  
Modified heavily by: [J. Miller](https://github.com/jmillerdesign)