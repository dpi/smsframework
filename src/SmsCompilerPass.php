<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\sms\Hook\SmsVerificationHooks;
use Drupal\sms\PhoneNumber\BundleConfiguration;
use Drupal\sms\PhoneNumberVerification\EventListener\Routes;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerification;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Notifier\Bridge;

/**
 * SMS compiler pass.
 *
 * @phpstan-import-type FieldMapping from \Drupal\notifier\NotifierCompilerPass
 * @phpstan-type SmsVerificationParameter array{enabled: bool, unique: bool, route: array{path: string, success: string}, message: string, unverified: array{lifetime: positive-int, purge_fields: bool}}
 * @phpstan-type SmsPhoneNumberMapping FieldMapping&array{verifications?: bool}
 */
final class SmsCompilerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container): void {
    // SMS Framework services:
    /** @var array<SmsPhoneNumberMapping> $mappingRaw */
    // Take notifier configuration and customize it with `verifications`.
    $mappingRaw = $container->getParameter('notifier.field_mapping.sms');
    $mapping = [];
    foreach ($mappingRaw as $map) {
      $mapping[] = BundleConfiguration::fromRaw($map);
    }

    $def = $container->getDefinition(EntityMapping::class);
    $def->setArgument('$mapping', \serialize($mapping));

    // Verification:
    /** @var SmsVerificationParameter $verification */
    $verification = $container->getParameter('sms.verification');

    if ($verification['enabled']) {
      $container->getDefinition(Routes::class)
        ->setArgument('$enabled', $verification['enabled'])
        ->setArgument('$path', $verification['route']['path']);
      $container->getDefinition(SmsPhoneNumberVerification::class)
        ->setArgument('$verificationMessage', $verification['message'])
        ->setArgument('$unverifiedLifetime', $verification['unverified']['lifetime'])
        ->setArgument('$enforceUniquePhoneNumbers', $verification['unique']);
    }
    else {
      $container->removeDefinition(SmsVerificationHooks::class);
      $container->removeDefinition(Routes::class);
      $container->removeDefinition(SmsPhoneNumberVerification::class);
      $container->removeAlias(SmsPhoneNumberVerificationInterface::class);
      $container->removeDefinition('logger.channel.sms.phone_number_verification');
    }

    // Notifier/Texter services:
    $texterFactories = [
      // @phpcs:disable
      'all-my-sms' => Bridge\AllMySms\AllMySmsTransportFactory::class,
      'bandwidth' => Bridge\Bandwidth\BandwidthTransportFactory::class,
      'brevo' => Bridge\Brevo\BrevoTransportFactory::class,
      'click-send' => Bridge\ClickSend\ClickSendTransportFactory::class,
      'clickatell' => Bridge\Clickatell\ClickatellTransportFactory::class,
      'contact-everyone' => Bridge\ContactEveryone\ContactEveryoneTransportFactory::class,
      'engagespot' => Bridge\Engagespot\EngagespotTransportFactory::class,
      'esendex' => Bridge\Esendex\EsendexTransportFactory::class,
      'expo' => Bridge\Expo\ExpoTransportFactory::class,
      'fake-sms' => Bridge\FakeSms\FakeSmsTransportFactory::class,
      'forty-six-elks' => Bridge\FortySixElks\FortySixElksTransportFactory::class,
      'free-mobile' => Bridge\FreeMobile\FreeMobileTransportFactory::class,
      'gateway-api' => Bridge\GatewayApi\GatewayApiTransportFactory::class,
      'go-ip' => Bridge\GoIp\GoIpTransportFactory::class,
      'infobip' => Bridge\Infobip\InfobipTransportFactory::class,
      'iqsms' => Bridge\Iqsms\IqsmsTransportFactory::class,
      'isendpro' => Bridge\Isendpro\IsendproTransportFactory::class,
      'joli-notif' => Bridge\JoliNotif\JoliNotifTransportFactory::class,
      'kaz-info-teh' => Bridge\KazInfoTeh\KazInfoTehTransportFactory::class,
      'light-sms' => Bridge\LightSms\LightSmsTransportFactory::class,
      'lox24' => Bridge\Lox24\Lox24TransportFactory::class,
      'mailjet' => Bridge\Mailjet\MailjetTransportFactory::class,
      'message-bird' => Bridge\MessageBird\MessageBirdTransportFactory::class,
      'message-media' => Bridge\MessageMedia\MessageMediaTransportFactory::class,
      'mobyt' => Bridge\Mobyt\MobytTransportFactory::class,
      'novu' => Bridge\Novu\NovuTransportFactory::class,
      'ntfy' => Bridge\Ntfy\NtfyTransportFactory::class,
      'octopush' => Bridge\Octopush\OctopushTransportFactory::class,
      'one-signal' => Bridge\OneSignal\OneSignalTransportFactory::class,
      'orange-sms' => Bridge\OrangeSms\OrangeSmsTransportFactory::class,
      'ovh-cloud' => Bridge\OvhCloud\OvhCloudTransportFactory::class,
      'plivo' => Bridge\Plivo\PlivoTransportFactory::class,
      'primotexto' => Bridge\Primotexto\PrimotextoTransportFactory::class,
      'pushover' => Bridge\Pushover\PushoverTransportFactory::class,
      'pushy' => Bridge\Pushy\PushyTransportFactory::class,
      'redlink' => Bridge\Redlink\RedlinkTransportFactory::class,
      'ring-central' => Bridge\RingCentral\RingCentralTransportFactory::class,
      'sendberry' => Bridge\Sendberry\SendberryTransportFactory::class,
      'sevenio' => Bridge\Sevenio\SevenIoTransportFactory::class,
      'sipgate' => Bridge\Sipgate\SipgateTransportFactory::class,
      'simple-textin' => Bridge\SimpleTextin\SimpleTextinTransportFactory::class,
      'sinch' => Bridge\Sinch\SinchTransportFactory::class,
      'sms-biuras' => Bridge\SmsBiuras\SmsBiurasTransportFactory::class,
      'sms-factor' => Bridge\SmsFactor\SmsFactorTransportFactory::class,
      'sms-sluzba' => Bridge\SmsSluzba\SmsSluzbaTransportFactory::class,
      'sms77' => Bridge\Sms77\Sms77TransportFactory::class,
      'smsapi' => Bridge\Smsapi\SmsapiTransportFactory::class,
      'smsbox' => Bridge\Smsbox\SmsboxTransportFactory::class,
      'smsc' => Bridge\Smsc\SmscTransportFactory::class,
      'smsense' => Bridge\Smsense\SmsenseTransportFactory::class,
      'smsmode' => Bridge\Smsmode\SmsmodeTransportFactory::class,
      'spot-hit' => Bridge\SpotHit\SpotHitTransportFactory::class,
      'sweego' => Bridge\Sweego\SweegoTransportFactory::class,
      'telnyx' => Bridge\Telnyx\TelnyxTransportFactory::class,
      'termii' => Bridge\Termii\TermiiTransportFactory::class,
      'turbo-sms' => Bridge\TurboSms\TurboSmsTransportFactory::class,
      'twilio' => Bridge\Twilio\TwilioTransportFactory::class,
      'unifonic' => Bridge\Unifonic\UnifonicTransportFactory::class,
      'vonage' => Bridge\Vonage\VonageTransportFactory::class,
      'yunpian' => Bridge\Yunpian\YunpianTransportFactory::class,
      // @phpcs:enable
    ];

    $parentPackages = ['symfony/framework-bundle', 'symfony/notifier'];

    foreach ($texterFactories as $name => $class) {
      // FrameworkExtension.php:2908.
      if (FALSE === ContainerBuilder::willBeAvailable(\sprintf('symfony/%s-notifier', $name), $class, $parentPackages)) {
        continue;
      }

      $factory = (new ChildDefinition('notifier.transport_factory.abstract'))
        ->setClass($class)
        ->addTag('texter.transport_factory')
        ->setAutowired(TRUE)
        ->setAutoconfigured(TRUE);

      if ($name === 'fake-sms') {
        $factory->setArgument('$logger', new Reference('logger.channel.sms.fake'));
      }

      $container->setDefinition(\sprintf('notifier.transport_factory.%s', $name), $factory);
    }

    // If Fake was not defined, then remove the logger defined in services.yml.
    if (FALSE === $container->hasDefinition('notifier.transport_factory.fake-sms')) {
      $container->removeDefinition('logger.channel.sms.fake');
    }
  }

}
