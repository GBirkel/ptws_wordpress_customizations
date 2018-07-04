<?php

define('PTWS_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
define('PTWS_SITE_URL', site_url());
define('DEBUG', false);
define('VERSION', '0.0.1');

function ptws_create_afgFlickr_obj() {
    global $pf;
    $pf = new afgFlickr(get_option('ptws_api_key'), get_option('ptws_api_secret') ? get_option('ptws_api_secret') : NULL);
    $pf->setToken(get_option('ptws_flickr_token'));
}


function ptws_error($error_msg) {
    return "<h3>Awesome Flickr Gallery Error - $error_msg</h3>";
}

/*
class ptws_parse_xml_string {

    // XML parser variables 
    var $parser; 
    var $name; 
    var $attr; 
    var $data  = array(); 
    var $stack = array(); 
    var $errors = array(); 
    var $keys; 
    var $path; 
    
    // function with the default parameter value 
    function ptws_parse_xml_string($content='<html></html>') { 
        $this->content = $content; 
        $this->parse(); 
    } 
    
    // parse XML data 
    function parse() { 
        $data = ''; 
        $this->parser = xml_parser_create(); 
        xml_set_object($this->parser, $this); 
        xml_set_element_handler($this->parser, 'startXML', 'endXML'); 
        xml_set_character_data_handler($this->parser, 'charXML'); 
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false); 

        $lines = explode("\n",$this->content); 
        foreach ($lines as $val) { 
            if (trim($val) == '') 
                continue; 
            $data = $val . "\n"; 
            if (!xml_parse($this->parser, $data)) { 
                $this->error(sprintf('XML error at line %d column %d', 
                xml_get_current_line_number($this->parser), 
                xml_get_current_column_number($this->parser))); 
            } 
        } 
    } 

    function startXML($parser, $name, $attr) {
        $this->stack[$name] = array(); 
        $keys = ''; 
        $total = count($this->stack)-1; 
        $i=0; 
        foreach ($this->stack as $key => $val)    { 
            if (count($this->stack) > 1) { 
                if ($total == $i) 
                    $keys .= $key; 
                else 
                    $keys .= $key . '|'; // The separator 
            } 
            else 
                $keys .= $key; 
            $i++; 
        } 
        if (array_key_exists($keys, $this->data))    { 
            $this->data[$keys][] = $attr; 
        }    else 
            $this->data[$keys] = $attr; 
        $this->keys = $keys; 
    } 

    function endXML($parser, $name) {
        end($this->stack); 
        if (key($this->stack) == $name) 
            array_pop($this->stack); 
    } 

    function charXML($parser, $data) {
        if (trim($data) != '') 
            $this->data[$this->keys]['data'][] = trim(str_replace("\n", '', $data)); 
    } 

    function error($msg) {
        array_push($this->errors, $msg);
    } 
} 
*/


// http://php.net/manual/en/class.simplexmliterator.php

function ptws_parse_gallery_xml($text) {
    $sxi = new SimpleXmlIterator($text);
    return ptws_parse_gallery_to_array($sxi)
}


function ptws_parse_gallery_to_array($sxi) {
    $a = array();
    for ($sxi->rewind(); $sxi->valid(); $sxi->next() ) {

        if ($sxi->key() == 'photo') {
            
        }

        if (!array_key_exists($sxi->key(), $a)) {
            $a[$sxi->key()] = array();
        }
        if ($sxi->hasChildren()) {
            $a[$sxi->key()][] = ptws_parse_gallery_to_array($sxi->current());
        } else {
            $a[$sxi->key()][] = strval($sxi->current());
        }
    }
    return $a;
}


?>