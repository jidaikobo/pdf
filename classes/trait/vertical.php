<?php
namespace Pdf;

/*
 * 縦書き関数
 */

trait Trait_Vertical
{

	// wrapper
	public function Vertical(
		$x = 0,
		$y = 0,
		$text = '',
		$max_height = 0,
		$valign = "T",
		$size = null,
		$space = 0,
		$num2kanji=false,
		$fix = 0.5 )
	{
		if (is_array($x))
		{
			$defaults = array(
				'x' => 0,
				'y' => 0,
				'txt' => '',
				'max_height' => 0,
				'valign' => "T",
				'font_size' => null,
				'space' => 0,
				'num2kanji' => false,
				'fix' => 0.5 
			);
			$x = static::verticalWordBuffer($x);
			$x = array_merge($defaults, $x);
			return $this->text_vertival(
				$x['x'],
				$x['y'],
				$x['txt'],
				$x['max_height'],
				$x['valign'],
				$x['font_size'],
				$x['space'],
				$x['num2kanji'],
				$x['fix']
			);
		}
		else
		{
			return $this->text_vertival(
				$x,
				$y,
				$text,
				$max_height,
				$valign,
				$size,
				$space,
				$num2kanji,
				$fix
			);
		}

	}

	/*
	 * text_vertical()
	 * @param int    $x (mm)
	 * @param int    $y (mm)
	 * @param string $text
	 * @param int    $max_height (mm) to auto_size
	 * @param string $valign
	 * @param int    $size (pt)
	 * @param int    $space (mm)
	 * @param bool   $num2kanji false でも数字は全角になる
	 * @param float  $fix
	 * @return array
	 */
	protected function text_vertival(
		$x = 0,
		$y = 0,
		$text = '',
		$max_height = 0,
		$valign = "T",
		$size = null,
		$space = 0,
		$num2kanji=false)
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
			case "T":
				break;
			case "M":
				$y += ($max_height - $height) /2;
				break;
			case "B":
				$y += ($max_height - $height);
				break;
		}

		$y0 = $y;

		for($i = 0; $i < $length; $i++) {
			$char = mb_substr($text, $i, 1, MY_ENCODING);

			$sw = $this->GetStringWidth($char);
			if (mb_substr_count("－ー―—‐…‥（）〔〕［］｛｝〈〉《》「」『』【】-−＝＜＞", $char)) {
				$fixX = 0.05 * $size - 1.0;
				// $fixX = 0;
				$fixY = 0.125 * $size - 0.8;
				// 回転文字
				$this->StartTransform();
				$this->Rotate(270, $x+ $sw / 2 , $y+ $sw / 2);
				// 多少補正して出力
				$this->Text($x + $fixX, $y - $sw/2 + $fixY,  $char, false, false, true, 0, 2);
				//	$this->Rotate(-270);
				$this->StopTransform();
			}
			elseif (mb_substr_count("、。．，", $char)) {
				// 句読点
				$this->Text($x + $size * MM_PER_POINT * 0.75, $y - $size * MM_PER_POINT * 0.75, $char);
			}
			elseif (mb_substr_count("ぁぃぅぇぉゃゅょァィゥェォャュョっッ", $char)) {
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
	 * num_to_kanji()
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

	/*
	 * Box options の key の揺れを吸収
	 */
	protected static function verticalWordBuffer($options)
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


