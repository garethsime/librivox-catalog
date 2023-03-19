<?php
use PHPUnit\Framework\TestCase;

/**
 * Test for the advanced search API.
 *
 * I didn't really have a good strategy for this. Normally, for this kind of
 * integration test, I would do one of these two:
 *
 *   - Have each test set up it's own state
 *   - Have some known stub data in the database
 *
 * The first is ideal, but I don't know enough about the APIs to know whether
 * I can just _create_ authors, readers, books, projects, etc. on demand.
 *
 * So, for now, I'm just testing against the data dump that's provided
 * for running locally. This will make the test a bit brittle as they could
 * start failing when new dumps become available.
 */
final class SearchTest extends TestCase
{
    public function testAdvancedSearch_NoParameters(): void
    {
        $body_json = querySearchApi();
        $body = json_decode($body_json);

        $this->assertIsObject($body);
        $this->assertSame($body->status, 'SUCCESS');
        $this->assertSame($body->search_page, '1');
        $this->assertTrue(property_exists($body, 'results')); // HTML blobs, hard to assert
        $this->assertTrue(property_exists($body, 'pagination'));
    }

    public function testAdvancedSearch_WithParameters(): void
    {
        // Always returns empty on scrubbed data since reader names disappear
        $body_json = querySearchApi(
            reader: 'Sally',
        );

        $this->assertJsonStringEqualsJsonString(
            $body_json,
            json_encode([
                'status' => 'SUCCESS',
                'results' => 'No results found',
                'pagination' => '',
                'search_page' => '1',
            ])
        );
    }

    public function testAdvancedSearch_SingleResultByTitleAndReader(): void
    {
        // This test is going to be pretty fragile. If the scrubbed data changes, then it could break

        $body_json = querySearchApi(
            title: 'For love and life vol 1',
            reader: 'LibriVoxer 82',
        );
        $body = json_decode($body_json);

        $this->assertIsObject($body);
        $this->assertSame($body->status, 'SUCCESS');
        $this->assertSame($body->search_page, '1');
        $this->assertSame($body->pagination, '');

        $expected_results = '
<li class="catalog-result">
    <div class="catalog-type"><span class="title-icon"></span>Title</div>

    <a href="https://librivox.org/for-love-and-life-vol-1-by-margaret-o-oliphant/" class="book-cover">
        <img src="https://archive.org/download/love_and_life_1_2201_librivox/love_life_2201_thumb.jpg" alt="book-cover-65x65" width="65" height="65" />
    </a>

    <div class="result-data">
        <h3><a href="https://librivox.org/for-love-and-life-vol-1-by-margaret-o-oliphant/">For Love and Life Vol. 1</a></h3>
        <p class="book-author"> <a href="https://librivox.org/author/4609"> Margaret O. Oliphant <span class="dod-dob">(1828 - 1897)</span></a> </p>
        <p class="book-meta"> Complete | Collaborative | English</p>
    </div>

    <div class="download-btn">
        <a href="https://www.archive.org/download/love_and_life_1_2201_librivox/love_and_life_1_2201_librivox_64kb_mp3.zip">Download</a>
        <span>231MB</span>
    </div>
</li>
';

        $this->assertXmlStringEqualsXmlString($body->results, $expected_results);
    }
}

/**
 * Queries the search API.
 *
 * This code is almost entirely just dealing with cURL.
 */
function querySearchApi(
    $title = '',
    $reader = '',
) {
    $base_url = "https://localhost"; // So I can run it in my docker container
    $host = "librivox.org";
    $api_path = "/advanced_search";

    $params = http_build_query([
        'title' => $title,
        'author' => '',
        'reader' => $reader,
        'keywords' => '',
        'genre_id' => '0',
        'status' => 'all',
        'project_type' => 'either',
        'recorded_language' => '',
        'sort_order' => 'catalog_date',
        'search_page' => '1',
        'search_form' => 'advanced',
        'q' => '',
    ]);

    $url = "$base_url$api_path?$params";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Host: $host",
        "Accept: application/json",
        "Accept-Language: en-US,en;q=0.5",
        "X-Requested-With: XMLHttpRequest" // Gotta have it. Why? Just gotta. (Returns empty body otherwise)
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignore my dodgy, self-signed certificate
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore my dodgy, self-signed certificate
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 25); // Seconds

    try {
        $result = curl_exec($ch);

        $error = curl_error($ch);
        if (!empty($error)) {
            throw new ErrorException($error);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            throw new ErrorException("HTTP status was $status. Body: $result");
        }

        return $result;
    } finally {
        curl_close($ch);
    }
}

