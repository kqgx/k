<?php

class M_Loader extends CI_Loader {
    
    protected $_ci_models =	array();
    
    public function __get($key){
    }
    
    public function initialize()
    {
        $this->_ci_library_paths =	array(FCPATCH, APPPATH, BASEPATH);
        $this->_ci_model_paths   =	array(FCPATCH, APPPATH);
        $this->_ci_helper_paths  =	array(FCPATCH, APPPATH, BASEPATH);
        $this->_ci_autoloader();
    }

    protected function _ci_init_library($class, $prefix, $config = FALSE, $object_name = NULL)
    {
        if ($config === NULL)
        {
            $config_component = $this->_ci_get_component('config');

            if (is_array($config_component->_config_paths))
            {
                $found = FALSE;
                foreach ($config_component->_config_paths as $path)
                {
                    if (is_file($path.'config/'.strtolower($class).'.php'))
                    {
                        include($path.'config/'.strtolower($class).'.php');
                        $found = TRUE;
                    }
                    elseif (is_file($path.'config/'.ucfirst(strtolower($class)).'.php'))
                    {
                        include($path.'config/'.ucfirst(strtolower($class)).'.php');
                        $found = TRUE;
                    }
                    if ($found === TRUE)
                    {
                        break;
                    }
                }
            }
        }

        $class_name = $prefix.$class;

        if ( ! class_exists($class_name, FALSE))
        {
            log_message('error', 'Non-existent class: '.$class_name);
            show_error('Non-existent class: '.$class_name);
        }
        if (empty($object_name))
        {
            $object_name = strtolower($class);
            if (isset($this->_ci_varmap[$object_name]))
            {
                $object_name = $this->_ci_varmap[$object_name];
            }
        }

        $CI =& get_instance();
        if (isset($CI->$object_name))
        {
            if ($CI->$object_name instanceof $class_name)
            {
                log_message('debug', $class_name." has already been instantiated as '".$object_name."'. Second attempt aborted.");
                return;
            }
            show_error("Resource '".$object_name."' already exists and is not a ".$class_name." instance.");
        }

        $this->_ci_classes[$object_name] = $class;

        $CI->$object_name = isset($config)
            ? new $class_name($config)
            : new $class_name();
    }

    protected function _ci_autoloader()
    {
        include(CONFPATH.'autoload.php');

        if ( ! isset($autoload))
        {
            return;
        }

        if (isset($autoload['packages']))
        {
            foreach ($autoload['packages'] as $package_path)
            {
                $this->add_package_path($package_path);
            }
        }

        if (count($autoload['config']) > 0)
        {
            foreach ($autoload['config'] as $val)
            {
                $this->config($val);
            }
        }

        foreach (array('helper', 'language') as $type)
        {
            if (isset($autoload[$type]) && count($autoload[$type]) > 0)
            {
                $this->$type($autoload[$type]);
            }
        }
        
        if (isset($autoload['drivers']))
        {
            $this->driver($autoload['drivers']);
        }
        
        if (isset($autoload['libraries']) && count($autoload['libraries']) > 0)
        {
            if (in_array('database', $autoload['libraries']))
            {
                $this->database();
                $autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
            }

            $this->library($autoload['libraries']);
        }
    }
    
    public function model($model)
    {
        if (empty($model))
        {
            return $this;
        }
            
        $model = ucfirst($model);
        
        $path = '';

        if (($last_slash = strrpos($model, '/')) !== FALSE)
        {
            $path = substr($model, 0, ++$last_slash);
            $model = ucfirst(substr($model, $last_slash));
        }

        $class =  ($path ? str_replace('/', '_', $path): '') . $model.'_model';
        
        if (in_array($class, $this->_ci_models, TRUE))
        {
            return $this;;
        }

        $CI =& get_instance();
        if (isset($CI->$class))
        {
            throw new RuntimeException('The model name you are loading is the name of a resource that is already being used: '.$class);
        }

        if ( ! class_exists('CI_Model', FALSE))
        {
            $app_path = APPPATH.'core'.DIRECTORY_SEPARATOR;
            if (file_exists($app_path.'Model.php'))
            {
                require_once($app_path.'Model.php');
                if ( ! class_exists('CI_Model', FALSE))
                {
                    throw new RuntimeException($app_path."Model.php exists, but doesn't declare class CI_Model");
                }
            }
            elseif ( ! class_exists('CI_Model', FALSE))
            {
                require_once(FCPATH.'core/M_Model.php');
            }
        }

        if ( ! class_exists($class, FALSE))
        {
            foreach ($this->_ci_model_paths as $mod_path)
            {   
                if( file_exists($file = $mod_path.'models/'.$path . $model.'/model.php'))
                {
                    require_once($file);
                    if ( ! class_exists($class, FALSE))
                    {
                        throw new RuntimeException("{$file} exists, but doesn't declare class {$class}");
                    }
                    break;
                }
            }
            if ( ! class_exists($class, FALSE))
            {
                throw new RuntimeException("Unable to locate the model you have specified: {$model}");
            }
        }
        elseif ( ! is_subclass_of($class, 'CI_Model'))
        {
            throw new RuntimeException("Class {$model} already exists and doesn't extend CI_Model");
        }
        $this->_ci_models[] = $class;
        $CI->$class = new $class();
        return $this;
    }
}