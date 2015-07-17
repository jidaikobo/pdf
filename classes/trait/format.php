<?php
namespace Pdf;

trait Trait_Format
{

	/**
	 * Utilities
	 */

	// 封筒印刷
	// 用紙サイズ某と宛名
	public function envelope(
		$title = null,
		$obj,
		$field_format_default = array(
			'name' => 'name',
			'zip' => 'zip',
			'address' => 'address',
		),
		$width = 150,
		$margins = array(0, 0),
		$cr="",
		$name_align = 'L',
		$change_format = false, // function change format
		$rotate = 0,
		$rotate_x = 115,
		$rotate_y = 0,
		$size = false,
		$orientation = 'P'
	)
	{
	}

	// タックシール

	// 郵便はがき




}
