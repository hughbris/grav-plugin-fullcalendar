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

            $options = array();
            if(isset($params['ical'])) { // NB changed from 'icsfile'
                $options['ical'] = $this->grav['twig']->processString($params['ical']); // not sure why Twig process is needed here
            }
            elseif(isset($params['gcal'])) {
                // TODO: check for existence of google_api_key here (or in template)
                $options['gcal'] = $params['gcal'];
            }

            $output = $twig->processTemplate('partials/monthcalendar.html.twig', $options);
            return $output;
        });
    }
}
