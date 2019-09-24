<?php

namespace MarcinOrlowski\ResponseBuilder\Tests;

/**
 * Laravel API Response Builder
 *
 * @package   MarcinOrlowski\ResponseBuilder
 *
 * @author    Marcin Orlowski <mail (#) marcinorlowski (.) com>
 * @copyright 2016-2019 Marcin Orlowski
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      https://github.com/MarcinOrlowski/laravel-api-response-builder
 */

use MarcinOrlowski\ResponseBuilder\BaseApiCodes;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ErrorTest extends TestCase
{
	/**
	 * @var HttpResponse
	 */
	protected $response;

	/**
	 * Check success()
	 */
	public function testError(): void
	{
		// GIVEN random error code
		$api_code = $this->random_api_code;

		// WHEN we report error
		$this->response = ResponseBuilder::error($api_code);

		// THEN returned message contains given error code and mapped message
		$j = $this->getResponseErrorObject($api_code);
		$this->assertEquals($this->random_api_code_message, $j->message);

		// AND no data
		$this->assertNull($j->data);
	}

	/**
	 * Tests error() with various http codes and random payload
	 */
	public function testError_WithDataHttpCode(): void
	{
		$http_codes = [HttpResponse::HTTP_CONFLICT,
		               HttpResponse::HTTP_BAD_REQUEST,
		               HttpResponse::HTTP_FAILED_DEPENDENCY,
		               ResponseBuilder::DEFAULT_HTTP_CODE_ERROR,
		];

		foreach ($http_codes as $http_code) {
			// GIVEN data
			$data = [$this->getRandomString('key') => $this->getRandomString('val')];

			// AND error code
			$api_code = $this->random_api_code;

			// WHEN we report error
			$this->response = ResponseBuilder::error($api_code, null, $data, $http_code);

			// THEN returned message contains given error code and mapped message
			$j = $this->getResponseErrorObject($api_code, $http_code);
			$this->assertEquals($this->random_api_code_message, $j->message);

			// AND passed data
			$this->assertEquals((object)$data, $j->data);
		}
	}

	/**
	 * Tests errorWithData()
	 */
	public function testErrorWithData(): void
	{
		$data = [$this->getRandomString('key') => $this->getRandomString('val')];
		$api_code = $this->random_api_code;
		$this->response = ResponseBuilder::errorWithData($api_code, $data);

		$j = $this->getResponseErrorObject($api_code);
		$this->assertEquals((object)$data, $j->data);
	}

	/**
	 * Tests errorWithDataAndHttpCode()
	 */
	public function testErrorWithDataAndHttpCode(): void
	{
		$http_codes = [
			HttpResponse::HTTP_CONFLICT,
			HttpResponse::HTTP_BAD_REQUEST,
			HttpResponse::HTTP_FAILED_DEPENDENCY,
			ResponseBuilder::DEFAULT_HTTP_CODE_ERROR,
		];

		foreach ($http_codes as $http_code) {
			$data = [$this->getRandomString('key') => $this->getRandomString('val')];
			$this->response = ResponseBuilder::errorWithDataAndHttpCode($this->random_api_code, $data, $http_code);

			$j = $this->getResponseErrorObject($this->random_api_code, $http_code);
			$this->assertEquals((object)$data, $j->data);
		}
	}

	/**
	 * Tests errorWithHttpCode()
	 */
	public function testErrorWithHttpCode(): void
	{
		$http_codes = [
			HttpResponse::HTTP_CONFLICT,
			HttpResponse::HTTP_BAD_REQUEST,
			HttpResponse::HTTP_FAILED_DEPENDENCY,
			ResponseBuilder::DEFAULT_HTTP_CODE_ERROR,
		];

		foreach ($http_codes as $http_code) {
			$this->response = ResponseBuilder::errorWithHttpCode($this->random_api_code, $http_code);

			$j = $this->getResponseErrorObject($this->random_api_code, $http_code);
			$this->assertNull($j->data);
		}
	}

	/**
	 * Tests errorWithMessageAndData()
	 */
	public function testErrorWithMessageAndData(): void
	{
		$data = [$this->getRandomString('key') => $this->getRandomString('val')];
		$api_code = $this->random_api_code;
		$error_message = $this->getRandomString('msg');
		$this->response = ResponseBuilder::errorWithMessageAndData($api_code, $error_message, $data);

		$j = $this->getResponseErrorObject($api_code, ResponseBuilder::DEFAULT_HTTP_CODE_ERROR, $error_message);
		$this->assertEquals($error_message, $j->message);
		$this->assertEquals((object)$data, $j->data);
	}

	/**
	 * Tests errorWithMessageAndDataAndDebug()
	 */
	public function testErrorWithMessageAndDataAndDebug(): void
	{
		$trace_key = \Config::get(ResponseBuilder::CONF_KEY_DEBUG_EX_TRACE_KEY, ResponseBuilder::KEY_TRACE);
		$trace_data = [
			$trace_key => (object)[
				$this->getRandomString('trace_key') => $this->getRandomString('trace_val'),
			],
		];

		$data = [$this->getRandomString('key') => $this->getRandomString('val')];
		$api_code = $this->random_api_code;
		$error_message = $this->getRandomString('msg');

		\Config::set(ResponseBuilder::CONF_KEY_DEBUG_EX_TRACE_ENABLED, true);
		$this->response = ResponseBuilder::errorWithMessageAndDataAndDebug($api_code, $error_message,
			$data, null, null, $trace_data);

		$j = $this->getResponseErrorObject($api_code, ResponseBuilder::DEFAULT_HTTP_CODE_ERROR, $error_message);
		$this->assertEquals($error_message, $j->message);
		$this->assertEquals((object)$data, $j->data);

		$debug_key = \Config::get(ResponseBuilder::CONF_KEY_DEBUG_DEBUG_KEY, ResponseBuilder::KEY_DEBUG);
		$this->assertEquals((object)$trace_data, $j->$debug_key);
	}

	/**
	 * Tests errorWithMessage()
	 */
	public function testErrorWithMessage(): void
	{
		$api_code = $this->random_api_code;
		$error_message = $this->getRandomString('msg');
		$this->response = ResponseBuilder::errorWithMessage($api_code, $error_message);

		$j = $this->getResponseErrorObject($api_code, ResponseBuilder::DEFAULT_HTTP_CODE_ERROR, $error_message);
		$this->assertEquals($error_message, $j->message);
		$this->assertNull($j->data);
	}

	/**
	 * Checks if using errorXXX() with OK() code triggers resistance.
	 */
	public function testErrorWithOkCode(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		ResponseBuilder::error(BaseApiCodes::OK());
	}

	/**
	 * Tests error() handling api code with no message mapping
	 */
	public function testError_MissingMessageMapping(): void
	{
		/** @var \MarcinOrlowski\ResponseBuilder\BaseApiCodes $api_codes_class_name */
		$api_codes_class_name = $this->getApiCodesClassName();

		// FIXME we **assume** this code is not set nor mapped. But assumptions suck...
		$api_code = $this->max_allowed_code - 1;
		$this->response = ResponseBuilder::error($api_code);

		$key = $api_codes_class_name::getCodeMessageKey($api_codes_class_name::NO_ERROR_MESSAGE());
		$lang_args = ['api_code' => $api_code];
		$msg = \Lang::get($key, $lang_args);

		$j = $this->getResponseErrorObject($api_code, ResponseBuilder::DEFAULT_HTTP_CODE_ERROR, $msg);
		$this->assertNull($j->data);
	}

	/**
	 * Tests buildErrorResponse() fed with not allowed OK api code
	 */
	public function testBuildErrorResponse_ApiCodeOK(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		/** @var \MarcinOrlowski\ResponseBuilder\BaseApiCodes $api_codes_class_name */
		$api_codes_class_name = $this->getApiCodesClassName();

		$data = null;
		$http_code = 404;
		$api_code = $api_codes_class_name::OK();
		$lang_args = null;

		$this->callBuildErrorResponse($data, $api_code, $http_code, $lang_args);
	}

	/**
	 * Tests buildErrorResponse() fed with @null as http_code
	 */
	public function testBuildErrorResponse_NullHttpCode(): void
	{
		/** @var \MarcinOrlowski\ResponseBuilder\BaseApiCodes $api_codes_class_name */
		$api_codes_class_name = $this->getApiCodesClassName();

		$data = null;
		$http_code = null;
		$api_code = $api_codes_class_name::NO_ERROR_MESSAGE();
		$lang_args = null;

		$this->response = $this->callBuildErrorResponse($data, $api_code, $http_code, $lang_args);

		$http_code = ResponseBuilder::DEFAULT_HTTP_CODE_ERROR;
		$this->assertEquals($http_code, $this->response->getStatusCode());
	}

	/**
	 * Tests buildErrorResponse() fed with http code out of allowed bounds
	 */
	public function testBuildErrorResponse_TooLowHttpCode(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		/** @var \MarcinOrlowski\ResponseBuilder\BaseApiCodes $api_codes_class_name */
		$api_codes_class_name = $this->getApiCodesClassName();

		$data = null;
		$http_code = 0;
		$api_code = $api_codes_class_name::NO_ERROR_MESSAGE();
		$lang_args = null;

		$this->callBuildErrorResponse($data, $api_code, $http_code, $lang_args);
	}

	/**
	 * Calls protected method buildErrorResponse()
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param mixed|null   $data      payload to be returned as 'data' node, @null if none
	 * @param integer|null $api_code  API code to be returned with the response
	 * @param integer|null $http_code HTTP error code to be returned with this Cannot be @null
	 * @param array|null   $lang_args arguments array passed to Lang::get() for messages with placeholders
	 *
	 * @return mixed
	 */
	protected function callBuildErrorResponse($data, $api_code, $http_code, $lang_args)
	{
		$obj = new ResponseBuilder();

		/** @noinspection PhpUnhandledExceptionInspection */
		return $this->callProtectedMethod($obj, 'buildErrorResponse', [$data,
		                                                               $api_code,
		                                                               $http_code,
		                                                               $lang_args]);
	}

}
