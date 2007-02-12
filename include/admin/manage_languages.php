<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2006  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

// TODO have a better way to differentiate between Phorum distribution
// TODO and addon files, so we won't index text strings from addon
// TODO files in here.

if(!defined("PHORUM_ADMIN")) return;

define('TOKEN_DEBUGGER', 0);

// Because sometimes the script can take a while, we set the
// PHP time limit to a high value to prevent execution timeouts.
set_time_limit(600);

include_once "./include/admin/PhorumInputForm.php";

// Get some form variables.
$action = isset($_POST['action']) ? $_POST['action'] : 'start';
$language = isset($_POST['language']) ? $_POST['language'] : $PHORUM["SETTINGS"]["default_language"];
$filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
$displayname = isset($_POST['displayname']) ? trim($_POST['displayname']) : '';

// Handle downloading a new language file.
if ($action == 'download_lang') 
{
    // Ditch HTML header we have so far (from the admin framework).
    ob_end_clean();

    // Send the new languagefile to the client. 
    $basename = preg_replace('/-.*$/', '', $filename);
    $fullfile = $basename . '-' . PHORUM . '.php';  
    header ("Content-Type: application/download; filename=$fullfile");
    header ("Content-Disposition: attachment; filename=\"$fullfile\"");
    $langfile = phorum_cache_get('updated_language', $filename);
    print $langfile;
    
    exit();
}

// Handle updating a language.
if ($action == 'update_lang') {
    $langinfo = phorum_get_language_info();
    return phorum_generate_language_file($language, $langinfo[$language], false);
}

// Handle generating a new language.
if ($action == 'generate_lang') {
    $filename = preg_replace('/\.php$/i', '', basename($filename));
    if ($filename == '') {
        phorum_admin_error("The basename may not be empty");
    } elseif (! preg_match('/^[\w_\.]+$/', $filename)) {
        phorum_admin_error(
            "The basename contains illegal characters. Please, keep the " .
            "filename simple by using only letters, numbers, underscores and " .
            "dots. You can't use hyphens, because those are used for " .
            "separating the basename from the Phorum version for which the " .
            "language file is used."
        );      
    } elseif ($displayname == '') {
        phorum_admin_error("The display name for the language may not be empty.");     
    } else {
        $filename .= "-" . PHORUM;
        return phorum_generate_language_file($filename, $displayname, true);
    }
}


// Handle start page.
$frm = new PhorumInputForm ("", "post", "Generate updated language file");

$frm->addmessage(<<<INTRO
  <font color="red">EXPERIMENTAL FEATURE<br/>
  Please backup your existing language file if you replace it with
  one generated by this maintenance tool. We feel pretty confident
  about it, but we wouldn't want you to loose data in case of bugs.</font>
  <hr size="0"/>
  
  <h2>Manage language files</h2>
  This is a tool which can be used for easy maintenance of
  language files for Phorum. It will collect all actual used 
  language strings from the Phorum software and generate an
  updated langage file for your language of choice based on 
  those strings. In the generated language file, missing and
  deprecated strings will be clearly marked, so you can 
  update the language file to match the running Phorum distribution.
INTRO
);

$frm->hidden("module", "manage_languages");
$frm->hidden("action", "update_lang");
$frm->addbreak("Update existing language file");
$frm->addrow("Generate updated version of an existing language file", 
             $frm->select_tag("language", phorum_get_language_info(), $language, 0));
$frm->show();


$frm = new PhorumInputForm("", "post", "Generate new language file");
$frm->addmessage(<<<INTRO
  In case there is no language file available for your language or 
  if you want to create a new language file all of your own, you can
  generate a new language file using the form below.
INTRO
);

$frm->hidden("module", "manage_languages");
$frm->hidden("action", "generate_lang");
$frm->addbreak("Generate a new language file");
$frm->addrow("The basename for the generated file", $frm->text_box('filename', $filename, 20));
$frm->addrow("The display name for the language", $frm->text_box('displayname', $displayname, 20));
$frm->show();

exit;


// ======================================================================
// Generating language files
// ======================================================================

function phorum_generate_language_file($lang, $displayname, $generate_new) 
{
    global $fullfile;
    
    $basename = preg_replace('/-.*$/', '', $lang);
    $fullfile = $basename . '-' . PHORUM . '.php';  
           
    // Get our default language file.   
    $DEFAULT = phorum_get_language('english');

    // Get the languagefile to update, unless generating a new language.
    $CURRENT = array();
    if (! $generate_new) {
        $CURRENT = phorum_get_language($lang);
    } else {
        $CURRENT['STORE']['language_hide'] = 0;
        $CURRENT['STORE']['language'] = urlencode("'" . addslashes($displayname) . "'");
    }
    
    // Keep a copy of the languagefile.
    $CURRENT_COPY = $CURRENT;

    // Collect all language strings from the distribution files.
    $language_strings = phorum_extract_language_strings();
    
    $frm = new PhorumInputForm ("", "post", "Download new " . htmlspecialchars($fullfile) . " language file");   
    $frm->hidden("module", "manage_languages");
    $frm->hidden("action", "download_lang");
    $frm->hidden("filename", $lang);
    
    if (! $generate_new) {

        $frm->addmessage(
          "<h2>Update language: " . htmlspecialchars($displayname) . "</h2>" .
          "Below you will see all the things that have been updated " .
          "to get to the new version of the language file. At the " .
          "bottom of the page you will find a download button to download " .
          "the updated language file. This language file has to be placed " .
          "in <b>include/lang/" . htmlspecialchars($lang) . ".php</b> to make it " .
          "available to Phorum (backup your old file first of course!). " .
          "If new language strings have been added, " .
          "they will be marked with '***' in the language file, so it's " .
          "easy for you to find them."
        );
        $frm->addbreak("Updates for the new language file");
    } else {
        $frm->addmessage(
          "<h2>Generate new language: " . htmlspecialchars($displayname) . "</h2>" .
          "A new language file has been generated. Below you will find " .
          "a download button to download the new file. In this file, you " .
          "can replace all language strings by strings which apply to " .
          "\"" . htmlspecialchars($displayname) . "\". After updating the new " .
          "file, you will have to place it in " .
          "<b>include/lang/" . htmlspecialchars($fullfile) . ".php</b>, " .
          "so Phorum can use it (backup your old file first of course!)."
        );
    }
    
    $notifies = 0;

    // Check for language strings that are missing.
    $missing = array();
    $count_missing = 0;
    foreach ($language_strings as $string => $data) {
        if ($string == 'TIME') continue; // This one is special.
        if (! isset($CURRENT["DATA"]["LANG"][$string])) {
            array_push($missing, $string);
            $translation = urlencode("'" . addslashes($string) . "'");
            if (isset($DEFAULT["DATA"]["LANG"][$string])) {
                $translation = $DEFAULT["DATA"]["LANG"][$string];
            }
            $CURRENT_COPY["DATA"]["LANG"][$string] = 
                urlencode("'***'. " . urldecode($translation));
            
            $count_missing++;
            if (! $generate_new) {
                $frm->addrow("MISSING ($count_missing)", $string);
                $notifies++;
            }
        } else {
            unset($CURRENT["DATA"]["LANG"][$string]);
        }
    }

    // Check for language strings that are deprecated.
    $deprecated = array();
    $count_deprecated = 0;
    if (! $generate_new) 
    {
        foreach ($CURRENT["DATA"]["LANG"] as $string => $translation) 
        {
            if ($string == 'TIME') continue; // This one is special.
            
            $count_deprecated++;
            $deprecated[$string] = true;

            // Only notify the deprecation if not already in deprecated state.
            if (! isset($CURRENT['STORE']['DEPRECATED'][$string])) {                
                $frm->addrow("DEPRECATED ($count_deprecated)", htmlspecialchars($string));
                $notifies++;
            }
        }
    }
    $CURRENT_COPY['STORE']['DEPRECATED'] = $deprecated;

    // Restore our full current language data from the copy.
    $CURRENT = $CURRENT_COPY;
    
    // Copy values from our default language to the current language.
    $copyfields = array(
        'long_date',
        'long_date_time',
        'short_date',
        'short_date_time',
        'locale',
        'thous_sep',
        'dec_sep',
    );
    foreach ($copyfields as $f) {
        if (! isset($CURRENT[$f])) {
            $CURRENT[$f] = $DEFAULT[$f];
            if (! $generate_new) {
                $frm->addrow("MISSING VARIABLE", "$f set to default " . 
                             htmlentities(urldecode($DEFAULT[$f])));
                $notifies++;
            }
        }
    }
    // Copy default values beneath DATA to the current language.
    $datafields = array('CHARSET', 'MAILENCODING', 'LANG_META');
    foreach ($datafields as $f) {
        if (! isset($CURRENT['DATA'][$f]) || $CURRENT['DATA'][$f] == '') {
            $CURRENT['DATA'][$f] = $DEFAULT['DATA'][$f];
            if (! $generate_new) {
                $frm->addrow("MISSING VARIABLE", "DATA->$f set to default " . 
                             htmlentities(urldecode($DEFAULT['DATA'][$f])));
                $notifies++;
            }
        }
    }
    
    // Copy default values for timezone information to the current language.
    foreach ($DEFAULT['DATA']['LANG']['TIME'] as $key => $val) {
        if (! isset($CURRENT['DATA']['LANG']['TIME'][$key])) {
            $CURRENT['DATA']['LANG']['TIME'][$key] = $val;
            
            if (! $generate_new) {
                $dflt = htmlentities(urldecode($DEFAULT['DATA']['LANG']['TIME'][$key]));
                $frm->addrow("MISSING TZINFO", "TZ $key set to default<br/>$dflt");
                $notifies++;
            }
        }
    }
    
    if ($generate_new) {
        $frm->addrow("COMPLETED", "A new language file has been generated for you");
    } elseif (! $notifies) {
        $frm->addrow("NONE", "There were no updates for the current \"$lang\" language file");
    }
    
    $frm->show();
    
    phorum_write_language_file($lang, $CURRENT);
}

function phorum_write_language_file($lang, $CURRENT)
{
    // Sort array keys.
    ksort($CURRENT['DATA']['LANG']);
    ksort($CURRENT['STORE']['DEPRECATED']);
    
    $langfile = 
        "<?php\n" .
        "\n" .
        $CURRENT['STORE']['keep_comment'] . "\n" .
        "\n" . 
        "// ============================================================\n" .
        "// General settings\n" .
        "// ============================================================\n" .        
        "\n" .
        "// The language name as it is presented in the interface.\n" .
        "\$language = " . urldecode($CURRENT['STORE']['language']) . ";\n" .
        "\n" .
        "// Uncomment this to hide this language from the user-select-box.\n" .
        ($CURRENT['STORE']['language_hide'] ? '' : '//') . "\$language_hide = 1;\n" .
        "\n" .
        "// Date formatting. Check the PHP-docs for the syntax of these\n" .
        "// entries (http://www.php.net/strftime). One tip: do not use\n" .
        "// %T for showing the time zone, as users can change their time zone.\n" .
        "\$PHORUM['long_date'] = " . urldecode($CURRENT['long_date']) . ";\n" .
        "\$PHORUM['long_date_time'] = " . urldecode($CURRENT['long_date_time']) . ";\n" .
        "\$PHORUM['short_date'] = " . urldecode($CURRENT['short_date']) . ";\n" .
        "\$PHORUM['short_date_time'] = " . urldecode($CURRENT['short_date_time']) . ";\n" .
        "\n" .
        "// The locale setting for enabling localized times/dates. Take a look\n" .
        "// at http://www.w3.org/WAI/ER/IG/ert/iso639.htm for the needed string.\n" .
        "\$PHORUM['locale'] = " . urldecode($CURRENT['locale']) . ";\n" .
        "\n" .
        "// Numeric separators used to format numbers.\n" .
        "\$PHORUM['thous_sep'] = " . urldecode($CURRENT['thous_sep']) . ";\n" .
        "\$PHORUM['dec_sep'] = " . urldecode($CURRENT['dec_sep']) . ";\n" .
        "\n" .
        "// The character set to use for converting html into safe valid text.\n" .
        "// Also used in the header template for the xml tag. For a list of\n" .
        "// supported character sets see: http://www.php.net/htmlentities\n" .
        "// You may also need to set a meta-tag with a character set in it.\n" .
        "\$PHORUM['DATA']['CHARSET'] = " . urldecode($CURRENT['DATA']['CHARSET']) . ";\n" .
        "\n" .
        "// The encoding used for outgoing mail messages.\n" .
        "\$PHORUM['DATA']['MAILENCODING'] = " . urldecode($CURRENT['DATA']['MAILENCODING']) . ";\n" .
        "\n" .
        "// Some languages need additional meta tags to set encoding, etc.\n" .
        "\$PHORUM['DATA']['LANG_META'] = " . urldecode($CURRENT['DATA']['LANG_META']) . ";\n" .
        "\n" .
        "// ============================================================\n" .
        "// Language translation strings\n" .
        "// ============================================================\n" .  
        "\n" .
        "\$PHORUM['DATA']['LANG'] = array(\n";
    
    // Add active language data to the array.
    foreach ($CURRENT['DATA']['LANG'] as $key => $val) {
        if ($key == 'TIME') continue;
        if (isset($CURRENT['STORE']['DEPRECATED'][$key])) continue;

        $langfile .= "    '$key' => " . urldecode($val) . ",\n";
    }
           
    // Add deprecated language data to the array.
    if (count($CURRENT['STORE']['DEPRECATED']))
    {
        $langfile .= 
            "\n" .
            "    // ============================================================\n" .
            "    // DEPRECATED:\n" .
            "    // These are all language strings which are not used anymore.\n" .
            "    // You might want to keep them to make this language file work\n" .
            "    // for versions of Phorum prior to version " . PHORUM . "\n" .
            "    // ============================================================\n" .
            "\n";
        
        foreach ($CURRENT['STORE']['DEPRECATED'] as $key => $dummy) {
            $langfile .= "    '$key' => " . urldecode($CURRENT['DATA']['LANG'][$key]) . ",\n"; 
        }
    }
    
    $langfile .=
        ");\n" .
        "\n" .  
        "// ============================================================\n" .
        "// Timezone description strings\n" .
        "// ============================================================\n" .
        "\n" .
        "\$PHORUM['DATA']['LANG']['TIME'] = array(\n";
    
    foreach ($CURRENT['DATA']['LANG']['TIME'] as $key => $val) {
        $pre = sprintf("    %6s", "'$key'");
        $langfile .= "$pre => " . urldecode($val) . ",\n";
    }
    
    $langfile .= 
        ");\n" .
        "\n" .
        "?>\n";

    phorum_cache_put('updated_language', $lang, $langfile);   
}


// ======================================================================
// Parsing language files
// ======================================================================

// Helper function for phorum_get_language() to be able to do
// some debugging output while getting all PHP tokens.
function token_shift(&$tokens)
{
    $token = array_shift($tokens);
    if (TOKEN_DEBUGGER > 1) {
        print '<div style="color: darkorange">';
        if (is_array($token)) {
            print "COMPLEX: " . token_name($token[0]) . " [" . htmlspecialchars($token[1]) . "]<br/>";
        } else {
            print "SIMPLE: [" . htmlspecialchars($token) . "]<br/>";
        }
        print '</div>';
    }
    return $token;
}

function token_skip_whitespace(&$tokens)
{
    while ($tokens[0][0] == T_WHITESPACE) {
        array_shift($tokens);

    }
}

function token_get_string(&$tokens, $string = NULL)
{
    $levels = 0;
    
    while (count($tokens)) 
    {
        $token = token_shift($tokens);
        
        if (is_array($token)) 
        {
            switch ($token[0]) 
            {
                case T_COMMENT:
                    if (strstr($token[1], 'DEPRECATED')) {
                        global $in_deprecated;
                        $in_deprecated = true;
                    }
                    break;
                    
                // Tokens which we handle in scalar token code.
                case T_DOUBLE_ARROW:
                    $token = '=>';
                    break;
                case T_CURLY_OPEN:
                    $token = '{';
                    break;
                    
                case T_WHITESPACE:
                case T_ENCAPSED_AND_WHITESPACE:
                case T_CONSTANT_ENCAPSED_STRING: 
                case T_NUM_STRING:
                case T_STRING:
                case T_ARRAY:
                case T_LNUMBER:
                case T_VARIABLE:
                case T_CHARACTER:
                    $string .= $token[1];
                    break;
                default:
                    die ("Unhandled complex " . token_name($token[0]) . " token in token_get_string: " .
                         htmlspecialchars($token[1]));
                    break;
            }
        } 
        
        if (is_scalar($token)) 
        {   
            $oldlevels = $levels;
            
            // Keep track of nested brackets and curlies. 
            if ($token == '(' || $token == '{' || $token == '[') {
                $levels++;
            } elseif ($levels && ($token == ')' || $token == '}' || $token == ']')) {
                $levels--;
            }
               
            if ($levels || $oldlevels) {
                $string .= $token;
            } else {
                // Tokens which end a string.
                if ($token == ';' || $token == '=' || 
                    $token == '=>' || $token == ',' ||
                    $token == ')') {
                    $string = trim($string);
                    return array($string, $token);
                } else {
                    $string .= $token;
                }
            }
        }
    }
}

// This function retrieves all info from a language file, by directly 
// parsing its tokens. We can't simply load the language file, because
// we have to extract any PHP code intact from it. By loading, all
// PHP code would be interpreted.
function phorum_get_language($lang)
{
    $path = "./include/lang/$lang.php";
    $PHORUM = array();
    $DEPRECATED = array();
    $keep_comment = '';
    if (! file_exists($path)) {
        die("Cannot locate language module in $path");
    }
  
    // Read the language file. Keep track of comments that
    // we want to keep (those starting with '##').
    $file = '';
    $fp = fopen($path, "r");
    if (! $fp) die("Cannot read language file $path");
    while (($line = fgets($fp))) {
        $file .= $line;
        if (substr($line, 0, 2) == '##') {
            $keep_comment .= $line;
        }
    }
    fclose($fp);
    
    // Split the contents of the language file into PHP tokens.
    $tokens = token_get_all($file);
    
    // Parse the PHP tokens.
    while (count($tokens))
    {
        // Extract all variables. The rest is ignored.
        $token = token_shift($tokens);
        if (is_array($token))
        {
            if ($token[0] == T_VARIABLE) {
                list($varname,$endedby) = token_get_string($tokens, $token[1]);
                if ($endedby != '=') break; // We want only the assignments.
                
                // Peek at the following code, to see what type of variable we're
                // handling. Scalar or array.
                token_skip_whitespace($tokens);
                if ($tokens[0][0] == T_ARRAY) 
                {
                    global $in_deprecated;
                    $in_deprecated = false;
                    
                    // Handle opening bracket for the array.
                    token_shift($tokens);
                    token_skip_whitespace($tokens);
                    $token = token_shift($tokens);
                    if ($token != '(') {
                        die("$path: Expected array opening bracket for array " .
                            htmlspecialchars($varname));
                    }

                    while (count($tokens))
                    {   
                        // Get key
                        list($key, $endedby) = token_get_string($tokens);
                        if ($endedby != '=>') {
                            die("$path: Expected double arrow (=>) for key " .
                                htmlspecialchars($key) . " in array " . 
                                htmlspecialchars($varname) . ", but got $endedby");
                        }

                        // Get value
                        list($val, $endedby) = token_get_string($tokens);
                        
                        if ($endedby != ',' && $endedby != ')') {
                            die("$path: Expected ending comma or bracket for key " .
                                htmlspecialchars($key) . " in array " . 
                                htmlspecialchars($varname) . ", but got $endedby");
                        }     
                        
                        // Put the data in the environment.
                        $fullvar = $varname . '[' . $key . ']';
                        eval("$fullvar = '" . urlencode($val) . "';");

                        // Keep track of data flagged deprecated.
                        if ($in_deprecated) {
                            eval("\$DEPRECATED[$key] = true;");
                        }
                        
                        // Last key/value pair?
                        if ($endedby == ')') break;                           
                        token_skip_whitespace($tokens);
                        if ($tokens[0] == ')') {
                            array_shift($tokens);
                            break;
                        }
                    }
                } else {
                    list($varvalue,$endedby) = token_get_string($tokens);
                    eval("$varname = '" . urlencode($varvalue) . "';");
                }
            }
        }
    }
   
    if ($keep_comment == '') {
        $keep_comment = <<<HELP
## For adding information to the start of this language file,
## you can use comments starting with "##". Those comments will
## be kept intact when a new language file is generated by the 
## language file maintenance software.
HELP;
    }
    
    // These aren't inside $PHORUM, but we put them there so we have
    // access to them later on.
    $PHORUM['STORE']['language_hide'] = $language_hide;
    $PHORUM['STORE']['language'] = $language;
    $PHORUM['STORE']['keep_comment'] = $keep_comment;
    $PHORUM['STORE']['DEPRECATED'] = $DEPRECATED;

    if (TOKEN_DEBUGGER){
        print_var($PHORUM);
    }
    return $PHORUM;
}


// ======================================================================
// Extracting language strings from distribution files
// ======================================================================

function phorum_extract_language_strings()
{
    global $extract_strings;
    $extract_strings = array();
    phorum_extract_language_strings_recurse(".");
    return $extract_strings;
}

// This function processes directories recursively to search
// for language strings.
function phorum_extract_language_strings_recurse($path)
{
    global $extract_strings;

    $dh = opendir($path);
    while (($f = readdir($dh))) 
    {
        $file = "$path/$f";
        
        $ext = null;
        if (preg_match('/\.(\w+)$/', $f, $m)) $ext = $m[1];

        // Skip what we do not want to index.
        if ($f == "." || $f == "..") continue; // this and parent dir
        if ($f == ".svn") continue;            // SVN data directories
        if ($f == "lang") continue;            // language files
        if ($f == "mods") continue;            // mods
        if ($f == "docs") continue;            // documentation 
        if ($f == "cache") continue;           // the cache directory
        

        if (preg_match('/\.(php|tpl)$/', $file)) {
            $fp = fopen($file, "r");
            if (! $fp) die("Can't read file '$file'");
            while (($line = fgets($fp, 1024))) {
                $strings = array();
                if (preg_match_all('/LANG->([\w_-]+)/', $line, $m, PREG_SET_ORDER)) {
                    $strings = array_merge($strings, $m);
                }
                if (preg_match_all('/\$PHORUM\[["\']DATA["\']\]\[["\']LANG["\']\]\[["\']([^"\']+)["\']\]/', $line, $m, PREG_SET_ORDER)) {              
                    $strings = array_merge($strings, $m);
                }
                foreach ($strings as $string) {
                    if (! isset($extract_strings[$string[1]])) {
                        $extract_strings[$string[1]] = array('files'=>array());
                    }
                    $extract_strings[$string[1]]['files'][$file]++;
                    $extract_strings[$string[1]]['source'][$string[0]]++;

                }
            }
            fclose($fp);
        }
        
        if (is_dir($file)) {
            phorum_extract_language_strings_recurse($file);
        }
    }
    closedir($dh);
}
?>
