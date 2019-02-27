<?php

namespace Codeception\Module;

use Codeception\Module;
use PHPUnit\Framework\Assert;

class Drip extends Module {

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

	public function seeCustomFieldForSubscriber( $email, $name, $value ) {
		$current_value = $this->getSubscriberCustomField( $email, $name );

		Assert::assertEquals( $current_value, $value );
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

	public function seeTagsForSubscriber( $email, $tags ) {
		$subscriber = $this->getSubscriber( $email );
		if ( ! $subscriber ) {
			Assert::fail( 'Subscriber not found' );
		}

		if ( ! is_array( $tags ) ) {
			$tags = array( $tags );
		}

		sort( $tags );
		sort( $subscriber['tags'] );

		Assert::assertTrue( ! array_diff( $tags, $subscriber['tags'] ) );
	}

	public function cantSeeTagsForSubscriber( $email, $tags ) {
		$subscriber = $this->getSubscriber( $email );
		if ( ! $subscriber ) {
			Assert::fail( 'Subscriber not found' );
		}

		if ( ! is_array( $tags ) ) {
			$tags = array( $tags );
		}

		foreach( $tags as $tag ) {
			Assert::assertFalse( in_array( $tag, $subscriber['tags'] ) );
		}
	}

	public function seeCampaignsForSubscriber( $email, $campaign_ids, $status = 'active' ) {
		$campaigns = $this->getCampaignsForSubscriber( $email, $status );
		if ( false === $campaigns ) {
			Assert::fail( 'Subscriber not found' );
		}

		if ( ! is_array( $campaign_ids ) ) {
			$campaign_ids = array( $campaign_ids );
		}

		sort( $campaign_ids );
		sort( $campaigns );


		Assert::assertTrue( ! array_diff( $campaign_ids, $campaigns ) );
	}

	public function cantSeeCampaignsForSubscriber( $email, $campaign_ids, $status = 'active' ) {
		$campaigns = $this->getCampaignsForSubscriber( $email, $status );
		if ( false === $campaigns ) {
			Assert::fail( 'Subscriber not found' );
		}

		if ( ! is_array( $campaign_ids ) ) {
			$campaign_ids = array( $campaign_ids );
		}

		foreach ( $campaign_ids as $campaign_id ) {
			Assert::assertFalse( in_array( $campaign_id, $campaigns ) );
		}
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

	/**
	 *
	 * @param int $timeout_in_second
	 * @param int $interval_in_millisecond
	 *
	 * @return DripWait
	 */
	protected function wait( $timeout_in_second = 30, $interval_in_millisecond = 250 ) {
		return new DripWait( $this, $timeout_in_second, $interval_in_millisecond );
	}

	/**
	 * Wait until an email has been received with specific text in the text body.
	 *
	 * @param string $subject
	 * @param int    $timeout
	 *
	 * @throws \Exception
	 */
	public function waitForSubscriberToNotHaveTags( $email, $tags, $timeout = 5 ) {
		if ( ! is_array( $tags ) ) {
			$tags = array( $tags );
		}
		$condition = function () use ( $email, $tags ) {
			$subscriber = $this->getSubscriber( $email );
			if ( ! $subscriber ) {
				return false;
			}

			$count  = 0;
			foreach ( $tags as $tag ) {
				$constraint = Assert::isFalse();
				if ( $constraint->evaluate( in_array( $tag, $subscriber['tags'] ), '', true ) ) {
					$count ++;
				}
			}

			if ( $count === count( $tags ) ) {
				return true;
			}

			return false;
		};

		$message = sprintf( 'Waited for %d secs but the subscriber still has one or more of the tags %s', $timeout, implode( ',', $tags ) );

		$this->wait( $timeout )->until( $condition, $message );
	}
}