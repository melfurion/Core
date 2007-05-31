#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "ext/standard/info.h"
#include "php_phorum.h"

static function_entry phorum_functions[] = {
    PHP_FE(phorum_ext_version, NULL)
    PHP_FE(phorum_ext_treesort, NULL)
    PHP_FE(phorum_get_url,  NULL)
    {NULL, NULL, NULL}
};

zend_module_entry phorum_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    PHP_PHORUM_EXTNAME,
    phorum_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_MINFO(phorum),
#if ZEND_MODULE_API_NO >= 20010901
    PHP_PHORUM_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PHORUM
ZEND_GET_MODULE(phorum)
#endif

PHP_MINFO_FUNCTION(phorum)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "Phorum acceleration support", "enabled");
    php_info_print_table_row(2, "Extension version", PHP_PHORUM_VERSION);
    php_info_print_table_row(2, "Support web site", "http://www.phorum.org/");
    php_info_print_table_end();
}

PHP_FUNCTION(phorum_ext_version)
{
    RETURN_STRING(PHP_PHORUM_VERSION, 1);
}

