<?php

namespace Codeception\Module;

use PHPUnit\Framework\Assert;

class Drip extends EmailMarketing {

	/**
	 * @var DripClient
	 */
	protected static $client;

	/**
	 * @return DripClient
	 */
	protected function getClient() {
		if ( empty( $this::$client ) ) {
			$this::$client = new DripClient( $this->config['api_key'], $this->config['account_id'] );
		}

		return $this::$client;
	}

	protected function getSubscriber( $email ) {
		$subscriber = $this->getClient()->fetch_subscriber( array( 'email' => $email ) );
		if ( ! $subscriber->is_success() ) {
			return false;
		}

		$body = $subscriber->get_contents();

		if ( ! isset( $body['subscribers'][0] ) ) {
			return false;
		}

		return $body['subscribers'][0];
	}

	public function getCampaignsForSubscriber( $email, $status = null ) {
		$campaigns = $this->getClient()->fetch_subscriber_campaign_subscriptions( $email );
		if ( ! $campaigns->is_success() ) {
			return false;
		}

		$body = $campaigns->get_contents();

		if ( ! isset( $body['campaign_subscriptions'] ) ) {
			return false;
		}

		$campaign_ids = array();
		foreach ( $body['campaign_subscriptions'] as $campaign ) {
			if ( $status && $status !== $campaign['status'] ) {
				continue;
			}
			$campaign_ids[] = $campaign['campaign_id'];
		}

		return $campaign_ids;
	}

	public function getTagsForSubscriber( $email ) {
		$subscriber = $this->getSubscriber( $email );
		if ( ! $subscriber ) {
			Assert::fail( 'Subscriber not found' );
		}

		if ( empty( $subscriber['tags'] ) ) {
			return array();
		}

		return $subscriber['tags'];
	}

	protected function getSubscriberCustomField( $email, $field_name ) {
		$subscriber = $this->getSubscriber( $email );
		if ( ! $subscriber ) {
			Assert::fail( 'Subscriber not found' );
		}

		if ( ! isset( $subscriber['custom_fields'][ $field_name ] ) ) {
			Assert::fail( 'Subscriber custom field not set' );
		}

		return $subscriber['custom_fields'][ $field_name ];
	}

	/**
	 * Delete a single file from the current bucket.
	 *
	 * @param string $email
	 *
	 * @return mixed
	 */
	public function deleteSubscriber( $email ) {
		$result = $this->getClient()->delete_subscriber( $email );
		if ( 404 === $result->get_http_code() ) {
			// Subscriber doesn't exist
			return;
		}

		if ( ! $result->is_success() ) {
			Assert::fail( $result->get_http_message() );
		}
	}
}