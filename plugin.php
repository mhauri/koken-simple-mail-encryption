<?php

class KokenSimpleMailEncryption extends KokenPlugin {

    protected $addresses = array();
    protected $encrypted = array();
    protected $vars = array();

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
        preg_match_all('/[A-Za-z0-9_-]+@[A-Za-z0-9_-]+\.([A-Za-z0-9_-][A-Za-z0-9_]+)/', $output, $matches, PREG_PATTERN_ORDER);
        foreach($matches['0'] as $match) {
            $this->setAddress($match);
        }
        // Check if there are any plain text mail addresses
        if($this->getAddressesCount() > 0) {
            // Encrypt email addresses
            $this->encryptEmails();

            // Replace amail addresses with encrypted part
            $output = $this->replaceEmailAddresses($output);

            // Add JS part at the end
            $output = str_replace('</body>', $this->emailJS() . '</body>', $output);
        }
        return $output;
    }

    /**
     * @return string
     */
    public function emailJS()
    {
        $output = "<script type=\"text/javascript\">";
        $output .= "var ";
        $vars = "";
        foreach ($this->getVars() as $key => $var) {
            $vars .= $key .' = "' . $var . '", ';
        }
        $output .= substr($vars, 0, -2) . ';';
        foreach ($this->getEncrypted() as $enc) {
            $key = array_keys($enc['parts']);
            $output .= "\n$('." . $enc['id'] . "').each(function() { $(this).html('<a href=\"mailto:' + " . $key['0'] . " + '@' + " . $key['1'] . " + '\">' + " . $key['0'] . " + '@' + " . $key['1'] . " + '</a>');});";
        }
        $output .= "</script>\n";
        return $output;

    }

    /**
     * @return array
     */
    public function getEncrypted()
    {
        return $this->encrypted;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @return int
     */
    public function getAddressesCount()
    {
        return count($this->addresses);
    }

    /**
     * @param $address
     */
    protected function setAddress($address)
    {
        $this->addresses[] = $address;
    }

    /**
     * @param $var
     */
    protected function setVar($key, $value)
    {
        $this->vars[$key] = $value;
    }

    /**
     * @param $key
     * @param $content
     */
    protected function setEncrypted($key, $content)
    {
        if(is_array($content)) {
            $this->encrypted[$key] = $content;
        } else {
            $this->encrypted[$key] = array($content);
        }

    }

    /**
     * encrypt mail addresses
     */
    protected function encryptEmails()
    {
        $addresses = $this->addresses;
        foreach($addresses as $address) {
            $parts = $this->splitAddressParts($address);
            $this->setEncrypted($address, array('id' => 'mail_' . substr(sha1($address), 0, 6), 'parts' => $parts));
        }
    }

    /**
     * @param $address
     * @return array
     */
    protected function splitAddressParts($address)
    {
        $output = array();
        $split = explode('@', $address);
        foreach($split as $part) {
            $key = 'enc_' . substr(sha1($part), 0, 6);
            $output[$key] = $part;
            $this->setVar($key, $part);
        }
        return $output;
    }

    /**
     * @param $output
     * @return mixed
     */
    protected function replaceEmailAddresses($output)
    {
        $addresses = $this->getEncrypted();
        foreach($addresses as $address => $parts) {
            $replace = '<span class="' . $parts['id'] . '"></span>';
            $output = str_replace($address, $replace, $output);
        }
        return $output;
    }
}