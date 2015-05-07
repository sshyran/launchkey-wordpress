<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

class LaunchKey_WP_Template_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @Mock
	 * @var LaunchKey_WP_Global_Facade
	 */
	private $facade;

	/**
	 * @var LaunchKey_WP_Template
	 */
	private $template;

	public function test_render_template_reads_correct_file() {
		$expected = file_get_contents(__DIR__ . '/__fixtures/template.html');
		$actual = $this->template->render_template('template', array());
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @requires test_render_template_reads_correct_file
	 */
	public function test_render_template_translates_all_context_items() {
		$this->template->render_template('template', array('key1' => 'value1', 'key2' => 'value2'));
		Phake::verify($this->facade)->__('value1', 'test-language-domain');
		Phake::verify($this->facade)->__('value2', 'test-language-domain');
	}

	/**
	 * @requires test_render_template_reads_correct_file
	 */
	public function test_render_template_replaces_all_the_placeholders_with_translated_context_items() {
		$expected = "<template>\n    <key1>Translated [value1]</key1>\n    <key2>Translated [value2]</key2>\n</template>";
		$actual = $this->template->render_template('template', array('key1' => 'value1', 'key2' => 'value2'));
		$this->assertEquals($expected, $actual);
	}

	protected function setUp() {
		Phake::initAnnotations($this);
		$this->template = new LaunchKey_WP_Template(__DIR__ . '/__fixtures', $this->facade, 'test-language-domain');
		Phake::when($this->facade)->__(Phake::anyParameters())->thenReturnCallback(function ($method, $parameters) {
			return sprintf('Translated [%s]', $parameters[0]);
		});
	}

	protected function tearDown() {
		$this->facade = null;
		$this->template = null;
	}


}
