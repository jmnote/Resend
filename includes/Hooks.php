<?php

namespace MediaWiki\Extension\Resend;

use MailAddress;
use MediaWiki\Hook\AlternateUserMailerHook;
use Resend\Exceptions\ErrorException;
use Throwable;

class Hooks implements AlternateUserMailerHook {
	/**
	 * Send MediaWiki mail through Resend.
	 *
	 * @param MailAddress[]|MailAddress $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @return bool|string
	 */
	public function onAlternateUserMailer( $headers, $to, $from, $subject, $body ) {
		global $wgResendAPIKey;
		if ( !$wgResendAPIKey ) {
			return 'Resend API key is not configured. Set $wgResendAPIKey in LocalSettings.php.';
		}

		$ms = is_array( $to ) ? $to : [ $to ];

		$toEmails = [];
		foreach ( $ms as $m ) {
			if ( !( $m instanceof MailAddress ) ) {
				return 'Resend: invalid recipient payload.';
			}

			$email = $m->address;
			if ( !$this->isValidEmail( $email ) ) {
				return 'Resend: invalid recipient email found.';
			}

			$toEmails[] = $email;
		}
		if ( $toEmails === [] ) {
			return 'Resend: no recipient address resolved from MediaWiki mail payload.';
		}

		$fromEmail = $from->address;
		if ( !$this->isValidEmail( $fromEmail ) ) {
			return 'Resend: invalid sender address. Check $wgPasswordSender and mail settings.';
		}

		try {
			$payload = [
				'from' => $fromEmail,
				'to' => $toEmails,
				'subject' => (string)$subject,
				'text' => (string)$body
			];

			$resend = \Resend::client( $wgResendAPIKey );
			$resend->emails->send( $payload );
		} catch ( ErrorException $e ) {
			return 'Resend API error: ' . $e->getMessage();
		} catch ( Throwable $e ) {
			return 'Resend mailer failure: ' . $e->getMessage();
		}

		return false;
	}

	private function isValidEmail( string $email ): bool {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
	}
}
