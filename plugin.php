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

    private $_addresses = array();

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
        // Search for all email addresses in the output
        $pattern = '/[a-z0-9_.\-\+]{1,256}+@[a-z0-9\-\.]+\.([a-z]{2,4})/i';
        preg_match_all($pattern, $output, $matches, PREG_PATTERN_ORDER);

        foreach($matches['0'] as $match) {
            $split = explode('@', $match);
            foreach($split as $part) {
                $this->_addresses[$match][] = $part;
            }
        }

        // only replace when not called by ajax
        if(stripos($output, '</body>') && $this->_hasAddresses()) {
            $output = $this->_replaceEmailAddresses($output);
            $output = str_replace('</body>', $this->_addJS() . '</body>', $output);
        } else {
            $output = $this->_replaceEmailAddresses($output, false);
        }
        return $output;
    }

    /**
     * @param $output
     * @param bool $encode
     * @return mixed
     */
    private function _replaceEmailAddresses($output, $encode = true)
    {
        foreach($this->_addresses as $address => $part) {
            if($encode) {
                $replace = "<span class=\"mail_". substr(base64_encode($address), 0, 12) ."\"></span>";
            } else {
                $replace = '<a href="mailto:' . $address . '">' . $address . '</a>';
            }
            $output = str_replace($address, $replace, $output);
        }
        return $output;
    }

    private function _addJS()
    {
        if ($this->_hasAddresses()) {
            $output = '<script type="text/javascript">';
            $output .= 'var simpleMailEncryption = function() { ';
            foreach ($this->_addresses as $address => $part) {
                $identifier = 'mail_' . substr(base64_encode($address), 0, 12);
                $output .= "$('." . $identifier . "').each(function(idx, e) { console.log($(e).html()); $(e).html('<a href=\"mailto:" . $part[0] . "@" . $part[1] . "\">" . $part[0] . "@" . $part[1] . "</a>'); }); ";
            }
            $output .= '}; ';
            $output .= 'simpleMailEncryption(); ';
            $output .= '</script>';

            return $output;
        }

        return '';
    }

    /**
     * @return bool
     */
    private function _hasAddresses()
    {
        if(count($this->_addresses) > 0) {
            return true;
        }
        return false;
    }

}
