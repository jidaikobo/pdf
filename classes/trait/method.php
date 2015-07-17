<?php
namespace Pdf;

trait Trait_Method
{
	/*
	 * Bulk
	 * @param Model or array $object
	 * @param array $values
	 */
	public function Bulk($object, $values)
	{
		foreach ($values as $key => $val)
		{
			if ( !isset($val['fields']) ) continue;

			$val['txt'] = '';
			foreach($val['fields'] as $field_name)
			{
				if (is_object($object) && isset($object->{$field_name}))
				{
					$val['txt'] .= $object->{$field_name};
				}
				else if (is_object($object) && isset($object[$field_name]))
				{
					$val['txt'] .= $object[$field_name];
				}
				else
				{
					$val['txt'] .= $field_name;
				}
			}

			$this->MultiBox($val);

		}
	}


	/*
	 * @param array $objects
	 * @param array $values
	 */
	public function Table($objects, $values)
	{
		$startX = $this->getX();
		$startY = $nextY = $this->getY();

		$defaults = array(
			'border' => 'T',
			'ln' => 1,
		);

		if (!isset($values[0][0])) {
			$values = array($values);
		}

		for ($i = 0; $i < count($values); $i++) {
			$values_length = count($values[$i]);

			for ($j = 0; $j < $values_length; $j++)
			{
				/*
				if ($j == ($values_length -1))
				{
					$defaults['ln'] = 1;
				}
				 */
				$values[$i][$j] = array_merge($defaults, $values[$i][$j]);
			}

		}

		$row_count = 0;
		foreach ($objects as $key => $object)
		{
			// todo xの位置
			$this->setXY($startX, $nextY);
			// $values[0]['fields'][1] = $nextY;

			$arg_values = $values[$row_count%count($values)];

			$nextY = $this->TableBulk($object, $arg_values);
			if (!$nextY)
			{
				$this->drawTableLines($arg_values, $startY);
				$this->addPage();
				$startY = $this->getMargins()['top'];
				$nextY = $this->TableBulk($object, $arg_values);
			}
			$row_count++;
		}
		$this->setX($startX);
		$this->drawTableLines($arg_values, $startY, $nextY);
		$this->setXY($startX, $nextY);
	}

	/*
	 * 縦の線を書くカプセル化 (最左を除く)
	 */
	protected function drawTableLines($values, $startY, $endY = null)
	{
		$total_width = 0;
		$x = $this->getX();
		if (is_null($endY)) $endY = $this->getY();
		$this->Line($x, $startY, $x, $endY);
		foreach ($values as $val)
		{
			$val = static::tableWordBuffer($val);
			$total_width += $val['w']; // todo buffer
			$this->Line($x+$total_width, $startY, $x+$total_width, $endY);
		}
		$this->Line($x, $endY, $x+$total_width, $endY);
	}

	/*
	 * Bulk のテーブル用
	 * @return 次の y 座標, pageBreak を挟むときは false
	 */
	protected function TableBulk($object, $values)
	{
		$maxY = 0;
		$is_addPage = false;

		$x = $this->getX();

		foreach ($values as $key => $val)
		{
			$val = static::tableWordBuffer($val);
			if ( !isset($val['fields']) ) continue;

			$values[$key]['txt'] = '';
			foreach($val['fields'] as $field_name)
			{
				if (is_object($object) && isset($object->{$field_name}))
				{
					$values[$key]['txt'] .= $object->{$field_name};
				}
				else if (is_array($object) && isset($object[$field_name]))
				{
					$values[$key]['txt'] .= $object[$field_name];
				}
				else
				{
					$values[$key]['txt'] .= $field_name;
				}
			}

			// addPage 判定
			$margins = $this->getMargins();
			$cellHeight = $this->getStringHeight($val['w'], $values[$key]['txt']) + $this->getCellMargins()['B'];
			$h = isset($val['h']) ? $val['h'] : 0;
			$cellHeight = max($cellHeight, $h);


			if (($this->getPageHeight() - $margins['bottom'] - $margins['padding_bottom'] -  $this->getY() - $cellHeight) < 0) {
				// $pdf->Line($pdf->getX(), $pdf->getY(), $pdf->getX()+$width, $pdf->getY());
				$is_addPage = true;
			}
		}

		// addPage が必要なら描画せずに返す
		if ($is_addPage) return false;

		$x = $this->getX();
		$y = $this->getY();
		foreach ($values as $key => $val)
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

}
