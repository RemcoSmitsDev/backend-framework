<?php

namespace Framework\Http\Request;

// post: update
// get: getting
// put: insert
// delete: deleting

trait ValidateCsrfToken
{
    /**
     * @return bool
     */
    public function validateCsrf(): bool
    {
        // validate token
        $passed = hash_equals(\request()->post('_token') ?: '', $_SESSION['_csrf_token'] ?? randomString(40));

        // unset token
        unset($_SESSION['_csrf_token']);

        // return token validation passed status
        return $passed;
    }
}
