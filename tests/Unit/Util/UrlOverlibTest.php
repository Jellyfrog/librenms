<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Url;


beforeEach(function () {
    LibrenmsConfig::set('web_mouseover', true);
});

/**
 * Return the onmouseover attribute as the browser would see it (entities decoded)
 */
function mouseover(string $html): string
{
    $dom = new DOMDocument;
    $dom->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_NOERROR);

    return $dom->getElementsByTagName('a')->item(0)->getAttribute('onmouseover');
}

test('overlib content escapes text', function () {
    $content = Url::overlibContent(['type' => 'storage_usage', 'id' => 1], "host - C:\\ ');alert(1);// <img src=x onerror=alert(2)>");
    $script = mouseover(Url::overlibLink('#', 'text', $content));

    expect($script)->toStartWith("return overlib('");
    $this->assertStringNotContainsString("');alert(1)", $script);
    $this->assertStringNotContainsString('<img src=x', $script);
    $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $script);
    $this->assertStringContainsString('C:\\\\ ', $script);
});

test('overlib link keeps encoded entities as text', function () {
    $script = mouseover(Url::overlibLink('#', 'text', 'name &#039;);alert(1);// &lt;img src=x onerror=alert(2)&gt;'));

    $this->assertStringNotContainsString("');alert(1)", $script);
    $this->assertStringNotContainsString('<img src=x', $script);
    $this->assertStringContainsString('&#039;);alert(1);// &lt;img src=x onerror=alert(2)&gt;', $script);
});

test('overlib link preserves markup', function () {
    $script = mouseover(Url::overlibLink('/graphs', 'text', "<div class=\\'test\\'><img src=\\'/graph.php?a=1&b=2\\' /></div>"));

    $this->assertStringContainsString("<div class=\\'test\\'><img src=\\'/graph.php?a=1&b=2\\' /></div>", $script);
});
