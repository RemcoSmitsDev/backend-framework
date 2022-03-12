<?php 

declare(strict_types=1);

namespace Framework\Http\Middlewares;

use Framework\Http\Api;
use Closure;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations, 
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).  
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class ApiMiddleware
{
	/**
	 * @param  array   $route
	 * @param  Closure $next
	 * 
	 * @return mixed
	 */
	public function handle(array $route, Closure $next): mixed
	{
		if(!Api::fromAjax() || !Api::fromOwnServer()) return false;
	
		// return next action
		return $next();
	}
}