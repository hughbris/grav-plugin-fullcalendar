<?php
namespace Grav\Plugin\Shortcodes;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class MonthCalendarShortcode extends Shortcode {
    public function init() {
        $this->shortcode->getHandlers()->add('monthcalendar', function(ShortcodeInterface $sc) {
            $s = $sc->getContent();
            $twig = $this->twig;
            $params = $sc->getParameters();
            $config = $this->config->get('plugins.fullcalendar');
            // icsfile is Parameter from shortcode:
            $icsfile = isset($params['icsfile']) ? $this->grav['twig']->processString($params['icsfile']) : '';
            $output = $twig->processTemplate('partials/monthcalendar.html.twig',
                [
                    'icsfile' => $icsfile,
                ]);
            return $output;
        });
    }
}
