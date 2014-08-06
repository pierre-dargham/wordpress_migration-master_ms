<?php
define('ABSPATH', realpath(dirname(__FILE__)));
require ABSPATH . '/config.php';
require ABSPATH . '/lang.php';
require ABSPATH . '/lib.php';

$display = NULL;

if('cli' === CFG_DISPLAY) {
    // CLI
    $param = get_cli_param();
}
else {
    // CGI
    $param = $_POST;
}

$message = array();
if(!empty($param)) {
    clean();
    // Gestion des champs obligatoires
    if(empty($param['base'])) {
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_REQUIRED_FIELD, STR_LIBELLE_BASE);
    }
    if(empty($param['old_domain'])) {
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_REQUIRED_FIELD, STR_LIBELLE_OLD_DOMAIN);
    }
    else {
        $host = !empty($param['host']) ? $param['host'] : CFG_HOST;
        $user = !empty($param['user']) ? $param['user'] : CFG_USER;
        if(!empty($param['empty_pass']))
            $pass = '';
        else
            $pass = !empty($param['pass']) ? $param['pass'] : CFG_PASS;
        if(@mysql_connect($host, $user, $pass)) {
            if(@mysql_select_db($param['base'])) {
                mysql_query('SET NAMES UTF8');

                $multisite = is_multisite();

                $blogs_id = get_blogs_id();
                foreach($blogs_id as $blog_id) {
                    update_blog($blog_id, $message);
                }

                // Le multisite
                if($multisite) {
                    // Blogs
                    update($param['prefix'].'blogs', array('blog_id', 'domain'), $message);
                    update($param['prefix'].'blogs', array('blog_id', 'path'), $message, TRUE);
                    // Site
                    update($param['prefix'].'site', array('id', 'path'), $message);
                    update($param['prefix'].'site', array('id', 'domain'), $message);
                    // Sitemetas
                    update($param['prefix'].'sitemeta', array('meta_id', 'meta_value'), $message);
                }



                if(empty($message))
                    $message['warning'][] = STR_ERROR_WARNING_MIGRATION_DONE;
            }
            else
                $message['fatal'][] = sprintf(STR_ERROR_FATAL_BASE, $param['base']);
        }
        else
            $message['fatal'][] = STR_ERROR_FATAL_CONNEXION;
    }
}

if(file_exists(ABSPATH . '/template_' . CFG_DISPLAY . '.php')) {
    require ABSPATH . '/template_' . CFG_DISPLAY . '.php';
}
