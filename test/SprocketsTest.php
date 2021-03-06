<?php
require_once dirname(__FILE__).'/../lib/Sprockets.php';

class SprocketsTest extends PHPUnit_Framework_TestCase {

	public function testRequiringSingleFileShouldReplaceRequireCommentWithFileContents () {
		$sprockets = $this->getNewSprockets(
			'requiring_a_single_file_should_replace_the_require_comment_with_the_file_contents.js'
		);
		$this->assertEquals(
			"var before_require;\nvar Foo = { };\n\nvar after_require;\n",
			$sprockets->render(true)
		);
	}

	public function testRequiringTheCurrentFileShouldDoNothing() {
		$sprockets = $this->getNewSprockets(
			'requiring_the_current_file_should_do_nothing.js'
		);
		$this->assertEquals(
			"\n",
			$sprockets->render(true)
		);
	}

	public function testRequiringFileThatDoesNotExistShouldRaiseAnError() {
		$sprockets = $this->getNewSprockets(
			'requiring_a_file_that_does_not_exist_should_raise_an_error.js'
		);
		$this->setExpectedException('SprocketsFileNotFoundException');
		$this->assertEquals(
			"this is going to cause an exception",
			$sprockets->render(true)
		);
	}

	public function testSinglelineCommentsShouldBeRemovedByDefault() {
		$sprockets = $this->getNewSprockets(
			'singleline_comments_should_be_removed_by_default.js'
		);
		$this->assertEquals(
			"var lorem = 'ipsum';	// no way w/o real parser",
			trim($sprockets->render(true)),
			'accepting inline comments'
		);
	}

	protected function getNewSprockets($file) {
		$fileWithPath =
			dirname(__FILE__) .
			DIRECTORY_SEPARATOR .
			'fixtures' .
			DIRECTORY_SEPARATOR .
			$file;

		$baseFolder =
			dirname(__FILE__) .
			DIRECTORY_SEPARATOR .
			'fixtures' .
			DIRECTORY_SEPARATOR .
			'src';

		return new Sprockets(
			$fileWithPath,
			array(
				'baseUri' => '../',
				'baseFolder' => $baseFolder,
				'debugMode' => true,
				'autoRender' => false,
			)
		);
	}
}
