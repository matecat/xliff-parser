<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use CURLFile;
use DOMDocument;
use Exception;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffParser;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use Matecat\XmlParser\XmlDomLoader;
use PHPUnit\Framework\TestCase;
use stdClass;

abstract class Base extends TestCase
{
    protected function getTestFile(string $file): string
    {
        return (string)file_get_contents($this->getTestFilePath($file));
    }

    protected function getTestFilePath(string $file): string
    {
        return __DIR__ . '/../../../files/' . $file;
    }

    protected function markTestSkippedInCoverage(): void
    {
        $isCoverage = (bool)count(array_filter($_SERVER['argv'], fn($arg) => str_contains($arg, 'coverage') && !str_contains($arg, 'no-coverage')));

        if ($isCoverage) {
            $this->markTestSkipped(
                'This test is very expensive when coverage is enabled.',
            );
        }

    }

    /**
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    protected function getTestFileAsDOMElement(string $file): DOMDocument
    {
        return XmlDomLoader::load($this->getTestFile($file));
    }

    /**
     * @param array<string, mixed> $expected
     *
     * @throws InvalidXmlException
     * @throws XmlParsingException
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    protected function assertXliffEquals(string $file, array $expected = []): void
    {
        $parser = new XliffParser();

        $this->assertEquals($expected, $parser->xliffToArray($this->getTestFile($file)));
    }

    /**
     * Asserts that two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $array
     */
    protected function assertArraySimilar(array $expected, array $array): void
    {
        $this->assertTrue(count(array_diff_key($array, $expected)) === 0);

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                $this->assertArraySimilar($value, $array[$key]);
            } else {
                $this->assertStringContainsString(trim((string)$value), trim((string)$array[$key]));
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int|string, array<int, int>>
     */
    protected function getTransUnitsForReplacementTest(array $data): array
    {
        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k['internal_id'];

            $transUnits[$internalId][] = $i;

            $data['matecat|' . $internalId][] = $i;
        }

        return $transUnits;
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array{data: array<int|string, mixed>, transUnits: array<string, array<int, int>>}
     */
    protected function getData(array $data): array
    {
        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k['internal_id'];

            $transUnits[$internalId][] = $i;

            $data['matecat|' . $internalId][] = $i;
        }

        return [
            'data' => $data,
            'transUnits' => $transUnits,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $headers
     */
    protected function httpPost(string $url, array $data, array $headers): stdClass
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errorNo = curl_errno($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        $http = new stdClass();
        $http->body = $body;
        $http->error = $error;
        $http->errorNo = $errorNo;
        $http->info = $info;

        return $http;
    }

    /**
     * @return array<int, string>
     *
     * @throws Exception
     */
    protected function validateXliff20(string $xliff20): array
    {
        $sessionCurl = curl_init("https://dev.maxprograms.com/Validation/version");
        curl_setopt($sessionCurl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($sessionCurl);
        $sessionValue = json_decode(is_string($result) ? $result : '{}');

        $url = 'https://dev.maxprograms.com/Validation/upload';

        $response = $this->httpPost(
            $url,
            [
                'xliff' => new CURLFile($xliff20, "application/xliff+xml", "file.xliff")
            ],
            [
                'Content-Type: multipart/form-data',
                'schematron: no',
                'session: ' . $sessionValue->session
            ]
        );

        if ($response->info['http_code'] !== 200) {
            throw new Exception(
                ($response->errorNo > 0) ? $response->error : 'An error occurred calling ' . $url . '. Status code ' . $response->info['http_code'] . ' was returned'
            );
        }

        $result = json_decode($response->body);

        if ($result->status === "error") {
            return [$result->reason];
        }

        return [];
    }
}
