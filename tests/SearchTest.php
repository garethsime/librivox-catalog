<?php
use PHPUnit\Framework\TestCase;

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
}

/**
 * Queries the search API.
 *
 * This code is almost entirely just dealing with cURL.
 */
function querySearchApi(
    $reader = '',
) {
    $base_url = "https://localhost"; // So I can run it in my docker container
    $host = "librivox.org";
    $api_path = "/advanced_search";

    $params = // I'm sure there's a nice way to do this
            "title=" .
            "&author=" .
            "&reader=$reader" .
            "&keywords=" .
            "&genre_id=0" .
            "&status=all" .
            "&project_type=either" .
            "&recorded_language=" .
            "&sort_order=catalog_date" .
            "&search_page=1" .
            "&search_form=advanced" .
            "&q=";

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

