<?php

namespace Chartbeat;

class Exception extends \Exception
{
	const ERROR_HTTP_RETURN_CODE = 10;
	const ERROR_CURL = 11;
	const ERROR_CHARTBEAT_API = 12;
}
