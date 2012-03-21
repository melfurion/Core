#!/usr/bin/php
<?php
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}
print "\n";
print "------------------------------------------------------------------\n";
print "Gathering hook information from the source code ...\n";

// Find files that might contain documentation for hooks.
chdir(dirname(__FILE__) . "/..");
$files = `find . -type f -name '*.php' -exec grep -l "\[hook\]" {} \;`;

// Storage for gathering the documentation.
$hookdocs = array();

// Process all files.
foreach ( explode("\n", $files) as $file) {
    if ($file == '') continue;
    if ($file == './scripts/generateHOOKdocs.php') continue;

    print "> $file\n";

    $fp = fopen($file, 'r');
    if (!$fp) die("Reading file $file failed!\n");

    $hook    = NULL;
    $doc     = NULL; 
    $section = NULL;
    while ($line = fgets($fp))
    {
        if (preg_match('/^\s*\*\//', $line)) {
            if ($hook !== NULL)
            {
                if ($doc['category'] === NULL) {
                    die("$file: no category defined for hook $hook\n");
                }
                if (empty($hookdocs[$doc['category']])) {
                    $hookdocs[$doc['category']] = array();
                }
                $hookdocs[$doc['category']][$doc['hook']] = $doc;
                $hook = NULL;
            }
            continue;
        }

        if ($hook === NULL) {
            if (preg_match('/^\s*\*\s*\[hook\]/', $line)) {
                $line = fgets($fp);
                if (preg_match('/^\s*\*\s*(\S+)/', $line, $m)) {
                    print "  > hook " . $m[1] . "\n";
                    $hook = $m[1];
                    $doc  = array(
                        "hook"        => $m[1],
                        "category"    => NULL,
                        "description" => NULL,
                        "when"        => NULL,
                        "input"       => NULL,
                        "output"      => NULL,
                    );
                    $section = NULL;
                }
            }
        } else {
            if (preg_match('/^\s*\*\s*\[(description|category|when|input|output)\]\s*(.*)\s*$/', $line, $m)) {
                $section = $m[1];
                $doc[$section] .= $m[2] . "\n";
            } elseif ($section !== NULL){
                $doc[$section] .= preg_replace('/^\s*\*\s*/', '', $line);
            }
        }
    }
}

print "------------------------------------------------------------------\n";
print "Generating documentation ...\n";

$output = dirname(__FILE__) . "/../docs/docbook/part_hooks.xml";
$fp = fopen($output, "w");
if (!$fp) die("Writing to $output failed.\n");

fputs($fp, "
<!-- Warning: this part was generated by scripts/generateHOOKdocs.php -->
<!-- Warning: do not do manual changes to this file. -->

<chapter id=\"hooks\">
  <title>Module hooks</title>
  <section>
    <title>Introduction</title>
    <para>
      To satisfy the webmaster that needs every bell and whistle,
      or those that want to make their web site unique, the Phorum
      team created a very flexible hook &amp; module system.
      The hooks allow a webmaster to create modules for doing things
      like using external authentication, altering message data before
      it is stored, adding custom information about users or messages,
      ec. Almost anything you can think of can be implemented through
      the hook &amp; module system.
    </para>
    <para>
      This chapter describes all the hooks that are available within
      the Phorum code. It is mainly targeted at developers that want
      to write modules.
    </para>
  </section>
");

foreach ($hookdocs as $category => $hooks) {
    $category = htmlspecialchars(trim($category));

    fputs($fp, "  <section>\n");
    fputs($fp, "    <title>$category</title>\n");

    foreach ($hooks as $name => $hook) {
        $name = htmlspecialchars(trim($name));
        $desc = htmlspecialchars($hook['description']);
        $when = htmlspecialchars($hook['when']);
        fputs($fp, "    <section>\n");
        fputs($fp, "      <title>$name</title>\n");
        fputs($fp, "      <para>\n");
        fputs($fp, "        $desc");
        fputs($fp, "      </para>\n");
        fputs($fp, "      <para>\n");
        fputs($fp, "        <emphasis role=\"bold\">\n");
        fputs($fp, "          Call time:\n");
        fputs($fp, "        </emphasis>\n");
        fputs($fp, "      </para>\n");
        fputs($fp, "      <para>\n");
        fputs($fp, "        $when\n");
        fputs($fp, "      </para>\n");
        fputs($fp, "    </section>\n");
    }

    fputs($fp, "  </section>\n");
}

fputs($fp, "
</chapter>
");

?>
