<?php
namespace WeAreHausTech\Queries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;


class BaseQuery
{
    protected $query = '';

    function fetch($languageCode = 'sv')
    {
        try {
            $response = (new Client([
            'base_uri' => VENDURE_API_URL .'?languageCode=' . $languageCode,
            'headers' => [
                'Content-Type' => 'application/json',
                'vendure-token'=> VENDURE_TOKEN,
                ],
                'body' => json_encode([
                    'query' => $this->query,
                ]),
            ]))->request('POST', '');

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            if ($statusCode === 200 && $responseBody) {
                return json_decode($responseBody, true);
            }
            return null;
        } catch (RequestException $e) {
            return null;
        } catch (GuzzleException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}