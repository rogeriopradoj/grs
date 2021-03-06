<?php

/**
 * Generic RESTful Server
 *
 * Implements a generic controller for a RESTful service.
 * Create a model class inside model directory.
 * When client calls
 *      http://www.server.com/context/Model/methodName/param1/param2/.../paramN.json
 * this class will instantiate the Model class, call the methodName method from
 * the object, passing params 1..N, and then format whatever comes back
 * (usually an array) into a json object.
 * You can use paramN.xml to format the response as a XML.
 * If you prefer to return a string, use paramN.txt to simply echo whatever
 * comes back.
 *
 * @author Ricardo Coelho (www.nexy.com.br)
*/

class Grs
{
    protected $_self;
    protected $_request;
    protected $_file_type;
    protected $_segments;
    protected $_class_name;
    protected $_method_name;
    protected $_params;
    protected $_models_path;	
    protected $_views_path;
    protected $_my_path;
	protected $_encoding;
    
    public function __construct()
    {
        $this->_my_path = realpath(dirname(__FILE__));
        $this->_models_path = $this->_my_path . '/model/';
		$this->_encoding = 'utf-8';
        $this->_init();
    }
    protected function _init()
    {
        $this->_initRequest();
        $this->_initFileType();
        $this->_initSegments();
        $this->_initAttributes();
        $this->_initParams();
    }
    protected function _initFileType()
    {
        $info = pathinfo($this->_request);
        if (isset($info['extension'])) {
			$this->_file_type = $info['extension'];
        } else {
			$this->_file_type = 'json';
		}
    }
    protected function _initRequest()
    {
        $this->_self = dirname($_SERVER['PHP_SELF']);
        $this->_request = str_replace($this->_self, '', $_SERVER['REQUEST_URI']);
        if (substr($this->_request, -1) == '/') {
            $this->_request = substr($this->_request, 0, -1);
        }
    }
    protected function _initSegments()
    {
        $this->_request = str_replace('.' . $this->_file_type, '', $this->_request);
        $this->_segments = explode('/', $this->_request);

        if (!isset($this->_segments[2])) {
			if (empty($this->_segments[1])) {
				$this->_segments[1] = 'Index';
			}
			$this->_segments[2] = 'index';
		}
        $this->_segments[0] = $this->_file_type;
    }
    protected function _initAttributes()
    {
        $this->_class_name = $this->_segments[1];
        $this->_method_name = $this->_segments[2];
    }
    protected function _initParams()
    {
        $this->_params = array();
        $tam = count($this->_segments);
        for ($idx = 3; $idx < $tam; $idx++) {
            $this->_params[] = urldecode($this->_segments[$idx]);
        }
    }
    public function setModelsPath($path)
    {
        if (substr($path, -1) != '/') {
            $path .= '/';
        }
        $this->_models_path = $path;   
    }
	public function setFileType($file_type)
	{
		$this->_file_type = $file_type;
	}
    public function setViewsPath($path)
    {
        if (substr($path, -1) != '/') {
            $path .= '/';
        }
        $this->_views_path = $path;   
    }
	public function setEncoding($encoding)
	{
		$this->_encoding = $encoding;
	}
    public function dispatch()
    {
        $filename = $this->_models_path . $this->_class_name . '.php';
        if (!file_exists($filename)) {
            throw new Exception('Model not found: [' . $filename . '].');
        }
        require $filename;
        if (!class_exists($this->_class_name)) {
            throw new Exception('Class ' . $this->_class_name . ' is not defined in file ' . $filename . '.');            
        }
        $obj = new $this->_class_name;
        if (method_exists($obj, $this->_method_name)) {
            $method = $this->_method_name;
			if (method_exists($obj, 'setGrs')) {
				$obj->setGrs($this);
			}
            $data = $obj->$method($this->_params);
        } else {
            throw new Exception('Method ' . $this->_method_name . ' does not exist within class ' . $this->_class_name . '.');
        }
        $view_filename = $this->_my_path . '/view/' . $this->_file_type . '.php';
        $local_view_filename = $this->_views_path . $this->_file_type . '.php';

        if (!file_exists($view_filename)) {
			throw new Exception('Unknown output format: [' . $this->_file_type . '].');
		}
		header('Content-type: text/html; charset=' . $this->_encoding);
        if (file_exists($local_view_filename)) {
			require $local_view_filename;
        } else {
			require $view_filename;
		}
    }
}
