<?php
require("page.php");

class AboutPage extends Page {

	/// protected methods
	protected function getTitle() {
		return "About";
	}

	protected function renderContent() {
		?>
		<?php
	}

	protected function renderHEAD() {
		parent::renderHEAD();
	}
};

$page = new AboutPage();
?>