<?php

/**
 * SimpleTemplateTest.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2025 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\View\SimpleTemplate;

test('basic variable replacement', function (): void {
    $template = new SimpleTemplate('Hello {{ name }}!', ['name' => 'World']);
    expect((string) $template)->toEqual('Hello World!');
});

test('variable replacement with dollar prefix', function (): void {
    $template = new SimpleTemplate('Hello {{ $name }}!', ['name' => 'World']);
    expect((string) $template)->toEqual('Hello World!');
});

test('static parse method', function (): void {
    $result = SimpleTemplate::parse('Hello {{ name }}!', ['name' => 'World']);
    expect($result)->toEqual('Hello World!');
});

test('set variable', function (): void {
    $template = new SimpleTemplate('Hello {{ name }}!');
    $template->setVariable('name', 'World');
    expect((string) $template)->toEqual('Hello World!');
});

test('keep empty templates', function (): void {
    $template = new SimpleTemplate('Hello {{ missing }}!');
    expect((string) $template)->toEqual('Hello !');

    $template = new SimpleTemplate('Hello {{ missing }}!');
    $template->keepEmptyTemplates();
    expect((string) $template)->toEqual('Hello {{ missing }}!');
});

test('custom callback', function (): void {
    $template = new SimpleTemplate('Hello {{ name }}!');
    $template->replaceWith(fn ($matches) => strtoupper((string) $matches[1]));
    expect((string) $template)->toEqual('Hello NAME!');
});

test('trim filter', function (): void {
    $template = new SimpleTemplate('{{ value|trim }}', ['value' => '  Hello World  ']);
    expect((string) $template)->toEqual('Hello World');
});

test('upper filter', function (): void {
    $template = new SimpleTemplate('{{ value|upper }}', ['value' => 'hello']);
    expect((string) $template)->toEqual('HELLO');
});

test('lower filter', function (): void {
    $template = new SimpleTemplate('{{ value|lower }}', ['value' => 'HELLO']);
    expect((string) $template)->toEqual('hello');
});

test('title filter', function (): void {
    $template = new SimpleTemplate('{{ value|title }}', ['value' => 'hello world']);
    expect((string) $template)->toEqual('Hello World');
});

test('capitalize filter', function (): void {
    $template = new SimpleTemplate('{{ value|capitalize }}', ['value' => 'hello world']);
    expect((string) $template)->toEqual('Hello world');
});

test('length filter', function (): void {
    $template = new SimpleTemplate('{{ value|length }}', ['value' => 'Hello']);
    expect((string) $template)->toEqual('5');
});

test('replace filter', function (): void {
    $template = new SimpleTemplate('{{ value|replace("world", "universe") }}', ['value' => 'hello world']);
    expect((string) $template)->toEqual('hello universe');
});

test('replace filter with single quotes', function (): void {
    $template = new SimpleTemplate("{{ value|replace('\"anon\" ', '') }}", ['value' => 'john "anon" doe']);
    expect((string) $template)->toEqual('john doe');
});

test('replace filter replacement with single quotes', function (): void {
    $template = new SimpleTemplate("{{ value|replace(\"ryan's\", 'hello') }}", ['value' => "ryan's world"]);
    expect((string) $template)->toEqual('hello world');
});

test('replace filter with no quotes', function (): void {
    $template = new SimpleTemplate('{{ value|replace(world, universe) }}', ['value' => 'hello world']);
    expect((string) $template)->toEqual('hello universe');
});

test('slice filter', function (): void {
    $template = new SimpleTemplate('{{ value|slice(0, 5) }}', ['value' => 'Hello World']);
    expect((string) $template)->toEqual('Hello');
});

test('slice filter without length', function (): void {
    $template = new SimpleTemplate('{{ value|slice(6) }}', ['value' => 'Hello World']);
    expect((string) $template)->toEqual('World');
});

test('escape filter', function (): void {
    $template = new SimpleTemplate('{{ value|escape }}', ['value' => '<script>alert("xss")</script>']);
    expect((string) $template)->toEqual('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
});

test('escape filter with strategy', function (): void {
    $template = new SimpleTemplate('{{ value|escape("html") }}', ['value' => '<div>test</div>']);
    expect((string) $template)->toEqual('&lt;div&gt;test&lt;/div&gt;');
});

test('url encode filter', function (): void {
    $template = new SimpleTemplate('{{ value|url_encode }}', ['value' => 'hello world']);
    expect((string) $template)->toEqual('hello+world');
});

test('striptags filter', function (): void {
    $template = new SimpleTemplate('{{ value|striptags }}', ['value' => '<p>Hello <b>World</b></p>']);
    expect((string) $template)->toEqual('Hello World');
});

test('nl2br filter', function (): void {
    $template = new SimpleTemplate('{{ value|nl2br }}', ['value' => "Hello\nWorld"]);
    expect((string) $template)->toEqual("Hello<br />\nWorld");
});

test('raw filter', function (): void {
    $template = new SimpleTemplate('{{ value|raw }}', ['value' => '<b>Hello</b>']);
    expect((string) $template)->toEqual('<b>Hello</b>');
});

test('number format filter', function (): void {
    $template = new SimpleTemplate('{{ value|number_format(2) }}', ['value' => '1234.5678']);
    expect((string) $template)->toEqual('1,234.57');
});

test('number format filter with custom separators', function (): void {
    $template = new SimpleTemplate('{{ value|number_format(2, ".", " ") }}', ['value' => '1234.5678']);
    expect((string) $template)->toEqual('1 234.57');
});

test('date filter', function (): void {
    $template = new SimpleTemplate('{{ value|date("Y-m-d") }}', ['value' => '2023-12-25 15:30:00']);
    expect((string) $template)->toEqual('2023-12-25');
});

test('date filter with timestamp', function (): void {
    $timestamp = mktime(15, 30, 0, 12, 25, 2023);
    $template = new SimpleTemplate('{{ value|date("Y-m-d H:i") }}', ['value' => (string) $timestamp]);
    expect((string) $template)->toEqual('2023-12-25 15:30');
});

test('json encode filter', function (): void {
    $template = new SimpleTemplate('{{ value|json_encode }}', ['value' => 'Hello "World"']);
    expect((string) $template)->toEqual('"Hello \"World\""');
});

test('default filter', function (): void {
    $template = new SimpleTemplate('{{ value|default("fallback") }}', ['value' => '']);
    expect((string) $template)->toEqual('fallback');
});

test('default filter with value', function (): void {
    $template = new SimpleTemplate('{{ value|default("fallback") }}', ['value' => 'actual']);
    expect((string) $template)->toEqual('actual');
});

test('abs filter', function (): void {
    $template = new SimpleTemplate('{{ value|abs }}', ['value' => '-42']);
    expect((string) $template)->toEqual('42');
});

test('round filter', function (): void {
    $template = new SimpleTemplate('{{ value|round(2) }}', ['value' => '3.14159']);
    expect((string) $template)->toEqual('3.14');
});

test('round filter without precision', function (): void {
    $template = new SimpleTemplate('{{ value|round }}', ['value' => '3.7']);
    expect((string) $template)->toEqual('4');
});

test('chained filters', function (): void {
    $template = new SimpleTemplate('{{ value|trim|upper }}', ['value' => '  hello  ']);
    expect((string) $template)->toEqual('HELLO');
});

test('complex chained filters', function (): void {
    $template = new SimpleTemplate('{{ value|slice(0, 5)|upper }}', ['value' => 'hello world']);
    expect((string) $template)->toEqual('HELLO');
});

test('dot notation variables', function (): void {
    $template = new SimpleTemplate('{{ user.name }}', ['user.name' => 'John Doe']);
    expect((string) $template)->toEqual('John Doe');
});

test('unknown filter', function (): void {
    $template = new SimpleTemplate('{{ value|unknown_filter }}', ['value' => 'test']);
    expect((string) $template)->toEqual('test');
});

test('multiple variables', function (): void {
    $template = new SimpleTemplate('{{ greeting }} {{ name }}!', [
        'greeting' => 'Hello',
        'name' => 'World',
    ]);
    expect((string) $template)->toEqual('Hello World!');
});

test('complex template', function (): void {
    $template = new SimpleTemplate(
        'Welcome {{ name|title }}! You have {{ count|number_format }} {{ item|lower }}{{ count|default("1")|slice(-1)|escape("js") != "1" ? "s" : "" }}.',
        [
            'name' => 'john doe',
            'count' => '1234',
            'item' => 'MESSAGE',
        ]
    );

    // SimpleTemplate doesn't support ternary, so this will be failed a bit
    // $expected = 'Welcome John Doe! You have 1,234 messages.';
    expect((string) $template)->toEqual('Welcome John Doe! You have 1,234 message"4".');

    $simpleTemplate = new SimpleTemplate('Welcome {{ name|title }}! You have {{ count|number_format }} {{ item|lower }}.', [
        'name' => 'john doe',
        'count' => '1234',
        'item' => 'MESSAGE',
    ]);
    expect((string) $simpleTemplate)->toEqual('Welcome John Doe! You have 1,234 message.');
});
