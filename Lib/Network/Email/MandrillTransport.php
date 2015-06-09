<?php
/**
 * Mandrill Transport
 *
 */

App::uses('AbstractTransport', 'Network/Email');
App::uses('HttpSocket', 'Network/Http');
App::uses('File', 'Utility');

/**
 * MandrillTransport
 *
 * This class is used for sending email messages
 * using the Mandrill API http://mandrillapp.com/
 *
 */
class MandrillTransport extends AbstractTransport {

/**
 * CakeEmail
 *
 * @var CakeEmail
 */
	protected $_cakeEmail;

/**
 * Variable that holds Mandrill connection
 *
 * @var HttpSocket
 */
	private $__mandrillConnection;

/**
 * CakeEmail headers
 *
 * @var array
 */
	protected $_headers;

/**
 * Configuration to transport
 *
 * @var mixed
 */
	protected $_config = array();

/**
 * Sends out email via Mandrill
 *
 * @return array Return the Mandrill
 */
	public function send(CakeEmail $email) {
		$this->_cakeEmail = $email;

		$this->_config = $this->_cakeEmail->config();
		$this->_headers = $this->_cakeEmail->getHeaders(array('from', 'to', 'cc', 'bcc', 'replyTo', 'subject'));

		// Setup connection
		$this->__mandrillConnection = &new HttpSocket();

		$message = $this->__buildMessage();

		$request = array(
			'header' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			)
		);

		if (array_key_exists('template_name', $this->_config)) {
			// Use Mandrill template
			$messageSendURI = $this->_config['uri'] . 'messages/send-template.json';
		} else {
			// Build email internally
			$messageSendURI = $this->_config['uri'] . 'messages/send.json';
		}

		// Perform the http connection
		$returnMandrill = $this->__mandrillConnection->post($messageSendURI, json_encode($message), $request);

		// Parse Mandrill results
		$result = json_decode($returnMandrill, true);
		$headers = $this->_headersToString($this->_headers);

		return array(
			'has_error' => (!$result || (array_key_exists('status', $result) && ($result['status'] == 'error'))),
			'Mandrill'  => $result,
			'headers'   => $headers,
			'message'   => $message
		);
	}

/**
 * Build message
 *
 * @return array
 */
	private function __buildMessage() {
		$json = array();

		// a valid API key
		$json['key'] = $this->_config['key'];

		// Template
		if (array_key_exists('template_name', $this->_config)) {
			// the immutable name or slug of a template that exists in the user's account.
			$json['template_name'] = $this->_config['template_name'];

			// an array of template content to send.
			// Each item in the array should be a struct with two keys
			// - name: the name of the content block to set the content for,
			// - and content: the actual content to put into the block
			$json['template_content'] = array();
			if (array_key_exists('template_content', $this->_config)) {
				$json['template_content'] = $this->_config['template_content'];
			}
		}

		// the other information on the message to send
		// - same as /messages/send, but without the html content
		$message = array();

		// optional full HTML content to be sent if not in template
		$htmlMessage = $this->_cakeEmail->message('html');
		if ($htmlMessage && ($htmlMessage != "\n")) {
			$message['html'] = $htmlMessage;
		}

		// optional full text content to be sent
		$textMessage = $this->_cakeEmail->message('text');
		if ($textMessage && ($textMessage != "\n")) {
			$message['text'] = $textMessage;
		}

		// the message subject
		$message['subject'] = mb_decode_mimeheader($this->_headers['Subject']);

		// the sender email address.
		$from = $this->_cakeEmail->from();
		$message['from_email'] = array_keys($from);
		$message['from_email'] = array_shift($message['from_email']);

		// optional from name to be used
		if (reset($from) != $message['from_email']) {
			$message['from_name'] = reset($from);
		}

		// If you sent from "mandrill@example.com",
		// then send from the template's settings instead
		if ($message['from_email'] == 'mandrill@example.com') {
			unset($message['from_email']);
		}
		if (array_key_exists('from_name', $message) && ($message['from_name'] == 'mandrill@example.com')) {
			unset($message['from_name']);
		}

		// an array of recipient information.
		$message['to'] = array();
		$tos = array_merge($this->_cakeEmail->to(), $this->_cakeEmail->cc());
		foreach ($tos as $email => $name) {
			// the email address of the recipient
			$to = array('email' => $email);
			// the optional display name to use for the recipient
			if ($name != $email) {
				$to['name'] = $name;
			}
			$message['to'][] = $to;
		}

		// optional extra headers to add to the message
		// (currently only Reply-To and X-* headers are allowed)
		$message['headers'] = array();
		if ($this->_cakeEmail->replyTo()) {
			$message['headers']['Reply-To'] = current(array_keys($this->_cakeEmail->replyTo()));
		}
		if (array_key_exists('headers', $this->_config)) {
			$message['headers'] = array_merge($message['headers'], $this->_config['headers']);
		}

		// whether or not this message is important,
		// and should be delivered ahead of non-important messages
		if (array_key_exists('important', $this->_config)) {
			$message['important'] = $this->_config['important'];
		}

		// whether or not to turn on open tracking for the message
		if (array_key_exists('track_opens', $this->_config)) {
			$message['track_opens'] = $this->_config['track_opens'];
		}

		// whether or not to turn on click tracking for the message
		if (array_key_exists('track_clicks', $this->_config)) {
			$message['track_clicks'] = $this->_config['track_clicks'];
		}

		// whether or not to automatically generate a text part for messages that are not given text
		if (array_key_exists('auto_text', $this->_config)) {
			$message['auto_text'] = $this->_config['auto_text'];
		}

		// whether or not to automatically generate an HTML part for messages that are not given HTML
		if (array_key_exists('auto_html', $this->_config)) {
			$message['auto_html'] = $this->_config['auto_html'];
		}

		// whether or not to automatically inline all CSS styles provided in the message HTML
		// - only for HTML documents less than 256KB in size
		if (array_key_exists('inline_css', $this->_config)) {
			$message['inline_css'] = $this->_config['inline_css'];
		}

		// whether or not to strip the query string from URLs when aggregating tracked URL data
		if (array_key_exists('url_strip_qs', $this->_config)) {
			$message['url_strip_qs'] = $this->_config['url_strip_qs'];
		}

		// whether or not to expose all recipients in to "To" header for each email
		if (array_key_exists('preserve_recipients', $this->_config)) {
			$message['preserve_recipients'] = $this->_config['preserve_recipients'];
		}

		// an optional address to receive an exact copy of each recipient's email
		if ($this->_cakeEmail->bcc()) {
			$message['bcc_address'] = array_shift(array_keys($this->_cakeEmail->bcc()));
		}

		// a custom domain to use for tracking opens and clicks instead of mandrillapp.com
		if (array_key_exists('tracking_domain', $this->_config)) {
			$message['tracking_domain'] = $this->_config['tracking_domain'];
		}

		// a custom domain to use for SPF/DKIM signing instead of mandrill (for "via" or "on behalf of" in email clients)
		if (array_key_exists('signing_domain', $this->_config)) {
			$message['signing_domain'] = $this->_config['signing_domain'];
		}

		// whether to evaluate merge tags in the message. Will automatically be set to true if either merge_vars or global_merge_vars are provided.
		if (array_key_exists('merge', $this->_config)) {
			$message['merge'] = $this->_config['merge'];
		}

		// global merge variables to use for all recipients. You can override these per recipient.
		$message['global_merge_vars'] = array();
		foreach ($this->_cakeEmail->viewVars() as $key => $value) {
			$message['global_merge_vars'][] = array(
				'name' => $key,
				'content' => $value
			);
		}

		// per-recipient merge variables, which override global merge variables with the same name.
		if (array_key_exists('merge_vars', $this->_config)) {
			$message['merge_vars'] = $this->_config['merge_vars'];
		}

		// an array of string to tag the message with.
		// Stats are accumulated using tags,
		// though we only store the first 100 we see,
		// so this should not be unique or change frequently.
		// Tags should be 50 characters or less.
		// Any tags starting with an underscore are reserved for internal use and will cause errors.
		if (array_key_exists('tags', $this->_config)) {
			$message['tags'] = $this->_config['tags'];
		}

		// an array of strings indicating for which any matching URLs
		// will automatically have Google Analytics parameters appended
		// to their query string automatically.
		if (array_key_exists('google_analytics_domains', $this->_config)) {
			$message['google_analytics_domains'] = $this->_config['google_analytics_domains'];
		}

		// optional string indicating the value to set for the utm_campaign tracking parameter.
		// If this isn't provided the email's from address will be used instead.
		if (array_key_exists('google_analytics_campaign', $this->_config)) {
			$message['google_analytics_campaign'] = $this->_config['google_analytics_campaign'];
		}

		// metadata an associative array of user metadata. Mandrill will store this metadata and make it available for retrieval. In addition, you can select up to 10 metadata fields to index and make searchable using the Mandrill search api.
		if (array_key_exists('metadata', $this->_config)) {
			$message['metadata'] = $this->_config['metadata'];
		}

		// Per-recipient metadata that will override the global values specified in the metadata parameter.
		if (array_key_exists('recipient_metadata', $this->_config)) {
			$message['recipient_metadata'] = $this->_config['recipient_metadata'];
		}

		// an array of supported attachments to add to the message
		$message['attachments'] = array();
		foreach ($this->_cakeEmail->attachments() as $filename => $fileInfo) {
			$message['attachments'][] = array(
				'type' => $fileInfo['mimetype'],
				'name' => $filename,
				'content' => isset($fileInfo['data']) ? $fileInfo['data'] : $this->_readFile($fileInfo['file'])
			);
		}

		// an array of embedded images to add to the message
		if (array_key_exists('images', $this->_config)) {
			$message['images'] = $this->_config['images'];
		}

		// Message
		$json['message'] = $message;

		// enable a background sending mode that is optimized for bulk sending. In async mode, messages/send will immediately return a status of "queued" for every recipient. To handle rejections when sending in async mode, set up a webhook for the 'reject' event. Defaults to false for messages with no more than 10 recipients; messages with more than 10 recipients are always sent asynchronously, regardless of the value of async.
		if (array_key_exists('async', $this->_config)) {
			$json['async'] = $this->_config['async'];
		}

		return $json;
	}

/**
 * Read the file contents and return a base64 version of the file contents.
 *
 * @param string $path The absolute path to the file to read.
 * @return string File contents in base64 encoding
 */
	protected function _readFile($path) {
		$File = new File($path);
		return chunk_split(base64_encode($File->read()));
	}

}
