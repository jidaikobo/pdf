<?php
/**
 * Pdf Package
 *
 * @package		Pdf
 * @author		konagai@jidaikobo.com
 * @copyright	Copyright (c) jidaikobo Inc.
 * @license		Public Domain
 * @link		http://www.jidaikobo.com/
 */

namespace Pdf;

include_once( dirname(dirname( __FILE__ )) . '/lib/tcpdf/config/lang/jpn.php' );
include_once( dirname(dirname( __FILE__ )) . '/lib/tcpdf/tcpdf.php' );
include_once( dirname(dirname( __FILE__ )) . '/lib/fpdi/fpdi.php' );

class Pdf
{
	use \Pdf\Trait_Wrapper;
	use \Pdf\Trait_Method;
	use \Pdf\Trait_Vertical;
	use \Pdf\Trait_Format;

	protected $_full_wrap = false;
	protected static $_pdf;

	public static function forge(
		$orientation='P',
		$unit='mm',
		$format='A4',
		$unicode=true,
		$encoding='UTF-8',
		$diskcache=false,
		$pdfa=false
	) {

		defined('MY_ENCODING')  ?: define('MY_ENCODING', 'UTF-8');
		defined('MM_PER_POINT') ?: define('MM_PER_POINT', (25.4 / 72.0));

		if (is_array($orientation)) {
			$defaults = array(
				'orientation' => 'P',
				'unit' => 'mm',
				'format' => 'A4',
				'unicode' => true,
				'encoding' => 'UTF-8',
				'diskcache' => false,
				'pdfa' => false
			);
			$options = array_merge($defaults, $options);
			$_pdf =  new \FPDI(
				$options['orientation'],
				$options['unit'],
				$options['format'],
				$options['unicode'],
				$options['encoding'],
				$options['diskcache'],
				$options['pdfa']
			);
			static::$_pdf = $_pdf;
			return new static();
		}
		else
		{
			$_pdf = new \FPDI(
				$orientation,
				$unit,
				$format,
				$unicode,
				$encoding,
				$diskcache,
				$pdfa
			);
			static::$_pdf = $_pdf;
			return new static();
		}
	}

	public function setting($settings = array())
	{
		if (is_null(static::$_pdf)) return;

		$pdf = static::$_pdf;

		$defaults = array(
			'creator' => 'creator',
			'author' => 'author',
			'title' => 'title',
			'subject' => 'subject',
			'header' => false,
			'footer' =>false,
			'pagebreak' => true,
			'margins' => array(0,0,0),
			'font' => 'mincho',
		);

		$settings = static::settingBuffer($settings);

		$settings = array_merge($defaults, $settings);

		// SetMargins
		if (!isset($settings['margins'][0])) $settings['margins'][0] = 0;
		if (!isset($settings['margins'][1])) $settings['margins'][1] = $settings['margins'][0];
		if (!isset($settings['margins'][2])) $settings['margins'][2] = -1;
		if (!isset($settings['margins'][3])) $settings['margins'][3] = false;
		$pdf->SetMargins(
			$settings['margins'][0],
			$settings['margins'][1],
			$settings['margins'][2],
			$settings['margins'][3]);

		$pdf->SetCreator($settings['creator']);
		$pdf->SetAuthor($settings['author']);
		$pdf->SetTitle($settings['title']);
		$pdf->SetSubject($settings['subject']);

		$pdf->setPrintHeader($settings['header']);
		$pdf->setPrintFooter($settings['footer']);
		$pdf->SetAutoPageBreak($settings['pagebreak']);

		// set font
		switch ($settings['font']) {
			case 'mincho' :
			case 'MINCHO' :
			case 'Mincho' :
			case '明朝体' :
			case '明朝' :
				$pdf->setFont('kozminproregular');
				break;
			case 'gothic' :
			case 'GOTHIC' :
			case 'Gothic' :
			case 'ゴシック体' :
			case 'ゴシック' :
				$pdf->setFont('kozgopromedium');
				break;
			default :
				$pdf->setFont($settings['font']);
				break;
		}
	}

	/*
	 * Box options の key の揺れを吸収
	 */
	protected static function settingBuffer($options)
	{
		// key to val
		$buffers = array(
			'print_header' => 'header',
			'printHeader' => 'header',
			'print_footer' => 'footer',
			'printFooter' => 'footer',
			'font_family' => 'font',
			'fontFamily' => 'font',
			'page_break' => 'pagebreak',
			'pageBreak' => 'pagebreak',
			'auto_page_break' => 'pagebreak',
			'autoPageBreak' => 'pagebreak',
			'margin' => 'margins',
		);

		foreach ($buffers as $key => $val)
		{
			if (isset($options[$key]) and !isset($options[$val]))
			{
				$options[$val] = $options[$key];
			}
		}

		return $options;
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



	/*
	 * 
	 */
	public function __call($method, $arguments)
	{
		// Get cameled method
		$cameled_method = $this->underscore_to_camel($method);
		
		if (method_exists(static::$_pdf, $method))
		{
			$pdf = static::$_pdf;

			$return = call_user_func_array(array($pdf, $method), $arguments);
			if ($return OR $return === 0) {
				return $return;
			} else {
				return $this;
			}
		}
		else if (method_exists(static::$_pdf, $cameled_method))
		{
			$pdf = static::$_pdf;
			
			$return = call_user_func_array(array($pdf, $cameled_method), $arguments);
			if ($return OR $return === 0) {
				return $return;
			} else {
				return $this;
			}
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


}
