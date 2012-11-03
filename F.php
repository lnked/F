<?php

define('F_PATH', $_SERVER['DOCUMENT_ROOT']);

function F()
{
	$args = func_get_args();
	$args = array_filter($args[0], function($x) { return !empty($x[0]); });
	
	$args = !isset( $args['error'] ) ? $args : array();
	
	return new F( $args );
}

class F extends image_driver
{
	public $allowed = array(
		'image/jpeg', 'image/png', 'image/bmp', 'image/gif', 'image/tiff'
	);
	
	public $_config = array( 
		'dir'	=> '/data/board/images/',
		'w'		=> 640,
		'h'		=> 480,
		'q'		=> 100
	);
	
	public $_ready = array();
	
	protected $_files = array();
	protected $_prefix = 'upload_';
	
	public function __construct( $files = array() )
	{
		if(empty($files))
			return false;
			
		$this->_files = $files ;
		
		return $this;
	}
	
	public function config( $cfg = array() )
	{
		$this->_config = array_replace($this->_config, array_intersect_key($cfg, $this->_config));
		$this->_config['dir'] = F_PATH . str_replace(F_PATH, '', $this->_config['dir']);
		
		return $this;
	}
	
	public function upload()
	{
		if( !$this->_files )
			return $this;
		
		if( !empty( $this->_files ) )
		{
			$tmp = array();
			
			foreach( $this->_files as $name => $arr )
			{
				if( is_array( $arr ) )
				{
					foreach( $arr as $k => $value )
					{
						$tmp[$k][$name] = $value;
					}
				}
				else {
					$tmp[$name] = $arr;
				}
			}
			
			if(!array_key_exists(0, $tmp))
			{
				if( isset( $tmp['type'] ) && in_array($x['type'], $this->allowed) ) {
					$this->_files = $tmp ;
				}
			}
			else
			{
				$allowed = $this->allowed;
				$this->_files = array_filter($tmp, function($x) use ($allowed) { return in_array($x['type'], $allowed); });
			}

			if( !$this->_files )
				return $this;
			
			if(!array_key_exists(0, $this->_files))
			{
				$this->uploading( $this->_files );
			}
			else {
				foreach( $this->_files as $k => $f ) {
					$this->uploading( $f );
				}
			}
		}
		return $this;
	}
	
	public function _crop()
	{
		return $this->crop();
	}
	
	public function _resize()
	{
		return $this;
	}
	
	public function optimize()
	{
		return $this;
	}
	
	private function uploading( $file = array() )
	{
		if( !empty( $file ) ) {
			$ext = $this->_extension( $file['name'] );
			$filename = $this->_generate() . $ext;
			
			$_new = $this->_config['dir'] . $filename;
			
			if( move_uploaded_file( $file['tmp_name'], $_new ) ) {
				$this->_ready[] = array(
					'file'			=>	str_replace(F_PATH, '', $_new),
					'filectime'		=>	filectime($_new),
					'filemtime'		=>	filemtime($_new),
					'filesize'		=>	filesize($_new)
				);
			}
		}
		return $this;
	}
	
	private function _generate()
	{
		return $this->_prefix . substr(md5(rand()), 0, 8) . '_' . substr(md5(rand()), 0, 8) . '.' ;
	}
	
	private function _extension($resource = null)
	{
		return pathinfo($resource,PATHINFO_EXTENSION);
	}
	
	private function size($file = null)
	{
	
	}
}

class image_driver
{
	protected $_real;
	protected $_newsize;
	protected $_file;
	
	function __construct($file) 
	{
		parent::__construct($file);
    }
	
	function crop()
	{
		if( !file_exists( $this->_result['file'] ) )
			return;
			
		$args = func_get_args() ;
		$this->_newsize['w'] = isset( $args[0] ) ? $args[0] : $this->_config['w'] ;
		$this->_newsize['h'] = isset( $args[1] ) ? $args[1] : $this->_config['h'] ;
		
		$this->_size() ;
		
		if( $this->resize() ) {
			echo 'resized' ;
		}
	}

	function resize()
	{
		if($this->_real === false) return false;
		
		$format = strtolower(substr($this->_real['mime'], strpos($this->_real['mime'], '/')+1));
		$icfunc = "imagecreatefrom" . $format;
		if (!function_exists($icfunc)) return false;

		$x_ratio = $this->_newsize['w'] / $this->_real[0];
		$y_ratio = $this->_newsize['h'] / $this->_real[1];
		
		$ratio       = min($x_ratio, $y_ratio);
		$use_x_ratio = ($x_ratio == $ratio);

		$new_w   = $use_x_ratio  ? $this->_newsize['w'] : floor($this->_real[0] * $ratio);
		$new_h  = !$use_x_ratio ? $this->_newsize['h'] : floor($this->_real[1] * $ratio);
		$new_left    = $use_x_ratio  ? 0 : floor(($this->_newsize['w'] - $new_w) / 2);
		$new_top     = !$use_x_ratio ? 0 : floor(($this->_newsize['h'] - $new_h) / 2);

		$isrc = $icfunc($this->_result['file']);
		$idest = imagecreatetruecolor($this->_newsize['w'], $this->_newsize['h']);

		imagefill($idest, 0, 0, 0xFFFFFF);
		imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0, 
		$new_w, $new_h, $this->_real[0], $this->_real[1]);

		imagejpeg($idest, $this->_result['file'], $this->_config['q']);

		imagedestroy($isrc);
		imagedestroy($idest);

		return true;
	}
	
	function _size()
	{
		$this->_real = @getimagesize( $this->_result['file'] ) ;
	}
	
	function watermark($file = null)
	{
		
	}
	
	function getWidth()
	{
		return imagesx($this->image);
	}
	
	function getHeight()
	{
		return imagesy($this->image);
	}
	
	function resizeToHeight($height)
	{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width,$height);
	}

	function resizeToWidth($width)
	{
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width,$height);
	}

	function scale($scale)
	{
		$width = $this->getWidth() * $scale/100;
		$height = $this->getheight() * $scale/100;
		$this->resize($width,$height);
	}
	
	/*
	function resize($width,$height)
	{
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}
	*/
}
