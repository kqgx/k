<?php
function &load_class($class, $directory = 'libraries', $param = NULL)
{
    static $_classes = array();

    if (isset($_classes[$class]))
    {
        return $_classes[$class];
    }

    $name = FALSE;

    foreach (array(APPPATH, BASEPATH) as $path)
    {
        if (file_exists($path.$directory.'/'.$class.'.php'))
        {
            $name = 'CI_'.$class;

            if (class_exists($name, FALSE) === FALSE)
            {
                require_once($path.$directory.'/'.$class.'.php');
            }

            break;
        }
    }
    if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
    {
        $name = config_item('subclass_prefix').$class;

        if (class_exists($name, FALSE) === FALSE)
        {
            require_once(APPPATH.$directory.'/'.$name.'.php');
        }
    } elseif ($directory == 'core' && is_file(FCPATH.'core/M_'.$class.'.php')) {
        $name = config_item('subclass_prefix').$class;
        if (class_exists($name, FALSE) === FALSE)
        {
            require_once(FCPATH.'core/M_'.$class.'.php');
        }
    } else {
        $name = 'CI_'.$class;
    }
    if (class_exists($name, FALSE) === FALSE)
    {
        set_status_header(503);
        throw new Exception('Unable to locate the specified class: '.$name.'.php');
        exit(5); // EXIT_UNK_CLASS
    }
    is_loaded($class);
    $_classes[$class] = isset($param)
        ? new $name($param)
        : new $name();
    return $_classes[$class];
}

function &get_config(Array $replace = array())
{
    static $config;

    if (empty($config))
    {
        require CONFPATH.'config.php';
    }
    foreach ($replace as $key => $val)
    {
        $config[$key] = $val;
    }

    return $config;
}

function &get_mimes()
{
    static $_mimes;

    if (empty($_mimes))
    {
        if (file_exists(CONFPATH.'mimes.php'))
        {
            $_mimes = include(CONFPATH.'mimes.php');
        }
        else
        {
            $_mimes = array();
        }
    }

    return $_mimes;
}
