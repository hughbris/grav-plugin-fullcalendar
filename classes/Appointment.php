<?php
namespace Grav\Plugin\FullCalendar;

use Grav\Common\Grav;
use Grav\Plugin\Email\Email;
use Grav\Plugin\Email\Message;
use Symfony\Component\Mime\Part\DataPart;

class Appointment extends Email {
    protected $grav;

    public function __construct() {
        parent::__construct();
        $this->grav = Grav::instance();
    }

    public function buildAppointment(array $params, array $vars = []): Message {
        // base off https://github.com/getgrav/grav-plugin-email/blob/develop/classes/Email.php#L123
        $message = new Message();

        /** @var Language $language */
        $language = $this->grav['language'];
        /** @var Config $config */
        $config = $this->grav['config'];

        // Extend parameters with defaults.
        $defaults = [
            'bcc' => $config->get('plugins.email.bcc', []),
            'bcc_name' => $config->get('plugins.email.bcc_name'),
            'cc' => $config->get('plugins.email.cc', []),
            'cc_name' => $config->get('plugins.email.cc_name'),
            'charset' =>  $config->get('plugins.email.charset', 'utf-8'),
            'from' => $config->get('plugins.email.from'),
            'from_name' => $config->get('plugins.email.from_name'),
            'reply_to' => $config->get('plugins.email.reply_to', []),
            'reply_to_name' => $config->get('plugins.email.reply_to_name'),
            'to' => $config->get('plugins.email.to'),
            'to_name' => $config->get('plugins.email.to_name'),
        ];

        foreach ($defaults as $key => $value) {
            if (!key_exists($key, $params)) {
                $params[$key] = $value;
            }
        }
        if (!$params['to']) {
            throw new \RuntimeException($language->translate('PLUGIN_EMAIL.PLEASE_CONFIGURE_A_TO_ADDRESS'));
        }
        if (!$params['from']) {
            throw new \RuntimeException($language->translate('PLUGIN_EMAIL.PLEASE_CONFIGURE_A_FROM_ADDRESS'));
        }

        array_walk_recursive($params, function(&$value) {
            if (is_string($value)) {
                $value = $this->grav['twig']->processString($value);
            }
            });

        $email = $message->getEmail();
        // Process parameters.
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'subject':
                    if ($value) {
                        $message->subject($language->translate($value));
                    }
                    break;

                case 'to':
                case 'from':
                case 'cc':
                case 'bcc':
                case 'reply_to':
                    if ($recipients = $this->processRecipients($key, $params)) {
                        $key = $key === 'reply_to' ? 'replyTo' : $key;
                        $email->$key(...$recipients);
                    }
                    break;
            }
        }

        /** @var Twig $twig */
        $twig = $this->grav['twig'];
        $twig->init();
        $ics = $twig->processString($params['ical']);

        // from https://github.com/symfony/symfony/issues/47279#issuecomment-1232053603
        $attachment = new DataPart($ics, 'request.ics', 'text/calendar', 'quoted-printable');
        $attachment->asInline();
        $attachment->getHeaders()->addParameterizedHeader('Content-Type', 'text/calendar', ['charset' => 'utf-8', 'method' => 'REQUEST']);
        $email->attachPart($attachment);

        $this->grav['log']->info(serialize($params));
        return $message;
    }
}