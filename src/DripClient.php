<?php

namespace Codeception\Module;

use Drip\Client;

class DripClient extends Client {

	public function fetch_subscriber_campaign_subscriptions( $email ) {
		return $this->make_request( "$this->account_id/subscribers/$email/campaign_subscriptions" );
	}

	/**
	 * Sends a request to add a subscriber and returns its record or false
	 *
	 * @param string $email
	 *
	 * @return \Drip\ResponseInterface
	 */
	public function delete_subscriber( $email ) {
		return $this->make_request( "$this->account_id/subscribers/$email", array(), self::DELETE );
	}
}