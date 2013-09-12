<?php
/**
 * Handle inbound emails
 *
 * @author J. Miller (@jmillerdesign)
 */

App::uses('MandrillAppModel', 'Mandrill.Model');
App::uses('File', 'Utility');
class Email extends MandrillAppModel {

/**
 * No database table
 *
 * @var boolean
 */
	public $useTable = false;

/**
 * Constructor
 */
	public function __construct($id = false, $table = null, $ds = null) {
		$this->pluginDir = APP . 'Plugin' . DS . 'Mandrill' . DS;
		$this->attachmentsDir = APP . 'Plugin' . DS . 'Mandrill' . DS . 'webroot' . DS . 'attachments' . DS;

		parent::__construct($id, $table, $ds);
	}

/**
 * This function does two things:
 *
 * 1. it scans the array $one for the primary key,
 * and if that's found, it sets the current id to the value of $one[id].
 * For all other keys than 'id' the keys and values of $one are copied to the 'data' property of this object.
 * 2. Returns an array with all of $one's keys and values.
 * (Alternative indata: two strings, which are mangled to
 * a one-item, two-dimensional array using $one for a key and $two as its value.)
 *
 * @param string|array|SimpleXmlElement|DomNode $one Array or string of data
 * @param string $two Value string for the alternative indata method
 * @return array Data with all of $one's keys and values
 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html
 */
	public function set($one, $two = null) {
		// Format data
		$data = parent::set($one, $two);

		// Set the primaryKey
		if (empty($one[$this->alias][$this->primaryKey])) {
			$data[$this->alias][$this->primaryKey] = $this->messageId($data);
		}

		return parent::set($data);
	}

/**
 * Extract the messageId to use as the primaryKey
 *
 * @param array $email Email
 * @return string Unique Email id
 */
	public function messageId($email = null) {
		if (!isset($email)) {
			$email = $this->data;
		}

		$id = $email[$this->alias]['msg']['headers']['Message-Id'];
		$id = preg_replace('/[^A-Za-z0-9_\-]/', '_', $id); // Sanitize
		return $id;
	}

/**
 * Check if an email contains attachments
 *
 * @return boolean True if has attachments
 */
	public function hasAttachments() {
		return !empty($this->data[$this->alias]['msg']['attachments']);
	}

/**
 * Get list of attachments
 *
 * @return array Attachments
 */
	public function attachments() {
		return $this->data[$this->alias]['msg']['attachments'];
	}

/**
 * Download one attachment
 *
 * @param array $attachment Attachment
 * @return boolean Success
 */
	public function downloadAttachment($attachment) {
		$path = $this->attachmentsDir . $this->id . DS;
		$File = new File($path . $attachment['name']);
		return $File->write(base64_decode($attachment['content']));
	}

/**
 * Download all attachments
 *
 * @return array Attachments that were downloaded
 */
	public function downloadAttachments() {
		$attachments = array();
		if ($this->hasAttachments()) {
			if (!file_exists($this->attachmentsDir . $this->id)) {
				mkdir($this->attachmentsDir . $this->id);
			}
			foreach($this->attachments() as $attachment) {
				if ($this->downloadAttachment($attachment)) {
					$attachments[] = $attachment['name'];
				}
			}
		}
		return $attachments;
	}

/**
 * Get the subject
 *
 * @return string Subject
 */
	public function subject() {
		return $this->data[$this->alias]['msg']['subject'];
	}

/**
 * Get the from email address
 *
 * @return string From email address
 */
	public function fromEmail() {
		return $this->data[$this->alias]['msg']['from_email'];
	}

/**
 * Get the from name
 *
 * @return string From name
 */
	public function fromName() {
		if (!array_key_exists('from_name', $this->data[$this->alias]['msg'])) {
			return '';
		}
		return $this->data[$this->alias]['msg']['from_name'];
	}

/**
 * Get the text body
 *
 * @return string Text body
 */
	public function textBody() {
		return $this->data[$this->alias]['msg']['text'];
	}

/**
 * Get the html body
 *
 * @return string HTML body
 */
	public function htmlBody() {
		return $this->data[$this->alias]['msg']['html'];
	}

/**
 * Get the time when the email was received
 *
 * @return integer Time
 */
	public function timestamp() {
		return (integer) $this->data[$this->alias]['ts'];
	}

/**
 * Get the email addresses this email was sent to
 *
 * @return array Email addresses, containing email and name keys
 */
	public function to() {
		$recipients = array();
		foreach ($this->data[$this->alias]['msg']['to'] as $recipient) {
			$recipients[] = array(
				'email' => $recipient[0],
				'name'  => $recipient[1]
			);
		}
		return $recipients;
	}

/**
 * Get the email addresses this email was sent to in the CC field
 *
 * @return array Email addresses, containing email and name keys
 */
	public function cc() {
		$recipients = array();
		if (empty($this->data[$this->alias]['msg']['cc'])) {
			return $recipients;
		}
		foreach ($this->data[$this->alias]['msg']['cc'] as $recipient) {
			$recipients[] = array(
				'email' => $recipient[0],
				'name'  => $recipient[1]
			);
		}
		return $recipients;
	}

}
