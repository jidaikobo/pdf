<?php
/**
 * PDF Package
 *
 * The TJS Technology PDF Package for Fuel takes any PHP5 PDF generating class
 * as a driver and acts as a wrapper for that class, thus integrating PDF generation
 * into Fuel without reinventing the wheel.
 *
 * @package		TJS
 * @author		TJS Technology Pty Ltd
 * @copyright	Copyright (c) 2011 TJS Technology Pty Ltd
 * @license		See LICENSE
 * @link		http://www.tjstechnology.com.au
 */

namespace Pdf;

define('MY_ENCODING', 'UTF-8');
define('MM_PER_POINT', (25.4 / 72.0));

class Pdf {
	
	// Lib path
	protected $_lib_path = '';
	
	// Driver Class
	protected $_driver_class = '';
	
	// Driver Instance
	protected $_driver_instance = '';
	
	/**
	 * Construct
	 * 
	 * Called when the class is initialised
	 * 
	 * @access	protected
	 * @return	PDF\PDF
	 */
	protected function __construct($driver = null)
	{
		// Load Config
		\Config::load(PKGPATH . 'pdf/config/pdf.php', 'pdf');
		// Default Driver
		if ($driver == null)
		{
			$driver = \Config::get('pdf.default_driver');
		}
		
		// Set the lib path
		$this->set_lib_path(PKGPATH . 'pdf' . DS . 'lib' . DS);
		
		$drivers = \Config::get('pdf.drivers');
		$temp_driver = (isset($drivers[$driver])) ? $drivers[$driver] : false;
		
		if ($temp_driver === false)
		{
			throw new \Exception(sprintf('Driver \'%s\' doesn\'t exist.', $driver));
		}
		
		$driver = $temp_driver;
		
		// Include files
		foreach ($driver['includes'] as $include)
		{
			include_once($this->_get_include_file($include));
		}
		
		$this->set_driver_class($driver['class']);
		
		// Return this object. User must now call init and provide the parameters that
		// the driver wants. This action is caught by __call()
		//var_dump($this); die();
		return $this;
	}
	
	/**
	 * Get Include File
	 * 
	 * Gets the path of the include file and
	 * makes it safe for Windows users.
	 * 
	 * @access	protected
	 * @param	string	file location (relative to lib path)
	 * @return	string	real file location
	 */
	protected function _get_include_file($file)
	{
		$file = sprintf('%s%s', $this->get_lib_path(), str_replace('/', DS, $file));
		
		if ( ! file_exists($file))
		{
			throw new \Exception(sprintf('File \'%s\' doesn\'t exist.', $file));
		}
		
		return $file;
	}
	
	/**
	 * Factory
	 * 
	 * Creates new instance of class
	 * 
	 * @access	public
	 * @return	PDF\PDF
	 */
	public static function factory($driver = null)
	{
		return new PDF($driver);
	}
	
	/**
	 * Camel to Underscore
	 * 
	 * Translates a camel case string into a string with underscores (e.g. firstName -> first_name)
	 * 
	 * @access	public
	 * @param	string	Camel-cased string
	 * @return	string	Underscored string
	 */
	public function camel_to_underscore($string)
	{
		$string[0]	= strtolower($string[0]);
		$function	= create_function('$c', 'return "_" . strtolower($c[1]);');
		
		return preg_replace_callback('/([A-Z])/', $function, $string);
	}
	
	/**
	 * Underscore to Camel
	 * 
	 * Translates a string with underscores into camel case (e.g. first_name -> firstName)
	 * 
	 * @access	public
	 * @param	string	Camel-cased string
	 * @param	bool	Pascal-case (firstName -> FirstName)
	 * @return	string	Underscored string
	 */
	public function underscore_to_camel($string, $pascal_case = false)
	{
		// Do we want to pascal-case it?
		if ($pascal_case)
		{
			$string[0] = strtoupper($string[0]);
		}
		
		$function = create_function('$c', 'return strtoupper($c[1]);');
		
		return preg_replace_callback('/_([a-z])/', $function, $string);
	}
	
	/**
	 * Call
	 * 
	 * Magic method to catch all calls
	 * 
	 * @access	public
	 * @param	string	method
	 * @param	array	arguments
	 * @return	mixed
	 */
	public function __call($method, $arguments)
	{
		// Init
		if ($method == 'init')
		{
			// Get new instance and provide arguments
			$reflect = new \ReflectionClass($this->get_driver_class());
			$instance = $reflect->newInstanceArgs($arguments);
			
			$this->set_driver_instance($instance);
			
			return $this;
		}
		
		// Get cameled method
		$cameled_method = $this->underscore_to_camel($method);
		
		if (method_exists($this->_driver_instance, $method))
		{
			$pdf = $this->get_driver_instance();
			
			$return = call_user_func_array(array($pdf, $method), $arguments);
			return ($return) ? $return : $this;
		}
		else if (method_exists($this->_driver_instance, $cameled_method))
		{
			$pdf = $this->get_driver_instance();
			
			$return = call_user_func_array(array($pdf, $cameled_method), $arguments);
			return ($return) ? $return : $this;
		}
		
		// Generic getter / setter
		
		// check if method is not public (protected methods called
		// outside are routed here)
		if (method_exists($this, $method))
		{
			$reflection = new ReflectionMethod($this, $name);
			
			if ( ! $reflection->isPublic())
			{
				throw new \Exception(sprintf('Call to non-public method %s::%s() caught by %s', $name, get_called_class(), get_called_class()));
			}
		}
		
		// Method (set / get)
		$method_type = substr($method, 0, 3);
		
		// Variable to set or get
		$variable = substr($method, 4);
		$protected_variable = '_' . $variable;
		
		// Verbose mode
		// The 'true' parameter might move depending on if
		// we're setting something
		if ($method_type === 'get')
		{
			$verbose = (isset($arguments[0])) ? $arguments[0] : false;
		}
		else if ($method_type === 'set')
		{
			$verbose = (isset($arguments[1])) ? $arguments[1] : false;
		}
		
		// Value
		if ($method_type === 'set')
		{
			$value = (isset($arguments[0])) ? $arguments[0] : false;
		}
		
		// See if it's a get or set
		if ($method_type === 'get' || $method_type === 'set')
		{
			if (isset($this->$variable))
			{
				if ($method === 'get')
				{
					return $this->$variable;
				}
				else if ($method === 'set')
				{
					$this->$variable = $value;
					
					return $this;
				}
			}
			// else check for that variable with an underscore first
			// (used in protected variables) - get_test() will first
			// check for $this->test, and then if non-existent check
			// for $this->_test
			else if (isset($this->$protected_variable))
			{
				if ($method_type === 'get')
				{
					return $this->$protected_variable;
				}
				else if ($method_type === 'set')
				{
					$this->$protected_variable = $value;
					
					return $this;
				}
			}
			else
			{
				if ($verbose)
				{
					throw new \Exception(sprintf('Variable $%s does not exist in class %s', $variable, get_called_class()));
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			throw new \Exception(sprintf('Call to undefined method %s::%s()', get_called_class(), $name));
		}
	}


	/** 3 => 6
	* Inset text vertical
	*
	* @param int $x (mm)
	* @param int $y (mm)
	* @param string $text
	* @param int $max_height (mm) to auto_size
	* @param str $valign
	* @param int $size (pt)
	* @param int $space (mm)
	* @param bool $num2kanji (mm)
	* @return array
	*/
   function text_horizontal($x, $y, $text, $max_width = 0,  $align = "left", $size = null,  $space = 0, $style = "", $family = "")
   {

		$length = mb_strlen($text);
		if($length === 0) return;
//		$this->SetFont(($family) ? $family : $this->family, $style, $size);

		while($max_width > 0 && $size > 0)
		{
			$this->SetFontSize($size);
			$width = $this->GetStringWidth($text) + $space * ($length - 1);
			if ($width  < $max_width)
			{
				break;
			}
			$size--;
		}

		if ($max_width > 0)
		{
			switch ($align)
			{
			case "center":
				$x += ($max_width - $width) /2;
				break;
			case "right":
				$x += ($max_width - $width);
				break;
			}
		}
		$x0 = $x;

		if ($space > 0) {
			for($i = 0; $i < $length; $i++)
			{
				$char = mb_substr($text, $i, 1);
				$this->Text($x, $y, $char);
				$x += ($this->GetStringWidth($char) + $space);
			}
		}
		else {
			$this->Text($x, $y, $text);
		}

		return array($size, $x0);
    }

	/**
	* Inset text vertical
	*
	* @param int $x (mm)
	* @param int $y (mm)
	* @param string $text
	* @param int $max_height (mm) to auto_size
	* @param str $valign
	* @param int $size (pt)
	* @param int $space (mm)
	* @param bool $num2kanji (mm)
	* @return array
	*/
	public function text_vertical($x, $y, $text, $max_height = 0, $valign = "top", $size = null, $space = 0, $num2kanji=false, $fix = 0.5)
	{

		if(mb_strlen($text) === 0) return;

		// setting
		if (!$size) $size = $this->getFontSizePt();

		$text = $this->num_to_kanji($text, $num2kanji);

		$length = mb_strlen($text, MY_ENCODING);
		$space_count = mb_substr_count($text, "　");

		$height = ($length - $space_count /2) * $size * MM_PER_POINT
			+ $space * ($length -1);

		if ($max_height > 0 && $height > $max_height)
		{
			$size = ($max_height - $space * ($length - 1)) / ($length - $space_count /2) / MM_PER_POINT;
			$height = $max_height;
		}


		$this->SetFontSize($size);

		$x -= ($size * MM_PER_POINT);    // 右端に補正

		switch($valign) {
			case "top":
				break;

			case "middle":
				$y += ($max_height - $height) /2;
				break;
			case "bottom":
				$y += ($max_height - $height);
				break;
		}

		$y0 = $y;

		for($i = 0; $i < $length; $i++) {
			$char = mb_substr($text, $i, 1, MY_ENCODING);

			$sw = $this->GetStringWidth($char);
		 	if (mb_substr_count("－ー―‐…‥（）〔〕［］｛｝〈〉《》「」『』【】-−＝＜＞", $char)) {
				// 回転文字
				$this->StartTransform();
				$this->Rotate(270, $x+ $sw / 2 , $y+ $sw / 2);
				// 多少補正して出力
				$this->Text($x - $size*0.05, $y - $sw/2 + $fix,  $char, false, false, true, 0, 2);
				//	$this->Rotate(-270);
				$this->StopTransform();
			}
			elseif (mb_substr_count("、。．，", $char)) {
				// 句読点
				$this->Text($x + $size * MM_PER_POINT * 0.75, $y - $size * MM_PER_POINT * 0.75, $char);
			}
			elseif (mb_substr_count("ぁぃぅぇぉゃゅょァィゥェォャュョ", $char)) {
				// 拗音
				$this->Text($x + $size * MM_PER_POINT * 0.15, $y - $size * MM_PER_POINT * 0.15, $char);
			}
			elseif (mb_substr_count("“‘", $char)) {
				// コーテーション
				$this->Text($x, $y + $size * MM_PER_POINT * 0.5, $char);
			}
			elseif ($char == '〓') {
				// 連名の姓（印刷しない）
			}
			else
			{
				$this->Text($x, $y, $char);
			}

			if ($char == "　") {
				$y += $size * MM_PER_POINT /2 + $space;
			} else {
				$y += $size * MM_PER_POINT + $space;
			}
		}
		// die();
		return array($size, $y0);
	}

	/* 
	 * 数字を漢字に変換
	 * 第2引数 false なら全角数字に
	 */
	public function num_to_kanji($text, $num2kanji) {
		// 最初に半角英数に統一
		$text = mb_convert_kana($text, "a", MY_ENCODING);

		// 1F 〜 9F 表記をそのまま全角に
		if (preg_match('/\b[1-9]F\b/', $text, $matchs)) {
			foreach($matchs as $search) {
				$replace = mb_convert_kana($search, "A", MY_ENCODING);
				$text = str_replace($search, $replace, $text);
			}
		}

		// 2桁以上は、漢数字＋階表記にするf
		$text = preg_replace('/([0-9])F\b/', "$1階", $text);

		// 残りの数字を全角に
		if ($num2kanji) {
			$text = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
							array('〇', '一', '二', '三', '四', '五', '六', '七', '八', '九'),
							$text);
		} else {
			$text = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
							array('０', '１', '２', '３', '４', '５', '６', '７', '８', '９'),
							$text);
		}

		$text = mb_convert_kana($text, "RASK", MY_ENCODING);

		return $text;
	}




}

