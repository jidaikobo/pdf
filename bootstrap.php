<?php
/**
 */

Autoloader::add_core_namespace('Pdf');

Autoloader::add_classes(array(
	'Pdf\\Pdf'            => __DIR__ . '/classes/pdf.php',
	'Pdf\\Trait_Wrapper'  => __DIR__ . '/classes/trait/wrapper.php',
	'Pdf\\Trait_Method'   => __DIR__ . '/classes/trait/method.php',
	'Pdf\\Trait_Vertical' => __DIR__ . '/classes/trait/vertical.php',
	'Pdf\\Trait_Format'   => __DIR__ . '/classes/trait/format.php',
	// 'Pdf\\Trait_Fullwrap'   => __DIR__ . '/classes/trait/fullwrap.php',
));
