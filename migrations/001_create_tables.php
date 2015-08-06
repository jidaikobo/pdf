<?php
namespace Fuel\Migrations;
class Create_Tables
{
	public function up()
	{
		echo "create pdfs table.\n";
		\DBUtil::create_table('pdfs', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true, 'unsigned' => true),
			'name' => array('type' => 'varchar',  'default' => '', 'constraint' => 255),
			'w' => array('type' => 'double',   'default' => 0.0,),
			'h' => array('type' => 'double',   'default' => 0.0,),
		), array('id'));

		echo "create pdf_formats table.\n";
		\DBUtil::create_table('pdf_elements', array(
			'id'             => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true, 'unsigned' => true),
			'pdf_id'         => array('type' => 'int',      'default' => 0,  'constraint' => 11,),
			'name'           => array('type' => 'varchar',  'default' => '', 'constraint' => 255),
			'seq'            => array('type' => 'int',      'default' => 0,  'constraint' => 11,),
			'x'              => array('type' => 'double',   'default' => 0.0,),
			'ln_x'           => array('type' => 'bool',     'default' => 0),
			'y'              => array('type' => 'double',   'default' => 0.0,),
			'ln_y'           => array('type' => 'bool',     'default' => 0),
			'w'              => array('type' => 'double',   'default' => 0.0,),
			'h'              => array('type' => 'double',   'default' => 0.0,),
			'h_adjustable'   => array('type' => 'bool',     'default' => 0),
			'padding_left'   => array('type' => 'double',   'default' => 0.0,),
			'padding_top'    => array('type' => 'double',   'default' => 0.0,),
			'padding_right'  => array('type' => 'double',   'default' => 0.0,),
			'padding_bottom' => array('type' => 'double',   'default' => 0.0,),
			'margin_left'    => array('type' => 'double',   'default' => 0.0,),
			'margin_top'     => array('type' => 'double',   'default' => 0.0,),
			'txt'            => array('type' => 'text',     'default' => ''),
			'font_size'      => array('type' => 'double',   'default' => 0.0,),
			'font_family'    => array('type' => 'varchar',  'default' => '', 'constraint' => 50,),
			'align'          => array('type' => 'varchar',  'default' => '', 'constraint' => 50,),
			'valign'         => array('type' => 'varchar',  'default' => '', 'constraint' => 50,),
			'border_width'   => array('type' => 'double',   'default' => 0.0,),
			'border_left'    => array('type' => 'bool',     'default' => 0),
			'border_top'     => array('type' => 'bool',     'default' => 0),
			'border_right'   => array('type' => 'bool',     'default' => 0),
			'border_bottom'  => array('type' => 'bool',     'default' => 0),
		), array('id'));
	}

	public function down()
	{
		echo "drop lcm_acls table.\n";
		\DBUtil::drop_table('pdfs');
		\DBUtil::drop_table('pdf_elements');
	}
}
