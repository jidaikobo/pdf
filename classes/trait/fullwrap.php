<?php
namespace Pdf;

/*
 * trait wrapper の使用前提
 */

trait Trait_FullWrap
{
	/*
	 * @Wrapper of Text
	 */
	public function Text($x, $y, $txt, $fstroke=false, $fclip=false, $ffill=true, $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M', $rtloff=false)
	{
		if (!is_array($x))
		{
			parent::Text($x, $y, $txt, $fstroke, $fclip, $ffill, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign, $rtloff);
			return $this;
		}
		else
		{
			$this->Txt($x);
			return $this;
		}
	}

	/*
	 * @Wrapper of Cell
	 */
	public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')
	{
		if (!is_array($w))
		{
			parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
			return $this;
		}
		else
		{
			$this->Box($w);
			return $this;
		}
	}

	/*
	 * @Wrapper of MultiCell
	 */
	public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
	{
		if (!is_array($x))
		{
			parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln, $x, $y, $reseth, $stretch, $ishtml, $autopadding, $maxh, $valign, $fitcell);
			return $this;
		}
		else
		{
			$this->MultiBox($x);
			return $this;
		}
	}

}

