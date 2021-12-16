<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class M_Config extends CI_Config {

	public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
	{

		$file = ($file === '') ? 'config' : str_replace('.php', '', $file);
		$loaded = FALSE;

        $this->_config_paths = array_unique($this->_config_paths);

        $this->_config_paths[] = WEBPATH;

        foreach ($this->_config_paths as $path)
		{
                $location = $file ;

				$file_path = $path.'config/'.$location.'.php';
				if (in_array($file_path, $this->is_loaded, TRUE))
				{
					return TRUE;
				}

				if ( ! file_exists($file_path))
				{
					continue;
				}

				include($file_path);

				if ( ! isset($config) OR ! is_array($config))
				{
					if ($fail_gracefully === TRUE)
					{
						return FALSE;
					}

					show_error('Your '.$file_path.' file does not appear to contain a valid configuration array.');
				}

				if ($use_sections === TRUE)
				{
					$this->config[$file] = isset($this->config[$file])
						? array_merge($this->config[$file], $config)
						: $config;
				}
				else
				{
					$this->config = array_merge($this->config, $config);
				}

				$this->is_loaded[] = $file_path;
				$config = NULL;
				$loaded = TRUE;
				log_message('debug', 'Config file loaded: '.$file_path);

		}

		if ($loaded === TRUE)
		{
			return TRUE;
		}
		elseif ($fail_gracefully === TRUE)
		{
			return FALSE;
		}

		show_error('The configuration file '.$file.'.php does not exist.');
	}
}
