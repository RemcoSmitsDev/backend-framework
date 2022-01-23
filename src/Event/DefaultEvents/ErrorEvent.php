<?php

namespace Framework\Event\DefaultEvents;

use Framework\Event\Interfaces\BaseEventInterface;
use Framework\Event\BaseEvent;
use Framework\Debug\Debug;
use ErrorException;
use Error;

class ErrorEvent extends BaseEvent
{
	public function handle(BaseEventInterface $event, mixed $data)
	{
		if (!IS_DEVELOPMENT_MODE) {
			return;
		}

		// check if is an erroy
		if ($data['data'] instanceof ErrorException || $data['data'] instanceof Error) {
			$data['type'] = 'Error';
		}

		// add to debug state
		Debug::add('error', $data);

		// when ray is enabled send error
		if (app()->rayIsEnabled()) {
			ray($data['data'])->title('Error')->color('red');
		}
	}
}
