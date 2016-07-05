<?php declare(strict_types = 1);


namespace Shopware\Psh\Test\Unit\ScriptRuntime;


use Shopware\Psh\ScriptRuntime\TemplateEngine;

class TemplateEngineTest extends \PHPUnit_Framework_TestCase
{
    private $fixtures = [
        'mysql -u __USER__ -p__PASSWORD__ __DATABASE__' => 3,
        'ssh -u __USER__ -p__PASSWORD__ __DATABASE__' => 3,
        'nope __not__ _matching_ anything' => 0,
        'nope __NOT MATCHING ANYTHING__' => 0,
        'juos __NOT_MATCHING_ANYTHING__' => 1,
    ];

    public function test_regex_matches_fixtures()
    {
        foreach($this->fixtures as $fixture => $expectedOccurrences) {
            preg_match_all(TemplateEngine::REGEX, $fixture, $matches);
            $this->assertCount($expectedOccurrences, $matches[0]);
        }
    }


    public function test_it_renders_successfully_with_no_placeholders()
    {
        $engine = new TemplateEngine();
        $this->assertEquals('foo', $engine->render('foo', []));
        $this->assertEquals('foo', $engine->render('foo', ['BAR' => 'baz']));
    }

    public function test_it_replaces_a_value()
    {
        $engine = new TemplateEngine();
        $this->assertEquals('foo baz', $engine->render('foo __BAR__', ['BAR' => 'baz']));
        $this->assertEquals('baz foo', $engine->render('__BAR__ foo', ['BAR' => 'baz']));
        $this->assertEquals('foo baz foo', $engine->render('foo __BAR__ foo', ['BAR' => 'baz']));
    }

    public function test_values_can_be_set_case_insensitive()
    {
        $engine = new TemplateEngine();
        $this->assertEquals('foo baz', $engine->render('foo __BAR__', ['BAR' => 'baz']));
        $this->assertEquals('foo baz', $engine->render('foo __BAR__', ['Bar' => 'baz']));
        $this->assertEquals('foo baz', $engine->render('foo __BAR__', ['bar' => 'baz']));
        $this->assertEquals('foo baz', $engine->render('foo __BAR__', ['baR' => 'baz']));
        $this->assertEquals('foo baz', $engine->render('foo __BAR__', ['bAr' => 'baz']));
    }

    public function test_it_throw_exceptions_for_missing_values()
    {
        $engine = new TemplateEngine();

        $this->expectException(\RuntimeException::class);
        $engine->render('foo __BAR__, __BUZ__', ['BAR' => 'baz']);
    }

    public function test_it_replaces_multiple_values()
    {
        $engine = new TemplateEngine();

        $this->assertEquals(
            'ssh -u foo -pfoo th_db',
            $engine->render(
                'ssh -u __USER__ -p__PASSWORD__ __DATABASE__',
                ['user' => 'foo', 'password' => 'foo', 'database' => 'th_db']
            )
        );
    }
}
