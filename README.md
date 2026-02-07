# Resend MediaWiki Extension

What it does: sends MediaWiki user emails through the [Resend](https://resend.com/) API instead of the default mail transport.

Why is this needed: it allows API-based email delivery without SMTP setup and keeps email infrastructure separated from your MediaWiki host.

## Installation

Note: this version of Extension:Resend targets MediaWiki `1.43+`.

1. Download the extension (`git clone --depth 1`):

```bash
cd /path/to/mediawiki/extensions
git clone --depth 1 https://github.com/jmnote/Resend.git
```

2. Configure Composer merge loading in `/path/to/mediawiki/composer.local.json` and install dependencies.

For details, see:
https://www.mediawiki.org/wiki/Composer#Using_composer-merge-plugin

```json
{
    "extra": {
        "merge-plugin": {
            "include": [
                "extensions/*/composer.json",
                "skins/*/composer.json"
            ]
        }
    }
}
```

```bash
cd /path/to/mediawiki
composer update
```

3. Update `LocalSettings.php` (see configuration below).

## Configuration in LocalSettings.php

```php
wfLoadExtension( 'Resend' );
$wgResendAPIKey = 're_xxxxxxxxxxxxxxxxx';
```

## How it works

- It intercepts MediaWiki email delivery using the `AlternateUserMailer` hook.
- If Resend delivery succeeds, the default MediaWiki mailer is skipped.
- If `$wgResendAPIKey` is empty, it returns an error.
