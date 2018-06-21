<?php

namespace ConvertApi;

class Task
{
    function __construct($fromFormat, $toFormat, $params, $conversionTimeout = null)
    {
        $this->fromFormat = $fromFormat;
        $this->toFormat = $toFormat;
        $this->params = $params;
        $this->conversionTimeout = $conversionTimeout ?: ConvertApi::$conversionTimeout;
    }

    function run()
    {
        $params = array_merge(
            $this->normalizedParams(),
            [
                'Timeout' => $this->conversionTimeout,
                'StoreFile' => 'true',
            ]
        );

        $fromFormat = $this->fromFormat ?: $this->detectFormat($params);
        $readTimeout = $this->conversionTimeout + ConvertApi::$conversionTimeoutDelta;
        $path = $fromFormat . '/to/' . $this->toFormat;

        $response = ConvertApi::client()->post($path, $params, $readTimeout);

        return new Result($response);
    }

    private function normalizedParams()
    {
        $result = [];

        foreach ($this->params as $key => $val)
        {
            switch($key) {
                case 'File':
                    $result[$key] = FileParam::build($val);
                    break;

                case 'Files':
                    $result[$key] = $this->filesBatch($val);
                    break;

                default:
                    $result[$key] = $val;
            }
        }

        return $result;
    }

    private function filesBatch($values)
    {
        $files = [];

        foreach ($values as $val)
            $files[] = FileParam::build($val);

        return $files;
    }

    private function detectFormat($params)
    {
        $resource = $params['File'];
        $resource = $resource ?: $params['Url'];
        $resource = $resource ?: $params['Files'][0];

        $detector = new FormatDetector($resource);

        return $detector->run();
    }
}