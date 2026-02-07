<?php

namespace MediaWiki\Extension\Resend;

use MailAddress;
use MediaWiki\Hook\AlternateUserMailerHook;
use MWException;
use Throwable;

class Hooks implements AlternateUserMailerHook
{
    /**
     * Send MediaWiki mail through Resend.
     *
     * @param  MailAddress[]|MailAddress  $to
     * @param  MailAddress  $from
     * @param  string  $subject
     * @param  string  $body
     * @return bool
     *
     * @throws MWException
     */
    public function onAlternateUserMailer($headers, $to, $from, $subject, $body)
    {
        global $wgResendAPIKey;
        if (! $wgResendAPIKey) {
            throw new MWException('Please set $wgResendAPIKey in LocalSettings.php.');
        }

        $toEmails = [];
        $ms = is_array($to) ? $to : [$to];
        foreach ($ms as $m) {
            $toEmails[] = $this->extractValidEmail($m, 'recipient');
        }
        if ($toEmails === []) {
            throw new MWException('No recipient address resolved from MediaWiki mail payload.');
        }

        $fromEmail = $this->extractValidEmail($from, 'sender');

        try {
            $payload = [
                'from' => $fromEmail,
                'to' => $toEmails,
                'subject' => (string) $subject,
                'text' => (string) $body,
            ];

            $resend = \Resend::client($wgResendAPIKey);
            $resend->emails->send($payload);

            return false;
        } catch (Throwable $e) {
            throw new MWException($e->getMessage());
        }
    }

    /**
     * @param mixed $mailAddress
     *
     * @throws MWException
     */
    private function extractValidEmail($mailAddress, string $role): string
    {
        if (! ($mailAddress instanceof MailAddress)) {
            throw new MWException("Invalid {$role} payload for Resend mailer.");
        }

        $email = $mailAddress->address;
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new MWException("Invalid {$role} email found.");
        }

        return $email;
    }
}
