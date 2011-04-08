<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Tests;

use Sismo\AnsiEscapeSequencesConverter;

class AnsiEscapeSequencesConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertRemoveCursorMovementSequences()
    {
        $this->assertEquals('Foo', AnsiEscapeSequencesConverter::convertToHtml("\033[KFoo"));
    }

    public function testConvert()
    {
        $this->assertEquals('<span class="ansi_color_fg_red ansi_color_bg_red underlined"><strong>Foo</strong></span>', AnsiEscapeSequencesConverter::convertToHtml("\033[31;41;4;1mFoo\033[0m"));

        $this->assertEquals('<strong>Foo</strong>', AnsiEscapeSequencesConverter::convertToHtml("\033[1mFoo\033[0m"));
    }
}
