<?php
#$Id: pdf.inc.php,v 1.1 2009/01/07 05:24:59 yoshiyuki Exp yoshiyuki $

//
// PDF class for FPDF width MBFPDF, FPDI and Rotations
// Copyriht (c) 2009 y.mikome
// http://www.myopensrc.com
//
//
mb_internal_encoding("SJIS");
define('MY_FPDF_DIR', '');
define('MY_ENCODING', 'SJIS');
define('MM_PER_POINT', (25.4 / 72.0));


define('FPDF_FONTPATH', "./font/");
require_once(dirname(__FILE__) . '/' .'MBFPDF.php');

//require "./MBFPDF.php";

class Pdf
{
    public $pdf;
    public $format;
    public $family;
    public $current = array();

    /**
    * Constructor
    *
    * @param string $orientation
    * @param string $unit
    * @param string format
    * @param array $families
    * @return object
    */

    function __construct($orientation = "P", $unit = "mm", $format = "A4", $families = array(PMINCHO))
    {
        if (!is_array($format) && $format == "Postcard")
        {
            $unit = "mm";
            $format = array(100, 148);
        }

        $this->pdf = new MBFPDF($orientation, $unit, $format);
        $this->format = $format;

        foreach($families as $family)
        {
            $this->pdf->AddMBFont($family, MY_ENCODING);
        }

        $this->pdf->SetTextColor(0,0,0);

        return $this->pdf;
    }

    /**
     * Add Page
     *
     * @param string $family
     * @param string $template
     */

    function add_page($family = PMINCHO, $template = "")
    {
        $this->family = $family;

        $this->pdf->addPage();

        if ($template != "")
        {
            if (is_array($this->format))
            {
                $x = $this->format[0];
                $y = $this->format[1];
            }
            else
            {
                switch ($this->format)
                {
                case "A3": $x = 297; $y = 420; break;
                case "A4": $x = 210; $y = 297; break;
                case "A5": $x = 148; $y = 210; break;
                case "Letter": $x = 215.9; $y = 279.4; break;
                case "Legal": $x = 215.9; $y = 355.6; break;
                }
            }

            $this->pdf->setSourceFile(dirname(__FILE__) . '/pdftemplate' . '/' . $template);
            $template_index = $this->pdf->ImportPage(1);

            $this->pdf->useTemplate($template_index, 0, 0, $x, $y);
        }
    }



    /**
    * Output
    *
    * @param string $name
    * @param string $dest
    */

    function output($name = "doc.pdf", $zome = "default")
    {
        $this->pdf->SetDisplayMode($zome);
        $this->pdf->Output($name, "I");
    }


    /**
     * Insert Text Horizontal
     *
     * @param integer $x (mm)
     * @param integer $y (mm)
     * @param integer $size (pt)
     * @param string $text
     * @param integer $max_width (mm)
     * @param string $align
     * @param integer $space (mm)
     * @param string $style
     * @param string $family
     * @return array
     */

    function text_horizontal($x, $y, $size, $text, $max_width = 0, $align = "left", $space = 0, $style = "", $family = "")
	{
		// $text = mb_convert_encoding($text, MY_ENCODING, "UTF-8");
        $length = mb_strlen($text, MY_ENCODING);

        $this->pdf->SetFont(($family) ? $family : $this->family, $style, $size);

        while($max_width > 0 && $size > 0)
        {
            $this->pdf->SetFontSize($size);

            $width = $this->pdf->GetStringWidth($text) + $space * ($length - 1);

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

        if ($space > 0)
        {
            for($i = 0; $i < $length; $i++)
            {
                $char = mb_substr($text, $i, 1, MY_ENCODING);

                $this->pdf->Text($x, $y, $char);

                $x += ($this->pdf->GetStringWidth($char) + $space);
            }
        }
        else
        {
            $this->pdf->Text($x, $y, $text);
        }

        return array($size, $x0);
    }

    /**
    * Inset text vertical
    *
    * @param int $x (mm)
    * @param int $y (mm)
    * @param int $size (pt)
    * @param string $text
    * @param int $max_height (mm) to auto_size
    * @param int $space (mm)
    * @param string $style
    * @return array
    */

    function text_vertical($x, $y, $size, $text, $max_height = 0, $valign = "top", $space = 0, $style = "", $family = "")
	{
		$text = mb_convert_encoding($text, MY_ENCODING, "UTF-8");

        $text = $this->num_to_kanji($text);

        $length = mb_strlen($text, MY_ENCODING);
        $space_count = mb_substr_count($text, "　");

        $height = ($length - $space_count /2) * $size * MM_PER_POINT
            + $space * ($length -1);

        if ($max_height > 0 && $height > $max_height)
        {
            $size = ($max_height - $space * ($length - 1)) / ($length - $space_count /2) / MM_PER_POINT;
            $height = $max_height;
        }

        $this->pdf->SetFont(($family) ? $family : $this->family, $style, $size);

        $x -= ($size * MM_PER_POINT);    // 右端に補正

        switch($valign)
        {
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

        $y += ($size * MM_PER_POINT);    // 横書きのベースラインは下端なので上端に補正

        for($i = 0; $i < $length; $i++)
        {
            $char = mb_substr($text, $i, 1, MY_ENCODING);

            if (mb_substr_count("ー―‐…‥（）〔〕［］｛｝〈〉《》「」『』【】−＝＜＞", $char))
            {
                // 回転文字
                $this->pdf->Rotate(270, $x, $y);
                $this->pdf->Text($x - $size * MM_PER_POINT * 0.85, $y - $size * MM_PER_POINT * 0.15, $char);
                $this->pdf->Rotate(0);
            }
            elseif (mb_substr_count("、。．，", $char))
            {
                // 句読点
                $this->pdf->Text($x + $size * MM_PER_POINT * 0.75, $y - $size * MM_PER_POINT * 0.75, $char);
            }
            elseif (mb_substr_count("ぁぃぅぇぉゃゅょァィゥェォャュョ", $char))
            {
                // 拗音
                $this->pdf->Text($x + $size * MM_PER_POINT * 0.15, $y - $size * MM_PER_POINT * 0.15, $char);
            }
            elseif (mb_substr_count("“‘", $char))
            {
                // コーテーション
                $this->pdf->Text($x, $y + $size * MM_PER_POINT * 0.5, $char);
            }
            elseif ($char == '〓')
            {
                // 連名の姓（印刷しない）
            }
            else
            {
                $this->pdf->Text($x, $y, $char);
            }

            if ($char == "　")
            {
                $y += $size * MM_PER_POINT /2 + $space;
            }
            else
            {
                $y += $size * MM_PER_POINT + $space;
            }
        }

        return array($size, $y0);
    }

    /**
     * Insert Text Ratate 90
     *
     * @param integer $x (mm)
     * @param integer $y (mm)
     * @param integer $size (pt)
     * @param string $text
     * @param integer $max_width (mm)
     * @param string $align
     * @param integer $space (mm)
     * @param string $style
     * @param string $family
     * @return array
     */

    function text_rotate90($x, $y, $size, $text, $max_width = 0, $align = "left", $space = 0, $style = "", $family = "")
    {
        $length = mb_strlen($text, MY_ENCODING);

        $this->pdf->SetFont(($family) ? $family : $this->family, $style, $size);

        while($max_width > 0 && $size > 0)
        {
            $this->pdf->SetFontSize($size);

            $width = $this->pdf->GetStringWidth($text) + $space * ($length - 1);

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
                $y -= ($max_width - $width) /2;
                break;

            case "right":
                $y -= ($max_width - $width);
                break;
            }
        }
        $y0 = $y;

        $this->pdf->Rotate(90, $x, $y);
        if ($space > 0)
        {
            for($i = 0; $i < $length; $i++)
            {
                $char = mb_substr($text, $i, 1, MY_ENCODING);

                $this->pdf->Text($x, $y, $char);

                $y -= ($this->pdf->GetStringWidth($char) + $space);
            }
        }
        else
        {
            $this->pdf->Text($x, $y, $text);
        }
        $this->pdf->Rotate(0);

        return array($size, $y0);
    }

    /**
    * number to kanji
    *
    * @param string $text
    * @return string
    */

    function num_to_kanji($text)
    {
        // 最初に半角英数に統一
        $text = mb_convert_kana($text, "a", MY_ENCODING);

        // 1F 〜 9F 表記をそのまま全角に
        if (preg_match('/\b[1-9]F\b/', $text, $matchs))
        {
            foreach($matchs as $search)
            {
                $replace = mb_convert_kana($search, "A", MY_ENCODING);
                $text = str_replace($search, $replace, $text);
            }
        }

        // 2桁以上は、漢数字＋階表記にするf
        $text = preg_replace('/([0-9])F\b/', "$1階", $text);

        // 残りの数字を全角に
        $text = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
                            array('〇', '一', '二', '三', '四', '五', '六', '七', '八', '九'),
                            $text);

        $text = mb_convert_kana($text, "RASK", MY_ENCODING);

        return $text;
    }
}
?>

