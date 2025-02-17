<?php

namespace spec\Tuurbo\Spreedly;

use GuzzleHttp\Psr7\Stream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\StreamInterface;

class ClientSpec extends ObjectBehavior
{
    const GATEWAY_TOKEN = '...GATEWAY_TOKEN...';
    const PAYMENT_TOKEN = '...PAYMENT_TOKEN...';
    const BASE_URL = 'https://core.spreedly.com/';
    const END_POINT = 'v1/fake_url';

    public function let(Client $client)
    {
        $config = [
            'key' => '12345',
            'secret' => '67890',
        ];

        $this->beConstructedWith($client, $config);
    }

    public function letGo()
    {
        $this->shouldReturnAnInstanceOf('Tuurbo\Spreedly\Client');
    }

    public function it_returns_an_array()
    {
        $array = [
            'gateway' => [
                'paypal' => [
                    'test' => 2,
                ],
            ],
        ];

        $this->setResponse($array);

        $this->response()->shouldReturn($array['gateway']);
    }

    public function it_sets_base_url_with_trailing_slash($client)
    {
        $this->beConstructedWith($client, [
            'key' => '12345',
            'secret' => '67890',
            'base_url' => 'https://example.com/',
        ]);
        $this->getBaseUrl()->shouldReturn('https://example.com/');
    }

    public function it_sets_base_url_without_trailing_slash($client)
    {
        $this->beConstructedWith($client, [
            'key' => '12345',
            'secret' => '67890',
            'base_url' => 'https://example.com',
        ]);
        $this->getBaseUrl()->shouldReturn('https://example.com/');
    }

    public function it_sets_base_url_with_null($client)
    {
        $this->beConstructedWith($client, [
            'key' => '12345',
            'secret' => '67890',
            'base_url' => null,
        ]);
        $this->getBaseUrl()->shouldReturn(self::BASE_URL);
    }

    public function it_sets_base_url_with_invalid_url($client)
    {
        $this->beConstructedWith($client, [
            'key' => '12345',
            'secret' => '67890',
            'base_url' => 'abcd',
        ]);
        $this->getBaseUrl()->shouldReturn(self::BASE_URL);
    }

    public function it_returns_an_array_without_any_keys_containing_an_at_symbol_attribute()
    {
        $this->setResponse([
            'gateway' => [
                'paypal' => [
                    'test' => 2,
                ],
            ],
        ]);

        $this->response()->shouldReturn([
            'paypal' => [
                'test' => 2,
            ],
        ]);
    }

    public function it_returns_an_array_of_errors($client)
    {
        $errors = [
            'errors' => [
                [
                    'key' => 'broken',
                    'message' => 'something went wrong',
                ],
            ],
        ];

        $this->setResponse($errors);

        $this->errors()
            ->shouldReturn($errors['errors']);
    }

    public function it_returns_a_string_of_errors($client)
    {
        $errors = [
            'errors' => [
                [
                    'key' => 'broken',
                    'message' => 'something went wrong',
                ],
            ],
        ];

        $this->setResponse($errors);

        $errors = array_map(function ($error) {
            return $error['message'];
        }, $errors['errors']);

        $this->errors(true)
            ->shouldReturn(implode(', ', $errors));
    }

    public function it_return_an_instance_of_itself($client)
    {
        $client->get(self::BASE_URL.self::END_POINT, Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new ClientStub200());

        $this->get(self::END_POINT)->shouldReturn($this);
    }

    public function it_throws_an_exception_if_http_response_is_404($client)
    {
        $client->get(self::BASE_URL.self::END_POINT, Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new ClientStub404());

        $this->shouldThrow('Tuurbo\Spreedly\Exceptions\NotFoundHttpException')
            ->duringGet(self::END_POINT);
    }

    public function it_sets_status_to_success_if_transaction_succeeds($client)
    {
        $client->get(self::BASE_URL.self::END_POINT, Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new ClientStub200());

        $this->get(self::END_POINT)
            ->success()
            ->shouldReturn(true);
    }

    public function it_sets_status_to_error_if_transaction_fails($client)
    {
        $client->get(self::BASE_URL.self::END_POINT, Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new ClientStub500());

        $this->get(self::END_POINT)
            ->fails()
            ->shouldReturn(true);
    }

    public function it_throws_an_exception_if_the_config_is_invalid($client)
    {
        $this->beConstructedWith($client, []);

        $this->shouldThrow('Exception')
            ->duringGet(self::END_POINT);
    }
}

class ClientStub200 extends GuzzleResponse
{
    public function getStatusCode(): int
    {
        return 200;
    }

    public function getHeader($header): array
    {
        return ['application/json; charset=utf-8'];
    }

    public function getBody(): StreamInterface
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, json_encode([]));
        fseek($stream, 0);

        return new Stream($stream);
    }
}

class ClientStub404 extends GuzzleResponse
{
    public function getStatusCode(): int
    {
        return 404;
    }

    public function getHeader($header): array
    {
        return ['application/text; charset=utf-8'];
    }

    public function json()
    {
        return json_encode([]);
    }
}

class ClientStub500 extends GuzzleResponse
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getHeader($header): array
    {
        return ['application/text; charset=utf-8'];
    }

    public function json()
    {
        return json_encode([]);
    }
}
