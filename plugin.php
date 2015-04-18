<?php
/**
 * koken-simple-mail-encryption is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @author    Marcel Hauri <marcel@hauri.me>
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

class KokenSimpleMailEncryption extends KokenPlugin {

    public function __construct()
    {
        $this->register_filter('site.output', 'render');
    }

    /**
     * @param $output
     * @return mixed
     */
    public function render($output)
    {
        $addresses = array();

        // Search for all email addresses in the output
        $pattern = '/[a-z0-9_.\-\+]{1,256}+@[a-z0-9\-\.]+\.([a-z]{2,4})/i';
        preg_match_all($pattern, $output, $matches, PREG_PATTERN_ORDER);

        foreach($matches['0'] as $match) {
            $split = explode('@', $match);
            foreach($split as $part) {
                $addresses[$match][] = $part;
            }
        }

        // Check if there are any plain text mail addresses
        if($addresses > 0) {

            // Replace amail addresses with encrypted part
            $output = $this->replaceEmailAddresses($addresses, $output);
        }
        return $output;
    }

    /**
     * @param $addresses
     * @param $output
     * @return mixed
     */
    protected function replaceEmailAddresses($addresses, $output)
    {
        foreach($addresses as $address => $part) {
            $replace = "<script type=\"text/javascript\">document.write('<a href=\"mailto:' + '" . $part[0] . "' + '@' + '" . $part[1] . "' + '\">' + '" . $part[0] . "' + '@' + '" . $part[1] . "' + '</a>');</script>";
            $output = str_replace($address, $replace, $output);
        }
        return $output;
    }
}