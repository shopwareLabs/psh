<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Config\SimpleValueProvider;
use Shopware\Psh\ScriptRuntime\Execution\MissingRequiredParameter;
use Shopware\Psh\ScriptRuntime\Execution\TemplateEngine;
use function preg_match_all;

class TemplateEngineTest extends TestCase
{
    private $fixtures = [
        'mysql -u __USER__ -p__PASSWORD__ __DATABASE__' => 3,
        'ssh -u __USER__ -p__PASSWORD__ __DATABASE__' => 3,
        'nope __not__ _matching_ anything' => 0,
        'nope __NOT MATCHING ANYTHING__' => 0,
        'juos __NOT_MATCHING_ANYTHING__' => 1,
        'curl http://__APP_HOST____APP_PATH__' => 2,
    ];

    public function test_regex_matches_fixtures(): void
    {
        foreach ($this->fixtures as $fixture => $expectedOccurrences) {
            preg_match_all(TemplateEngine::REGEX, $fixture, $matches);
            self::assertCount($expectedOccurrences, $matches[0]);
        }
    }

    public function test_it_renders_successfully_with_no_placeholders(): void
    {
        $engine = new TemplateEngine();
        self::assertEquals('foo', $engine->render('foo', []));
        self::assertEquals('foo', $engine->render('foo', ['BAR' => new SimpleValueProvider('baz')]));
    }

    public function test_it_replaces_a_value(): void
    {
        $engine = new TemplateEngine();
        self::assertEquals('foo baz', $engine->render('foo __BAR__', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz', $engine->render('foo __BAR_1__', ['BAR_1' => new SimpleValueProvider('baz')]));
        self::assertEquals('baz foo', $engine->render('__BAR__ foo', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz foo', $engine->render('foo __BAR__ foo', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz foo', $engine->render('foo __FO-BAR__ foo', ['FO-BAR' => new SimpleValueProvider('baz')]));

        self::assertEquals('foo __BAR__', $engine->render('foo __BAR__(sic!)', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('__BAR__ foo', $engine->render('__BAR__(sic!) foo', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('__BAR_1__ foo', $engine->render('__BAR_1__(sic!) foo', ['BAR_1' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo __BAR__ foo', $engine->render('foo __BAR__(sic!) foo', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo __FO-BAR__ foo', $engine->render('foo __FO-BAR__(sic!) foo', ['FO-BAR' => new SimpleValueProvider('baz')]));
    }

    public function test_values_can_be_set_case_insensitive(): void
    {
        $engine = new TemplateEngine();
        self::assertEquals('foo baz', $engine->render('foo __BAR__', ['BAR' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz', $engine->render('foo __BAR__', ['Bar' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz', $engine->render('foo __BAR__', ['bar' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz', $engine->render('foo __BAR__', ['baR' => new SimpleValueProvider('baz')]));
        self::assertEquals('foo baz', $engine->render('foo __BAR__', ['bAr' => new SimpleValueProvider('baz')]));
    }

    public function test_it_throw_exceptions_for_missing_values(): void
    {
        $engine = new TemplateEngine();

        $this->expectException(MissingRequiredParameter::class);
        $engine->render('foo __BAR__, __BUZ__', ['BAR' => new SimpleValueProvider('baz')]);
    }

    public function test_it_replaces_multiple_values(): void
    {
        $engine = new TemplateEngine();

        self::assertEquals(
            'ssh -u foo -pfoo th_db',
            $engine->render(
                'ssh -u __USER__ -p__PASSWORD__ __DATABASE__',
                [
                    'user' => new SimpleValueProvider('foo'),
                    'password' => new SimpleValueProvider('foo'),
                    'database' => new SimpleValueProvider('th_db'),
                ]
            )
        );
    }

    public function test_values_can_be_concatenated(): void
    {
        $engine = new TemplateEngine();

        self::assertEquals(
            'curl http://shopware.com/shopware/__MYKEY__',
            $engine->render(
                'curl http://__APP_HOST____APP_PATH____MYKEY__(sic!)',
                [
                    'app_host' => new SimpleValueProvider('shopware.com'),
                    'app_path' => new SimpleValueProvider('/shopware/'),
                ]
            )
        );

        self::assertEquals(
            'curl http://shopware.com/shopware/__MYKEY__',
            $engine->render(
                'curl http://__APP-HOST____APP-PATH____MYKEY__(sic!)',
                [
                    'app-host' => new SimpleValueProvider('shopware.com'),
                    'app-path' => new SimpleValueProvider('/shopware/'),
                ]
            )
        );
    }
}
