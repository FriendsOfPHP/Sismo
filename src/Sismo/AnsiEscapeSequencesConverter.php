<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo;

/**
 * Converts ANSI escape sequences to HTML.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AnsiEscapeSequencesConverter
{
    static public function convertToHtml($text)
    {
        // remove cursor movement sequences
        $text = preg_replace('#\033\[(K|s|u|2J|2K|\d+(A|B|C|D|E|F|G|J|K|S|T)|\d+;\d+(H|f))#', '', $text);

        return preg_replace_callback("#\033\[(.+?)m(?:\033\[(?:.+?)m)*(.+?)\033\[0m#s", function ($matches) {
            $options = explode(';', $matches[1]);
            $text = $matches[2];
            $classes = array();

            // bg and fg colors
            $colors = array('black', 'red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white');
            foreach ($options as $option) {
                if ($option >= 30 && $option < 40) {
                    $classes[] = sprintf('ansi_color_fg_%s', $colors[$option - 30]);
                } elseif ($option >= 40 && $option < 50) {
                    $classes[] = sprintf('ansi_color_bg_%s', $colors[$option - 40]);
                }
            }

            // options: bold => 1, underscore => 4, blink => 5, reverse => 7, conceal => 8
            if (in_array(4, $options)) {
                $classes[] = 'underlined';
            }

            if (in_array(1, $options)) {
                $text = sprintf('<strong>%s</strong>', $text);
            }

            if ($classes) {
                return sprintf('<span class="%s">%s</span>', implode(' ', $classes), $text);
            }

            return $text;
        }, $text);
    }
}
