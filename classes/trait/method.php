<?php
namespace Pdf;

trait Trait_Method
{
	/*
	 * Bulk
	 * @param Model or array $object
	 * @param array $formats
	 * 
	 */
	public function Bulk($object, $formats)
	{
		foreach ($formats as $key => $format)
		{
			if ( !isset($format['fields']) ) continue;

			$format['txt'] = '';
			foreach($format['fields'] as $field_name)
			{

				// field を元に, object を txt に変換
				if (is_object($object))
				{
					// リレーションの可能性有り
					$related_str = false;
					if (strpos($field_name, '.') !== false)
					{
						$related_name = substr($field_name, 0, strpos($field_name, '.'));
						$related_field = substr($field_name, strpos($field_name, '.') +1);
						isset($object->{$related_name}) &&
						isset($object->{$related_name}->{$related_field}) &&
						$related_str = $object->{$related_name}->{$related_field};
					}

					if ($related_str && is_string($related_str))
					{
						$format['txt'] .= $related_str;
					} // ここまでリレーションの処理
					else if (isset($object->{$field_name}))
					{
						$format['txt'] .= $object->{$field_name};
					}
					else if (isset($object[$field_name]))
					{
						$format['txt'] .= $object[$field_name];
					}
					else
					{
						$format['txt'] .= $field_name;
					}
				}
				else
				{
					$format['txt'] .= $field_name;
				}
			}

			$this->MultiBox($format);
		}
	}

	/*
	 * @param array $objects
	 * @param array $formats
	 */
	public function Table($objects, $formats)
	{
		$startX = $this->getX();
		$startY = $nextY = $this->getY();

		$defaults = array(
			'border' => 'T',
			'ln' => 1,
		);

		if (!isset($formats[0][0])) {
			$formats = array($formats);
		}

		for ($i = 0; $i < count($formats); $i++) {
			$formats_length = count($formats[$i]);

			for ($j = 0; $j < $formats_length; $j++)
			{
				/*
				if ($j == ($formats_length -1))
				{
					$defaults['ln'] = 1;
				}
				 */
				$formats[$i][$j] = array_merge($defaults, $formats[$i][$j]);
			}
		}


		// is_add_page の判定で使いたいので、key を詰める
		$objects = array_values( $objects );

		$rowspan_fields = array();
		foreach ($objects as $object_key => $object)
		{

			$this->setXY($startX, $nextY);
			// $formats[0]['fields'][1] = $nextY;

			$format_key = $object_key%count($formats);

			foreach ($formats[$format_key] as $key => $val)
			{
				$val = static::tableWordBuffer($val);
				if ( !isset($val['fields']) ) continue;

				$formats[$format_key][$key]['txt'] = '';
				foreach($val['fields'] as $field_name)
				{
					if (is_object($object) && isset($object->{$field_name}))
					{
						$formats[$format_key][$key]['txt'] .= $object->{$field_name};
					}
					else if (is_array($object) && isset($object[$field_name]))
					{
						$formats[$format_key][$key]['txt'] .= $object[$field_name];
					}
					else
					{
						$formats[$format_key][$key]['txt'] .= $field_name;
					}
				}

				/*
				// addPage 判定
				$margins = $this->getMargins();
				$cellHeight = $this->getStringHeight($val['w'], $formats[$key]['txt']) + $this->getCellMargins()['B'];
				$h = isset($val['h']) ? $val['h'] : 0;
				$cellHeight = max($cellHeight, $h);


				if (($this->getPageHeight() - $margins['bottom'] - $margins['padding_bottom'] -  $this->getY() - $cellHeight) < 0) {
					// $pdf->Line($pdf->getX(), $pdf->getY(), $pdf->getX()+$width, $pdf->getY());
					$is_addPage = true;
				}
				 */

			}

			if ($this->is_add_page($formats, $objects, $object_key))
			{
				$this->drawTableLines($formats[$format_key], $startY);
				$this->addPage();
				$this->setX($startX);
				$startY = $this->getMargins()['top'];
			}
			$nextY = $this->TableBulk($object, $formats[$format_key], $rowspan_fields);

			foreach ($formats[$format_key] as $key => $val) {
				if (
					isset($formats[$format_key][$key]['rowspan']) &&
					intval($formats[$format_key][$key]['rowspan']) > 0
				)
				{
					$rowspan_fields[$key] = array(
						'rowspan' => intval($formats[$format_key][$key]['rowspan']) - 1,
						'w' => $formats[$format_key][$key]['w']
					);
				}
			}


			foreach ($rowspan_fields as $rowspan_key => $rowspan_field)
			{
				$rowspan_fields[$rowspan_key]['rowspan'] = $rowspan_field['rowspan'] - 1;
				if ($rowspan_field['rowspan'] < 1) unset($rowspan_fields[$rowspan_key]);
			}


		}
		$this->setX($startX);
		$this->drawTableLines(reset($formats), $startY, $nextY);
		$this->setXY($startX, $nextY);
	}

	/*
	 * 縦の線を書くカプセル化 (最左を除く)
	 */
	protected function drawTableLines($formats, $startY, $endY = null)
	{
		$total_width = 0;
		$x = $this->getX();
		if (is_null($endY)) $endY = $this->getY();
		$this->Line($x, $startY, $x, $endY);
		foreach ($formats as $val)
		{
			$val = static::tableWordBuffer($val);
			$total_width += $val['w']; // todo buffer
			$this->Line($x+$total_width, $startY, $x+$total_width, $endY);
		}
		$this->Line($x, $endY, $x+$total_width, $endY);
	}

	/*
	 * Bulk のテーブル用
	 * 横一列を描画
	 * @return 次の y 座標, pageBreak を挟むときは false
	 */
	protected function TableBulk($object, $formats, $rowspan_fields)
	{
		$maxY = 0;
		$is_addPage = false;

		$x = $this->getX();

		$x = $this->getX();
		$y = $this->getY();

		// $rowpan_fields があったら空のフィールドを追加してずらす
		// var_dump($rowspan_fields);
		if (isset($rowspan_fields)) {
			foreach ($rowspan_fields as $key => $val) {
				array_splice($formats, $key, 0, array(array(
					'border' => false,
					'w' => $val['w'],
				)));
			}
		}

		foreach ($formats as $key => $val)
		{
			$this->setXY($x, $y);
			$this->MultiBox($val);
			$maxY = max($this->getY() , $maxY);
			$val = static::tableWordBuffer($val);
			$x += $val['w'];
		}

		return $maxY;
	}

	/*
	 * @return bool
	 */
	protected function is_add_page($formats, $objects, $object_key)
	{
		$rowspan_max_count = 0; // return に使う
		$rowspan_count = 1;
		$current_font_size = $this->getFontSize();
		$margins = $this->getMargins();
		$cell_margins = $this->getCellMargins();

		$total_height = 0;


		do {
			$object = $objects[$object_key];
			$format_key = $object_key%count($formats);
			$format = $formats[$format_key];
			$max_cell_height = 0;
			foreach ($format as $key => $val)
			{
				if (
					isset($format[$key]['rowspan']) &&
					intval($format[$key]['rowspan']) > 0
				)
				{
					$rowspan_count = max( intval($format[$key]['rowspan']), $rowspan_count);
				}

				$format[$key]['txt'] = '';
				foreach($val['fields'] as $field_name)
				{
					if (is_object($object) && isset($object->{$field_name}))
					{
						$format[$key]['txt'] .= $object->{$field_name};
					}
					else if (is_array($object) && isset($object[$field_name]))
					{
						$format[$key]['txt'] .= $object[$field_name];
					}
					else
					{
						$format[$key]['txt'] .= $field_name;
					}
				}

				// フォントサイズが違う場合
				$is_diff_font_size = false;
				if (isset($format[$key]['font_size']) &&
					intval($format[$key]['font_size']) > 0 &&
					$current_font_size != intval($format[$key]['font_size'])
				)
				{
					$is_diff_font_size = true;
					$this->SetFontSize(intval($format[$key]['font_size']));
				}

				$cell_height = $this->getStringHeight($format[$key]['w'], $format[$key]['txt']) + $cell_margins['T'] + $cell_margins['B'];
				$max_cell_height = max($cell_height, $max_cell_height);

				// フォントサイズを戻す
				if ($is_diff_font_size) $this->SetFontSize($current_font_size);
			}



			$total_height += $max_cell_height;
			$rowspan_count--; // 次ぎに行く前に 残りを減らす
			$object_key++;
		} while ($rowspan_count > 0 && isset($objects[$object_key]));


		if (($this->getPageHeight() - $margins['bottom'] - $margins['padding_bottom'] -  $this->getY() - $total_height) < 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	 * Box options の key の揺れを吸収
	 */
	protected static function tableWordBuffer($options)
	{
		// key to val
		$buffers = array(
			'width' => 'w',
			'height' => 'h',
		);

		foreach ($buffers as $key => $val)
		{
			if (isset($options[$key]) and !isset($options[$val]))
			{
				$options[$val] = $options[$key];
			}
			
		}

		// もし w なければ、エラーを投げる
		if (!isset($options['w'])) throw new \Exception('function Table() の第2引数の配列には、それぞれ w(width) が必須です');

		return $options;
	}


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


}
