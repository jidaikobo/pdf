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

class Pdf extends \FPDI
{
	use \Pdf\Trait_Wrapper;
	use \Pdf\Trait_Method;
	use \Pdf\Trait_Vertical;
	use \Pdf\Trait_Format;

	protected $_full_wrap = false;

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
			return new static(
				$options['orientation'],
				$options['unit'],
				$options['format'],
				$options['unicode'],
				$options['encoding'],
				$options['diskcache'],
				$options['pdfa']
			);
		}
		else
		{
			return new static(
				$orientation,
				$unit,
				$format,
				$unicode,
				$encoding,
				$diskcache,
				$pdfa
			);
		}
	}

	public function setting($settings = array())
	{

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
		$this->SetMargins(
			$settings['margins'][0],
			$settings['margins'][1],
			$settings['margins'][2],
			$settings['margins'][3]);

		$this->SetCreator($settings['creator']);
		$this->SetAuthor($settings['author']);
		$this->SetTitle($settings['title']);
		$this->SetSubject($settings['subject']);

		$this->setPrintHeader($settings['header']);
		$this->setPrintFooter($settings['footer']);
		$this->SetAutoPageBreak($settings['pagebreak']);

		// set font
		switch ($settings['font']) {
			case 'mincho' :
			case 'MINCHO' :
			case 'Mincho' :
			case '明朝体' :
			case '明朝' :
				$this->setFont('kozminproregular');
				break;
			case 'gothic' :
			case 'GOTHIC' :
			case 'Gothic' :
			case 'ゴシック体' :
			case 'ゴシック' :
				$this->setFont('kozgopromedium');
				break;
			default :
				$this->setFont($settings['font']);
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


}
