<?php
App::uses('CakeEmail', 'Network/Email');

/**
 * Test case
 *
 */
class MandrillTransportTest extends CakeTestCase {

/**
 * CakeEmail
 *
 * @var CakeEmail
 */
	private $email;

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		$this->email = new CakeEmail();
		$this->email->config('mandrill');
	}

/**
 * testMandrillSendSimple method
 *
 * @return void
 */
	public function testMandrillSendSimple() {
		$this->email->config(array(
			'tags' => array('test')
		));
		$this->email->from('from@example.com');
		$this->email->to('to@example.com');

		$sendReturn = $this->email->send();

		// Make sure status is not an error
		if (array_key_exists('status', $sendReturn['Mandrill'])) {
			$this->assertNotEquals('error', $sendReturn['Mandrill']['status']);
		}
		$this->assertFalse($sendReturn['has_error']);
		$this->assertTrue(count($sendReturn['Mandrill']) > 0);
	}

/**
 * testMandrillSendAdvanced method
 *
 * @return void
 */
	public function testMandrillSendAdvanced() {
		$this->email->config(array(
			'tags' => array('test'),
			'template_name' => 'test',
			'auto_text' => false
		));
		$this->email->emailFormat('html');
		$this->email->from(array('from@example.com' => 'From'));
		$this->email->to(array('to@example.com' => 'To'));
		$this->email->replyTo(array('replyto@example.com' => 'ReplyTo'));
		$this->email->cc(array('cc@example.com' => 'CC'));
		$this->email->bcc(array('bcc@example.com' => 'BCC'));
		$this->email->subject('Test Mandrill');
		$this->email->viewVars(array('TIME' => date('Y-m-d H:i:s')));
		$this->email->addAttachments(WWW_ROOT . 'img' . DS . 'cake.icon.png');

		$sendReturn = $this->email->send();

		// Make sure status is not an error
		if (array_key_exists('status', $sendReturn['Mandrill'])) {
			$this->assertNotEquals('error', $sendReturn['Mandrill']['status']);
		}
		$this->assertTrue(count($sendReturn['Mandrill']) > 0);
	}

}
