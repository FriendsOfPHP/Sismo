<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Contrib;

use Sismo\Notifier\Notifier;
use Sismo\Commit;

/**
 * Notifies builds via the wallpaper (Mac only).
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class WallpaperNotifier extends Notifier
{
    private $dir;
    private $log;
    private $updatedAt;
    private $image;
    private $imagePath;
    private $imageOptions;

    public function __construct($options = array())
    {
        $this->initializeImageOptions($options);
        $this->initializeTempDirectory();
        $this->initializeLogFile();
        $this->initializeFonts();
    }

    public function notify(Commit $commit)
    {
        $slug   = $commit->getProject()->getSlug();
        $status = 'succeeded' == strtolower($commit->getStatus()) ? 'ok' : 'ko';

        $data = unserialize(file_get_contents($this->log));

        $data['last_update'] = date('Y-M-j H:i:s');
        $data['projects'][$slug][] = $status;

        // image will only show `max_number_bars` builds per project
        $data['projects'][$slug] = array_slice($data['projects'][$slug], -$this->get('max_number_bars'));

        file_put_contents($this->log, serialize($data));

        // if the last image was generated less than 15 seconds ago, don't generate
        //  another image. This prevents collisions when building a lot of projects
        if (null != $this->updatedAt && microtime(true) - $this->updatedAt < 15) {
            return;
        }

        $this->updateBackground($data);
    }

    /**
     * Generates a wallpaper with the latest data and updates desktop background
     *
     * @param array $data Array with the latest build history per project
     */
    private function updateBackground($data)
    {
        $this->updatedAt = microtime(true);

        $this->image = imagecreatetruecolor($this->get('image_width'), $this->get('image_height'));
        imageantialias($this->image, true);

        $this->initializeColors();
        imagefill($this->image, 0, 0, $this->get('background_color'));

        $numProject = 0;
        foreach ($data['projects'] as $project => $builds) {
            // the most recent build is always shown on top
            rsort($builds);

            $x1 = $this->get('horizontal_padding') + (($this->get('bar_width') + $this->get('horizontal_gutter')) * $numProject);
            $x2 = $x1 + $this->get('bar_width');

            // plot each project slug
            $this->addTextToImage(
                substr($project, 0, $this->get('max_number_letters')),
                $x1,
                $this->get('vertical_padding') - 0.2 * $this->get('font_size')
            );

            foreach ($builds as $i => $build) {
                $y1 = $this->get('vertical_padding') + $this->get('font_size') + (($this->get('bar_height') + $this->get('vertical_gutter')) * $i);
                $y2 = $y1 + $this->get('bar_height');
                $color = 'ok' == $build ? $this->get('success_color') : $this->get('failure_color');

                // plot a bar for each project build
                imageFilledRectangle($this->image, $x1, $y1, $x2, $y2, $color);
            }
            $numProject++;
        }

        $this->addTextToImage(
            'Last update: '.$data['last_update'],
            $this->get('horizontal_padding'),
            $this->get('image_height') - $this->get('font_size')
        );

        // Hack: two different images are needed to update the wallpaper
        // One holds the current wallpaper and the other is the new one
        // If you use just one image and modify it, the OS doesn't reload it
        if (file_exists($this->evenBackgroundImagePath)) {
            $this->imagePath = $this->oddBackgroundImagePath;
            unlink($this->evenBackgroundImagePath);
        } elseif (file_exists($this->oddBackgroundImagePath)) {
            $this->imagePath = $this->evenBackgroundImagePath;
            unlink($this->oddBackgroundImagePath);
        } else {
            $this->imagePath = $this->oddBackgroundImagePath;
        }

        imagepng($this->image, $this->imagePath);
        imagedestroy($this->image);

        // Wallpaper is reloaded via AppleScript
        $scriptPath = $this->dir.'/update-background.scpt';

        file_put_contents($scriptPath, <<<END
tell application "System Events"
    tell current desktop
        set picture to POSIX file "file://localhost/$this->imagePath"
    end tell
end tell
END
);
        system("osascript $scriptPath");
    }

    /**
     * Convenience method to add text to a GD image.
     *
     * @param string $text The string to be plotted
     * @param int    $x    The x coordinate where the string will be plotted
     * @param int    $y    The y coordinate where the string will be plotted
     */
    private function addTextToImage($text, $x, $y)
    {
        imagettftext($this->image, $this->get('font_size'), 0, $x, $y, $this->get('text_color'), $this->dir.'/instruction.ttf', $text);
    }

    /**
     * Convenience method to get any image option.
     *
     * @param string $id The name of the option
     */
    private function get($id)
    {
        return $this->imageOptions[$id];
    }

    /**
     * Convenience method to set the value of any image option.
     *
     * @param string $id    The name of the option
     * @param mixed  $value The new value of the option
     */
    private function set($id, $value)
    {
        $this->imageOptions[$id] = $value;
    }

    /**
     * Initializes the options that control wallpaper design
     *
     * @param array $userOptions The options given by the user,
     *                           which override default values
     */
    private function initializeImageOptions($userOptions)
    {
        $this->imageOptions = array_merge(array(
            'bar_width'          => 80,
            'bar_height'         => 10,
            'horizontal_gutter'  => 20,
            'vertical_gutter'    => 10,
            'horizontal_padding' => 20,
            'vertical_padding'   => 50,
            'image_width'        => 1920,
            'image_height'       => 1080,
            'background_color'   => '#161616',
            'text_color'         => '#CCCCCC',
            'success_color'      => '#267326',
            'failure_color'      => '#B30F00',
            'font_size'          => 10,
        ), $userOptions);

        $this->set('max_number_bars', ($this->get('image_height') - $this->get('vertical_padding') - (2 * $this->get('font_size')) - (2 * $this->get('vertical_gutter'))) / ($this->get('bar_height') + $this->get('vertical_gutter')));

        $this->set('max_number_letters', floor($this->get('bar_width') / (0.7 * $this->get('font_size'))));

        $this->image = null;
    }

    /**
     * This notifier requires a directory to save several files. This method
     * creates a hidden directory called `.wallpaperNotifier` if it doesn't exist.
     */
    private function initializeTempDirectory()
    {
        $this->dir = __DIR__.'/.wallpaperNotifier';

        if (!file_exists($this->dir)) {
            try {
                mkdir($this->dir);
            } catch (Exception $e) {
                throw new \RuntimeException(sprintf(
                    "Wallpaper notifier requires a directory to hold its contents\n"
                    ."'%s' directory couldn't be created",
                    $this->dir
                ));
            }
        }

        // Hack: two different images are needed to reload the desktop background
        // One holds the current wallpaper, the other one is the new background
        // If you have just one image and modify it, the OS doesn't reload it
        $this->evenBackgroundImagePath = realpath($this->dir).'/even-background.png';
        $this->oddBackgroundImagePath  = realpath($this->dir).'/odd-background.png';
    }

    /**
     * This method checks that the build history log file exists.
     * If it doesn't exist, the method creates and initializes it.
     */
    private function initializeLogFile()
    {
        $this->log = $this->dir.'/builds.log';

        if (!file_exists($this->log)) {
            $data = array(
                'last_update' => null,
                'projects'    => array(),
            );

            file_put_contents($this->log, serialize($data));
        }
    }

    /**
     * Checks if the needed font exists at the expected path.
     * If it doesn't exist, this method recreates it.
     *
     * PHP GD default fonts are ugly, so this notifier uses a custom and
     * free font called `instruction.ttf`.
     *
     * Downloaded from: http://www.pixelsagas.com/viewfont.php?fontid=101
     * Free License details: http://www.pixelsagas.com/freeware_license.php
     */
    private function initializeFonts()
    {
        if (file_exists($fontFile = $this->dir.'/instruction.ttf')) {
            return;
        }

        // `instruction.ttf` font encoded as Base64 string
        file_put_contents($fontFile, base64_decode('AAEAAAALAIAAAwAwT1MvMj+KkW4
        AAAE4AAAAVmNtYXC8mLvZAAAFQAAAA1ZnYXNw//8AAwAAeLwAAAAIZ2x5ZljbvzQAAAp0AA
        BnbGhlYWT6iL+pAAAAvAAAADZoaGVhDt8H8AAAAPQAAAAkaG10eDK4HRoAAAGQAAADsGxvY
        2H+NeTcAAAImAAAAdptYXhwAPEAagAAARgAAAAgbmFtZTtgFDwAAHHgAAAElHBvc3S/pUqg
        AAB2dAAAAkUAAQAAAAEAACef2BxfDzz1AAsIAAAAAADKHLgeAAAAAMtvuzb/uP5eBiYI/AA
        AAAYAAQAAAAAAAAABAAAI/P5gAAII3/+4/8QGJgABAAAAAAAAAAAAAAAAAAAA7AABAAAA7A
        BqAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDRQGQAAUACAWaBTMAAAEbBZoFMwAAA9EAZ
        gISAAACAAUAAAAAAAAAgAAAp1AAAEoAAAAAAAAAAEhMICAAQAAg+wIF3AAAAM0I/AGgIAAB
        EUEAAAAAAAQAAGQAAAAAAfwAAASwAAAEsABkBLAAZASwAAAEsAAABLAAAASwAAAEsABkBLA
        AZASwAGQEsABkBLAAAASwAGQEsAAABLAAZASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAA
        AEsAAABLAAAASwAAAEsAAABLAAZASwAGQEsADIBLAAAASwAMgEsAAABLAAAASwAAAEsAAAB
        LAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASw
        AAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAZASwAAAEsAB
        kBLAAAASwAAAEsADIBLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAA
        SwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEs
        AAABLAAAASwAAAEsAAABLAAZASwAAAEsAAABLAAZASwAAAEsAAAA/YAegSwAAAEsABkA/YA
        qgSwAMgGigBkAogAAAOwAK4D9gBXBooAZAKeAAACogA8A/YAAAP2AAAD9gAABLAAyASwAAA
        EAABdAfwAgQSwAGQD9gAAAs4AAAOwAGUGrAAAB6wAAAasAAAEsAAABLAAAASwAAAEsAAABL
        AAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwA
        AAEsAAABLAAAASwAAAEsAAABLAAAASwAAAD9gChBLAAAASwAAAEsAAABLAAAASwAAAEsAAA
        BLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAAS
        wAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAAP2AFcEsA
        AABLAAAASwAAAEsAAABLAAAASwAAAEsAAABLAAAAICABMEsAAABLAAAAQAAP4EAADbBAABm
        ASwAMgEsABkBLAAAAQAANkEsAAABLAAAASwAGQEsABkBLAAZASwAGQEsABkBLAAZAP2AEcD
        9gBGBAAA2ASwAGQCdwCuAncAZQSwAAAEsAAABLAAAASwAAAEsAAACN8AAAXZAAAD9gBNBDk
        ABgXrADAEtgB4A/YAVwP2ABED9gBCApn/uAP2AF4D9gBXA/YAVwQxAAAEOQAAAAAAAwAAAA
        MAAAAcAAEAAAAAAUwAAwABAAAAHAAEATAAAABGAEAABQAGAH4AoACsAK0A/wExAscCyQLdA
        34gFCAaIB4gIiAmIDogRCCkIKcgrCEWISIiAiIGIg8iEiIVIhoiHiIrIkgiZfAC+wL//wAA
        ACAAoAChAK0ArgExAsYCyQLYA34gEyAYIBwgICAmIDkgRCCjIKcgrCEWISIiAiIGIg8iESI
        VIhkiHiIrIkgiZPAB+wH////jAAD/wQAA/8D/j/37/fr97Pyg4LfgtOCz4LLgr+Cd4JTgNu
        A04DDfx9+83t3e2t7S3tHewwAA3sfeu96f3oQQ6QXpAAEAAABEAAAAQgAAAAAAAAAAAAAAA
        AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFAAAAAAAAAAAAAAAAAAAAAMA
        EAB3AOQABgIKAAAAAAEAAAEAAAAAAAAAAAAAAAAAAAABAAIAAAAAAAAAAgAAAAAAAAAAAAA
        AAAAAAAAAAAAAAAAAAAAAAAAAAAABAAAAAAADAAQABQAGAAcACAAJAAoACwAMAA0ADgAPAB
        AAEQASABMAFAAVABYAFwAYABkAGgAbABwAHQAeAB8AIAAhACIAIwAkACUAJgAnACgAKQAqA
        CsALAAtAC4ALwAwADEAMgAzADQANQA2ADcAOAA5ADoAOwA8AD0APgA/AEAAQQBCAEMARABF
        AEYARwBIAEkASgBLAEwATQBOAE8AUABRAFIAUwBUAFUAVgBXAFgAWQBaAFsAXABdAF4AXwB
        gAGEAAACEAIUAhwCJAJEAlgCcAKEAoACiAKQAowClAKcAqQCoAKoAqwCtAKwArgCvALEAsw
        CyALQAtgC1ALoAuQC7ALwA0gBwAGMAZABoANQAdgCfAG4AagDeAHQAaQAAAIYAmADlAHEA6
        ADpAGYAdQDfAOIA4QAAAOYAawB6AAAApgC4AH8AYgBtAOQAAADnAOAAbAB7ANUAAwCAAIMA
        lQAAAAAAygDLAM8A0ADMAM0AtwAAAL8AAADYAGUA1gDXAOoA6wDTAHcAzgDRAAAAggCKAIE
        AiwCIAI0AjgCPAIwAkwCUAAAAkgCaAJsAmQDAAMEAyABvAMQAxQDGAHgAyQDHAMIAAAAAAF
        YAVgBWAFYAgACsAQgBegHcAi4CSAJ2AqQDAgMyA0oDZAN6A5oD7gQaBFgEqgToBSgFbAWsB
        f4GQgZoBpIGugbmBw4HVgeoB+IIIghcCIgIugjkCSwJXAmMCbYJ+AoYCl4Kjgq+CuwLOAt2
        C74L5AwQDEQMhAzIDPwNQg1oDYgNrg3WDfAODg5IDogOwg7uDyAPSg+SD8IP8hAcEF4QfhD
        EEPQRJBFSEZ4R3BIkEkoSdhKqEuoTLhNiE6gT8hQMFFYUiBSyFRIVXBXQFiYWUhbMFvIXeh
        d6F5wXrhhIGEgYbhhuGG4YbhiMGL4Y2hj8GRwZHBkcGT4ZPhk+GT4ZhhnWGiYagBrkGzwbl
        BvcHCocchy6HQwdXh2kHewePh6OHsYfIh9qH7IgBCBgILAgyiEeIWAhoiHuIjoihCK2IwQj
        VCOkI/4kYiS6JRIlWiWoJfAmOCaKJtwnIidqJ7woDChEKKAo6CkwKYIp3iouKnQqyCsKK0w
        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
        HBgcGFRQXFgMnNxc2NzYzMhcWFzcXBxYXFhUUBwYHFwcnBgcGIyInJicHJzcmJyY1NDc2AV
        4pOh0dHR06KSgRCQkRKCk7HR0dHTooJxEKCRBFdlB2LDcsLSkqOS12UHUfEQwMER91UHYtO
        CorKis5LXZQdiAQDA0QAjAoEQgIESgpOh0dHR06KSgRCAkRJyg6Hh4cHDsBLnZRdh4QDgwR
        H3ZRdS44KisqKzgudVF2IBAMDBAgdlF1LjkqKiwrNwAAAQAAAAAD6AXcAD0AACUVFAYjIiY
        9ASMiJjU0NjsBNSMiJjU0NjsBASY1NDYzMhYXCQE+ATMyFhUUBwEzMhYVFAYrARUzMhYVFA
        YjAlg7KSk7yCk7OynIyCk7OymU/swoOykpOxQBGAEYFDspKTso/syUKTs7KcjIKTs7KchkK
        Ts7KWQ7KSk7yDspKTsB9DspKTs7Kf46AcYpOzspKTv+DDspKTvIOykpOwAAAAIAZP84ASwG
        pAANABsAAAEUBiMiJjURNDYzMhYVERQGIyImNRE0NjMyFhUBLDspKTs7KSk7OykpOzspKTs
        Dtik7OykCiik7Oyn5XCk7OykCiik7OykAAAACAKr+YANMBa4AEQBUAAABNjc2NTQnIyYnJi
        cmIyIGFRQTNjc2NTQlLgE1NDc2NycuATU0NzY3NjsBMhYdASMmJyYnIyIGFRQFHgEVFAcGB
        xYXFhUUBwYHNQYrASImPQEzFhcWAiE4KUkBAgEDFls7Q05eqXFISP7Ee2lZQ2coe2lZUoYI
        CQNCwjMFS0ZPD05eAR+Vb2UvNGwuN2V8mQYHA0LCMwVLRAFBECVBZQUFDg09Qh1ZSXv85gY
        /QGV9l0CSaJRZQRETQJJolFlPBwEhGcJRMy8EWUmEiUOZb6tvJxk4Pk1uq3BmAwEBIRnCUT
        MtAAAAAgDIBqQDIAdsAAsAFwAAABYVFAYjIiY1NDYzIBYVFAYjIiY1NDYzAVU7OykpOzspA
        bk7OykpOzspB2w7KSk7OykpOzspKTs7KSk7AAMAZP/uBiYFsAAXAC8AWgAAATIEFxYSFRQC
        BwYEIyIkJyYCNTQSNzYkAQ4BFRQWFx4BMzI2Nz4BNTQmJy4BIyIGASIuATU0PgEzMhYXFjM
        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
        pAAAAAAEAZAPoASwF3AANAAATNDYzMhYVERQGIyImNWQ7KSk7OykpOwV4KTs7Kf7UKTs7KQ
        AAAAABAGT+1AEsAMgADQAANzQ2MzIWFREUBiMiJjVkOykpOzspKTtkKTs7Kf7UKTs7KQACA
        GQD6AK8BdwADQAbAAATNDYzMhYVERQGIyImNQE0NjMyFhURFAYjIiY1ZDspKTs7KSk7AZA7
        KSk7OykpOwV4KTs7Kf7UKTs7KQEsKTs7Kf7UKTs7KQAAAgBkA+gCvAXcAA0AGwAAEzQ2MzI
        WFREUBiMiJjUBNDYzMhYVERQGIyImNWQ7KSk7OykpOwGQOykpOzspKTsFeCk7Oyn+1Ck7Oy
        kBLCk7Oyn+1Ck7OykAAAIAZP8GArwA+gANABsAADc0NjMyFhURFAYjIiY1ATQ2MzIWFREUB
        iMiJjVkOykpOzspKTsBkDspKTs7KSk7lik7Oyn+1Ck7OykBLCk7Oyn+1Ck7OykAAAABAEf+
        8gOwBZ0AMgAAASYnJjU0NjMyFhUUBwYHNjc2MzIWFRQGIyInJicWFwIDIwIDNjcGBwYjIiY
        1NDYzMhcWAeYILjlIPDlLNy4JYFFlHDE8Ny0gaFVeCGZqBiYGbGYJYFJlHDE7NywgaVUD/m
        FRZRwxOzcsIGlVXgguOUg8OUs3LguwZf40/f4CAgHMZbAKLTlIPDlLOC4AAAABAEb+8gOvB
        Z0AWwAAJTY3NjMyFhUUBiMiJyYnFhcWFRQGIyImNTQ3NjcGBwYjIiY1NDYzMhcWFyYnNjcG
        BwYjIiY1NDYzMhcWFyYnJjU0NjMyFhUUBwYHNjc2MzIWFRQGIyInJicWFwYCEF5VaCAtNzw
        xHGVRYAkuN0s5PEg5LgheVWkgLDc7MRxlUmAJZmYJYFJlHDE7NywgaVVeCC45SDw5SzcuCW
        BRZRwxPDctIGhVXghmZroLLjdLOTxIOS4IXlVpICw3OzEcZVFhCS44Szk8SDktCuKsq+IKL
        TlIPDlLOC4JYVFlHDE7NywgaVVeCC45SDw5SzcuC+KrrAAAAQDYAaUDKAP1AAsAABM0NjMy
        FhUUBiMiJtiue3qtr3p7rALOe6ytenqvrgADAGQAAARMAMgACwAXACMAADYWFRQGIyImNTQ
        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
        /djk86zszcUlAdjdA6zc0cQQdnEgzYThUnEk0Yzj++JxIM2E4VJxJNGM4AAACAFcAAQOfBN
        kABgALAAAJAhUBNQkBNSEXFQOf/Y8Ccfy4A0j8uANHAQQy/pn+nacB4VMB5PsojAGLAAACA
        FcAAgOfBNkABgALAAATARUBNQkBETUhFxVXA0j8uAJx/Y8DRwEE2f4cU/4fpwFjAWf70IwB
        iwAAAAAAACoB/gABAAAAAAAAADkAAAABAAAAAAABAAsAAAABAAAAAAACAAcAOQABAAAAAAA
        DABgAQAABAAAAAAAEAAsAAAABAAAAAAAFAC8AWAABAAAAAAAGAAsAAAABAAAAAAAJAA4ADw
        ABAAAAAAAMABkAhwADAAEEAwACAAwCTAADAAEEBQACABAAoAADAAEEBgACAAwAsAADAAEEB
        wACABAAvAADAAEECAACABAAzAADAAEECQAAAHIA3AADAAEECQABABYA3AADAAEECQACAA4B
        TgADAAEECQADADABXAADAAEECQAEABYA3AADAAEECQAFAF4BjAADAAEECQAGABYA3AADAAE
        ECQAJABwA+gADAAEECQAMADIB6gADAAEECgACAAwCTAADAAEECwACABACHAADAAEEDAACAA
        wCTAADAAEEDgACAAwCagADAAEEEAACAA4CLAADAAEEEwACABICOgADAAEEFAACAAwCTAADA
        AEEFQACABACTAADAAEEFgACAAwCTAADAAEEGQACAA4CXAADAAEEGwACABACagADAAEEHQAC
        AAwCTAADAAEEHwACAAwCTAADAAEEJAACAA4CegADAAEELQACAA4CiAADAAEICgACAAwCTAA
        DAAEIFgACAAwCTAADAAEMCgACAAwCTAADAAEMDAACAAwCTEluc3RydWN0aW9uIKkgKE5lYW
        xlIERhdmlkc29uKS4gMjAxMi4gQWxsIFJpZ2h0cyBSZXNlcnZlZFJlZ3VsYXJJbnN0cnVjd
        GlvbjpWZXJzaW9uIDEuMDBWZXJzaW9uIDEuMDAgRmVicnVhcnkgMjYsIDIwMTIsIGluaXRp
        YWwgcmVsZWFzZWh0dHA6XFx3d3cucGl4ZWxzYWdhcy5jb20AbwBiAHkBDQBlAGoAbgDpAG4
        AbwByAG0AYQBsAFMAdABhAG4AZABhAHIAZAOaA7EDvQO/A70DuQO6A6wASQBuAHMAdAByAH
        UAYwB0AGkAbwBuACAAqQAgACgATgBlAGEAbABlACAARABhAHYAaQBkAHMAbwBuACkALgAgA
        DIAMAAxADIALgAgAEEAbABsACAAUgBpAGcAaAB0AHMAIABSAGUAcwBlAHIAdgBlAGQAUgBl
        AGcAdQBsAGEAcgBJAG4AcwB0AHIAdQBjAHQAaQBvAG4AOgBWAGUAcgBzAGkAbwBuACAAMQA
        uADAAMABWAGUAcgBzAGkAbwBuACAAMQAuADAAMAAgAEYAZQBiAHIAdQBhAHIAeQAgADIANg
        AsACAAMgAwADEAMgAsACAAaQBuAGkAdABpAGEAbAAgAHIAZQBsAGUAYQBzAGUAaAB0AHQAc
        AA6AFwAXAB3AHcAdwAuAHAAaQB4AGUAbABzAGEAZwBhAHMALgBjAG8AbQBOAG8AcgBtAGEA
        YQBsAGkATgBvAHIAbQBhAGwAZQBTAHQAYQBuAGQAYQBhAHIAZABOAG8AcgBtAGEAbABuAHk
        EHgQxBEsERwQ9BEsEOQBOAG8AcgBtAOEAbABuAGUATgBhAHYAYQBkAG4AbwBBAHIAcgB1AG
        4AdABhAAIAAAAAAAD/JwCWAAAAAAAAAAAAAAAAAAAAAAAAAAAA7AAAAAEAAgADAAQABQAGA
        AcACAAJAAoACwAMAA0ADgAPABAAEQASABMAFAAVABYAFwAYABkAGgAbABwAHQAeAB8AIAAh
        ACIAIwAkACUAJgAnACgAKQAqACsALAAtAC4ALwAwADEAMgAzADQANQA2ADcAOAA5ADoAOwA
        8AD0APgA/AEAAQQBCAEMARABFAEYARwBIAEkASgBLAEwATQBOAE8AUABRAFIAUwBUAFUAVg
        BXAFgAWQBaAFsAXABdAF4AXwBgAGEAowCEAIUAvQCWAOgAhgCOAIsAnQCpAKQAigDaAIMAk
        wECAQMAjQCXAIgAwwDeAQQAngCqAPUA9AD2AKIArQDJAMcArgBiAGMAkABkAMsAZQDIAMoA
        zwDMAM0AzgDpAGYA0wDQANEArwBnAPAAkQDWANQA1QBoAOsA7QCJAGoAaQBrAG0AbABuAKA
        AbwBxAHAAcgBzAHUAdAB2AHcA6gB4AHoAeQB7AH0AfAC4AKEAfwB+AIAAgQDsAO4AugDXAN
        gA4QEFANsA3ADdAOAA2QDfALIAswC2ALcAxAC0ALUAxQCCAMIAhwCrAL4AvwC8APcBBgEHA
        QgBCQCMAJgAqACaAJkA7wClAJIAnACnAJQAlQEKAQsHdW5pMDBCMgd1bmkwMEIzB3VuaTAw
        QjkHdW5pMDJDOQRsaXJhBnBlc2V0YQRFdXJvCWFmaWk2MTM1Mgd1bmlGMDAxB3VuaUYwMDI
        AAAAAAAAB//8AAg=='));
    }

    /**
     * Transforms hexadecimal colors to the color format used by GD images.
     */
    private function initializeColors()
    {
        $rgb = $this->hex2rgb($this->get('background_color'));
        $this->set('background_color', imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

        $rgb = $this->hex2rgb($this->get('text_color'));
        $this->set('text_color', imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

        $rgb = $this->hex2rgb($this->get('success_color'));
        $this->set('success_color', imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

        $rgb = $this->hex2rgb($this->get('failure_color'));
        $this->set('failure_color', imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
    }

    /**
     * Convenience method to transform a color from hexadecimal to RGB.
     *
     * @param string $hex The color in hexadecimal format (full or shorthand)
     *
     * @return array The RGB color as an array with R, G and B components
     */
    private function hex2rgb($hex)
    {
        $hex = str_replace('#', '', $hex);

        // expand shorthand notation (#36A -> #3366AA)
        if (3 == strlen($hex)) {
            $hex = $hex{0}
            .$hex{0}
            .$hex{1}
            .$hex{1}
            .$hex{2}
            .$hex{2};
        }

        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }
}
