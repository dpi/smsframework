SMS Framework

# License

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc., 51 Franklin
Street, Fifth Floor, Boston, MA 02110-1301 USA.

# Quick start

There is no configuration UI, instead we use container parameters.

In your site directory, usually `sites/default/`, create a `services.yml` file.

Add to `services.yml`:

```yaml
parameters:
  sms.transports:
    mytransport: 'mytransport://default'

  notifier.channel_policy:
    urgent: ['sms']
    high: ['sms']
    medium: ['sms']
    low: ['sms']
```

Go to [Symfony Notifier SMS documentation][symfony-notifier-sms], find your SMS vendor, and copy+paste the _DSN_ in place of `mytransport://default` above. Also execute the `composer require` section found on the same page.

Open settings.php, add `$settings['container_yamls'][] = __DIR__ . '/services.yml';`

Clear cache.

If you want to know how all this works, or a better explanation, read on.

# Configuration

Configuration is straightforward.

There is no UI to configure SMS Framework, instead, you will use YAML. The following will guide you on how to do that.

Depending on your comfort, you can either implement a brand-new module, or add to a sitewide services.yml file (preferred). The following describes how to configure a sitewide services.yml:

## Sitewide YAML**

Go to your site settings directory, usually `sites/default/`.

In that directory, determine if you have a `services.local.yml` file. If so, you can skip to **YAML** section below.

If not, you'll need to create a `services.local.yml file` and paste the YAML contents below. Then, in your `settings.php` file, include the file with:

```php
$settings['container_yamls'][] = __DIR__ . '/services.local.yml';
```
## Transport configuration

Transports use DNSs for configuration. A DSN specifies which SMS vendor to use (the protocol part), and the rest is API keys, passwords, etc.

Look up your SMS vendor in the list at [Symfony Notifier SMS documentation][symfony-notifier-sms]. This page shows how to format your DSN.

Run the `composer require` step in order to bring in the integration for your SMS vendor. Then clear your Drupal cache to allow Notifier + SMS to pick up this new integration.

## YAML

Add the following YAML structure to a YAML file, and change appropriately.

- `mytransport` is a custom name for your transport. Normally this doesn't need to be changed, unless you are routing SMS's to specific vendors in custom code.
- Change the DSN. Refer to [Symfony Notifier SMS documentation][symfony-notifier-sms].
- Normally the channel policy can be left as-is, unless you're involving other channels (email, chat, push, etc).

```yaml
parameters:
  sms.transports:
    mytransport: 'fakesms+logger://default'

  notifier.channel_policy:
    urgent: ['sms']
    high: ['sms']
    medium: ['sms']
    low: ['sms']
```

Any changes to YAML require a Drupal cache clear.

## General configuration

Configuration for general SMS Framework features are also provided by container parameters.

Configuration is optional. Default values can be found in `sms.services.yml`, to
override, create a `services.local.yml` per above.

### Entity Mapping

The `notifier.field_mapping.sms` parameter allows mapping entity fields as phone number source data.

```yaml
  notifier.field_mapping.sms:
    - entity_type: user
      bundle: user
      field_name: my_phone_number_field
      verifications: true
```

### SMS Verification settings

The `sms.verification` parameter provides general settings for verifications.

See `sms.services.yml` for documentation.

```yaml
  sms.verification:
    enabled: true
    route:
      path: '/verify'
      success: '/user'
    unique: true
    unverified:
      lifetime: 900
      purge_fields: true
    message: |-
      Your verification code is '[sms-message:verification-code]'.
      Go to [sms:verification-url] to verify your phone number.
      - [site:name]
```

Changing `enabled` to false will also remove relevant verification services
from the container.

## Testing

The special _Fake SMS_ integration is able to send SMS to logger or email.

To install this integration, run the `composer require symfony/fake-sms-notifier` step, per [Symfony Notifier SMS documentation][symfony-notifier-sms].

Then configure the DSN in YAML with either `fakesms+logger` or `fakesms+email` protocol.

# API

Sending an SMS programmatically is quite simple:

 - Construct the recipient
 - Construct the message
 - Send the combined notification

**Recipient**

Create a recipient. A recipient is any object that implements `\Symfony\Component\Notifier\Recipient\RecipientInterface`.

Though usually you'd want to implement something slightly more specific. A recipient that can receive SMS needs to extend `\Symfony\Component\Notifier\Recipient\SmsRecipientInterface`.

If your project makes use of _Bundle classes_, you can consider implementing a user bundle class. Then making this bundle class implement `\Symfony\Component\Notifier\Recipient\SmsRecipientInterface` !.

```php
$recipient = new \Symfony\Component\Notifier\Recipient\Recipient(
  email: 'hello@example.com',
  phone: '+123123123',
);
```

A phone number does not have any specific formatting. It just needs to be a non-empty string. Phone numbers are validated by your chosen SMS vendor. If you want to validate phone numbers, you may use the utilities implemented by [Telephone Validation][telephone-validation].

**Message**

```php
$notification = (new \Symfony\Component\Notifier\Notification\Notification())
  ->subject('Test message');
```

**Send**

Send the message through the Notifier service.

The notifier service is autowired with `\Symfony\Component\Notifier\NotifierInterface`.

```php
final class MyController {

  function __construct(
    private readonly \Symfony\Component\Notifier\NotifierInterface $notifier,
  ) {}

  function __invoke(){
    // Send the $notification to the $recipient.
    $this->notifier->send($notification, $recipient);

    // `send` method accepts any number of recipients. E.g:
    $this->notifier->send($notification, recipients: $recipient1, $recipient2, $recipient3);
  }

}
```

Further documentation for _Sending_ can be found in https://symfony.com/doc/current/notifier.html#creating-sending-notifications

# Messenger (i.e, Queues)

When Symfony Messenger is available (via [SM project][drupal-symfony-messenger]), messages will be dispatched asynchronously, i.e. via a queue.

No manual configuration is required. Messenger is setup and configured automatically.

---

- [drupal-symfony-messenger]: https://www.drupal.org/project/sm
- [symfony-notifier-sms]: https://symfony.com/doc/current/notifier.html#sms-channel
- [telephone-validation]: https://drupal.org/project/telephone_validation
