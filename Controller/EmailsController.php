<?php
/**
 * Handle inbound emails
 *
 * @author J. Miller (@jmillerdesign)
 */

App::uses('MandrillAppController', 'Mandrill.Controller');
class EmailsController extends MandrillAppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'Auth'
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		$this->Auth->allow('inbound');
		parent::beforeFilter();
	}

/**
 * Handle inbound emails
 *
 * @return void
 */
	public function inbound() {
		// Parse inbound email
		try {
			if (!array_key_exists('mandrill_events', $_POST)) {
				throw new Exception('Missing key mandrill_events from POST data');
			}
			$email = $_POST['mandrill_events'];
			$email = json_decode($email, true);
			$this->Email->set($email[0]); // Not sure why this is an array
		} catch (Exception $e) {
			throw new BadRequestException($e->getMessage());
		}

		// Download attachments
		$attachments = $this->Email->downloadAttachments();

		// Dispatch Mandrill.inbound event
		$this->getEventManager()->dispatch(new CakeEvent('Mandrill.inbound', $this, array(
			'subject'     => $this->Email->subject(),
			'from'        => array(
			    'email'   => $this->Email->fromEmail(),
			    'name'    => $this->Email->fromName(),
			),
			'to'          => $this->Email->to(),
			'cc'          => $this->Email->cc(),
			'date'        => date('Y-m-d H:i:s', $this->Email->timestamp()),
			'id'          => $this->Email->id,
			'text'        => $this->Email->textBody(),
			'html'        => $this->Email->htmlBody(),
			'attachments' => $attachments
		)));

		die('Received message id: ' . $this->Email->id);
	}

}
