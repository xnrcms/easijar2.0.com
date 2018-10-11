<?php
class ControllerStartupSass extends Controller {
	public function index() {
		// Build admin bootstrap.css
		$file = DIR_APPLICATION . 'view/stylesheet/bootstrap.css';
		if (!is_file($file) || !$this->config->get('developer_sass')) {
			$scss = new \Leafo\ScssPhp\Compiler();
			$scss->setImportPaths(DIR_APPLICATION . 'view/stylesheet/sass/');
			$scss->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
			$output = $scss->compile('@import "_bootstrap.scss"');

			$handle = fopen($file, 'w');
			flock($handle, LOCK_EX);
			fwrite($handle, $output);
			fflush($handle);
			flock($handle, LOCK_UN);
			fclose($handle);
		}

		// Build admin stylesheet.css
		$file = DIR_APPLICATION . 'view/stylesheet/stylesheet.css';
		if (!is_file($file) || !$this->config->get('developer_sass')) {
			$scss = new \Leafo\ScssPhp\Compiler();
			$scss->setImportPaths(DIR_APPLICATION . 'view/scss/');
			$scss->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
			$output = $scss->compile('@import "stylesheet.scss"');

			$handle = fopen($file, 'w');
			flock($handle, LOCK_EX);
			fwrite($handle, $output);
			fflush($handle);
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}
}
