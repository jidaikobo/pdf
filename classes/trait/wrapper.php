<?php
namespace Pdf;

trait Trait_Wrapper
{
	/*
	 * @Wrapper of Text
	 */
	public function Txt($options)
	{
		$options = static::boxWordBuffer($options);

		if(isset($options['font_size']) && $options['font_size'] > 0)
		{
			$this->SetFontSize($options['font_size']);
		}

		$defaults = array(
			'x' => 0,
			'y' => 0,
			'txt' => '',
			'fstroke' => false,
			'fclip' => false,
			'ffill' => true,
			'border' => 0,
			'ln' => 0,
			'align' => '',
			'fill' => false,
			'link' => '',
			'stretch' => 0,
			'ignore_min_height' => false,
			'calign' => 'T',
			'valign' => 'M',
			'rtloff' => false
		);

		$options = array_merge($defaults, $options);

		return parent::Text(
			$options['x'],
			$options['y'],
			$options['txt'],
			$options['fstroke'],
			$options['fclip'],
			$options['ffill'],
			$options['border'],
			$options['ln'],
			$options['align'],
			$options['fill'],
			$options['link'],
			$options['stretch'],
			$options['ignore_min_height'],
			$options['calign'],
			$options['valign'],
			$options['rtloff']
		);
	}

	/*
	 * @Wrapper of Cell
	 * @important align のデフォルトは 'j' -> 'L' になっています
	 */
	public function Box($options)
	{
		$options = static::boxWordBuffer($options);

		if (isset($options['x']) and $options['x']) {
			$x = intval($options['x']);
		}
		if (isset($options['y']) and $options['y']) {
			$y = intval($options['y']);
		}

		if (isset($x) and isset($y)) {
			$this->SetXY($x, $y);
		} elseif (isset($x)) {
			$this->SetX($x);
		} elseif (isset($y)) {
			$this->SetY($y);
		}


		if(isset($options['font_size']) && $options['font_size'] > 0)
		{
			$this->SetFontSize($options['font_size']);
		}

		$defaults = array(
			'w' => 0,
			'h' => 0,
			'txt' => '',
			'border' => 0,
			'ln' => 0,
			'align' => '',
			'fill' => false,
			'link' => '',
			'stretch' => 0,
			'ignore_min_height' => false,
			'calign' => 'T',
			'valign' => 'M'
		);

		$options = array_merge($defaults, $options);

		return parent::Cell(
			$options['w'],
			$options['h'],
			$options['txt'],
			$options['border'],
			$options['ln'],
			$options['align'],
			$options['fill'],
			$options['link'],
			$options['stretch'],
			$options['ignore_min_height'],
			$options['calign'],
			$options['valign']
		);
	}

	/*
	 * @Wrapper of MultiCell
	 * @important align のデフォルトは 'J' -> 'L' になっています
	 */
	public function MultiBox($options)
	{
		$options = static::boxWordBuffer($options);

		if(isset($options['font_size']) && $options['font_size'] > 0)
		{
			$this->SetFontSize($options['font_size']);
		}
		if(isset($options['fillcolor']))
		{
			if (!isset($options['fillcolor'][0])) $options['fillcolor'][0] = 0;
			if (!isset($options['fillcolor'][1])) $options['fillcolor'][1] = -1;
			if (!isset($options['fillcolor'][2])) $options['fillcolor'][2] = -1;
			if (!isset($options['fillcolor'][3])) $options['fillcolor'][3] = -1;
			$this->SetFillColor(
				$options['fillcolor'][0],
				$options['fillcolor'][1],
				$options['fillcolor'][2],
				$options['fillcolor'][3]
			);
		}

		$defaults = array(
			'w' => 0,
			'h' => 0,
			'txt' => '',
			'border' => 0,
			'align' => 'L', // 'J' -> 'L'
			'fill' => false,
			'ln' => 1,
			'x' => '',
			'y' => '',
			'reseth' => true,
			'stretch' => 0,
			'ishtml' => false,
			'autopadding' => true,
			'maxh' => 0,
			'valign' => 'T',
			'fitcell' => false,
		);

		$options = array_merge($defaults, $options);

		return parent::MultiCell(
			$options['w'],
			$options['h'],
			$options['txt'],
			$options['border'],
			$options['align'],
			$options['fill'],
			$options['ln'],
			$options['x'],
			$options['y'],
			$options['reseth'],
			$options['stretch'],
			$options['ishtml'],
			$options['autopadding'],
			$options['maxh'],
			$options['valign'],
			$options['fitcell']
		);
	}

	/*
	 * Box options の key の揺れを吸収
	 */
	protected static function boxWordBuffer($options)
	{
		// key to val
		$buffers = array(
			'size' => 'font_size',
			'fontsize' => 'font_size',
			'fontSize' => 'font_size',
			'width' => 'w',
			'height' => 'h',
			'text' => 'txt',
			'max_height' => 'maxh',
			'maxheight' => 'maxh',
			'maxHeight' => 'maxh',
			'cell_align' => 'calign',
			'cellalign' => 'calign',
			'cellAlign' => 'calign',
			'vertical_align' => 'valign',
			'verticalalign' => 'valign',
			'verticalAlign' => 'valign',
			'fit_cell' => 'fitcell',
			'fitcell' => 'fitcell',
			'fitCell' => 'fitcell',
			'fill_color' => 'fillcolor',
			'fillColor' => 'fillcolor',
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
