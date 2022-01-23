<section class="m:p-10 p-5 w-full flex flex-start justify-between container mx-auto bg-gray-800">
	<div class="w-full m:space-y-4 space-y-6">
		<div class="flex items-center justify-between w-full">
			<span class="px-2 py-1 border-2 border-dashed border-gray-700/20 text-sm">
				<?= htmlspecialchars($errors[0]['type']); ?>
			</span>
			<div class="flex items-center justify-center space-x-4">
				<span class="px-2 py-1 border-2 border-dashed border-gray-700/20 text-sm whitespace-nowrap">
					PHP <?= PHP_VERSION; ?>
				</span>
				<span class="px-2 py-1 border-2 border-dashed border-gray-700/20 text-sm whitespace-nowrap">
					Framework version `<?= Composer\InstalledVersions::getVersion('remcosmits/backend-framework') ?? 'latest-version'; ?>`
				</span>
			</div>
		</div>
		<span class="block font-semibold text-lg">
			<?= htmlspecialchars($errors[0]['data']->getMessage()); ?>
		</span>
	</div>
</section>
<section class="flex flex-start md:flex-row flex-col-reverse h-full container mx-auto bg-gray-800">
	<debug-error-list class="block h-full w-full max-w-md divide-y divide-gray-700/50">
		<?php

		use Framework\Debug\Debug;

		foreach ($errors as $error) : ?>
			<?php if ($error['type'] === 'Error') : ?>
				<div class="p-5 bg-gray-800 flex flex-start space-x-4">
					<div class="w-20">
						<span class="h-min inline-block px-2 py-1 border-dashed border-2 border-gray-700/30 rounded text-xs text-gray-600">
							Error
						</span>
					</div>
					<?php content()->view('error/item', ['error' => $error['data']]); ?>
				</div>
			<?php elseif ($error['type'] === 'Exception') : ?>
				<div class="p-5 bg-gray-800 flex flex-start space-x-4">
					<div class="w-20">
						<span class="h-min inline-block px-2 py-1 border-dashed border-2 border-gray-700/30 rounded text-xs text-gray-600">
							Exception
						</span>
					</div>
					<?php content()->view('exception/item', ['error' => $error['data']]); ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</debug-error-list>
	<div class="relative flex items-stretch flex-grow overflow-y-hidden overflow-x-auto">
		<div class="sticky left-0 block bg-gray-800 border-r border-gray-700/70">
			<?php foreach ($codepreview['lineNumbers'] as $lineNumber) : ?>
				<p class="px-2 font-mono leading-loose select-none text-xs <?= $lineNumber === $codepreview['line'] ? 'bg-red-500/60' : ''; ?>">
					<span class="text-gray-500">
						<?= $lineNumber; ?>
					</span>
				</p>
			<?php endforeach; ?>
		</div>
		<debug-code-preview class="block flex-grow" data-file="<?= htmlspecialchars($codepreview['file']); ?>" data-line="<?= htmlspecialchars($codepreview['line']); ?>">
			<pre><code class="language-php" style="white-space: pre; padding: 0px!important;"><?= $codepreview['snippet']; ?></code></pre>
		</debug-code-preview>
	</div>
</section>

<section class="pt-5 pb-5 container mx-auto">
	<div class="flex items-stretch">
		<nav class="hidden sm:block min-w-[8rem] flex-none mr-10 lg:mr-20">
			<div class="sticky top-[7.5rem]">
				<ul class="grid grid-cols-1 gap-10">
					<li>
						<a href="#request" class="uppercase tracking-wider text-gray-500 text-xs font-bold">Request</a>
					</li>
					<?php if (!empty(app('route')?->getRoutes() ?? [])) : ?>
						<li>
							<a href="#routes" class="uppercase tracking-wider text-gray-500 text-xs font-bold">Routes</a>
						</li>
					<?php endif; ?>
					<li>
						<a href="#cookies" class="uppercase tracking-wider text-gray-500 text-xs font-bold">Cookies</a>
					</li>
					<li>
						<a href="#session" class="uppercase tracking-wider text-gray-500 text-xs font-bold">Session</a>
					</li>
				</ul>
			</div>
		</nav>
		<div class="overflow-hidden grid grid-cols-1 gap-px shadow-lg flex-grow bg-gray-800">
			<section class="py-10 px-6 sm:px-10 min-w-0">
				<a id="request"></a>
				<h2 class="font-bold text-xs uppercase tracking-wider">Request</h2>
				<div class="mt-3 grid grid-cols-1 gap-6">
					<div>
						<div class="text-lg font-semibold flex items-center gap-2">
							<span><?= request()->host() . request()->uri(); ?></span>
							<div class="<?= http_response_code() >= 400 ? 'text-red-600 border-red-500/50' : 'text-green-600 border-green-500/50' ?> px-1.5 py-0.5 rounded-sm bg-opacity-20 border text-xs font-medium uppercase tracking-wider">
								<?= request()->method(); ?>
							</div>
							<div class="<?= http_response_code() >= 400 ? 'text-red-600 border-red-500/50' : 'text-green-600 border-green-500/50' ?> px-1.5 py-0.5 rounded-sm bg-opacity-20 border text-xs font-medium uppercase tracking-wider">
								<?= http_response_code(); ?>
							</div>
						</div>
					</div>
					<dl class="grid grid-cols-1 gap-2">
						<div class="flex items-baseline gap-10">
							<dt class="flex-none truncate w-32 text-sm">Query string</dt>
							<dd class="px-2 flex-grow min-w-0 bg-gray-900/20">
								<div class="overflow-x-auto py-2 text-xs">
									<?= htmlspecialchars(request()->query()); ?>
								</div>
							</dd>
						</div>
					</dl>
					<dl class="grid grid-cols-1 gap-2">
						<div class="flex items-baseline gap-10">
							<dt class="flex-none truncate w-32 text-sm">Body</dt>
							<dd class="px-2 flex-grow min-w-0 bg-gray-900/20">
								<div class="overflow-x-auto py-2 text-xs">
									<?php dd(empty((array) request()->post()) ? '[]' : (array) request()->post()); ?>
								</div>
							</dd>
						</div>
					</dl>
					<?php if (!empty(app('route')?->getRoutes() ?? [])) : ?>
						<a id="routes"></a>
						<h2 class="font-bold text-xs uppercase tracking-wider">
							Routes
						</h2>
						<dl class="grid grid-cols-1 gap-2">
							<div class="flex items-baseline gap-10">
								<dd class="px-2 flex-grow min-w-0 bg-gray-900/20">
									<?php foreach (app('route')?->getRoutes() ?? [] as $route) : ?>
										<div class="overflow-x-auto py-2 text-xs">
											<?= htmlspecialchars($route['uri']); ?>
										</div>
									<?php endforeach; ?>
								</dd>
							</div>
						</dl>
					<?php endif; ?>

					<a id="cookies"></a>
					<h2 class="font-bold text-xs uppercase tracking-wider">
						Cookies
					</h2>
					<dl class="grid grid-cols-1 gap-2 ">
						<?php foreach ($_COOKIE as $key => $value) : ?>
							<div class="flex items-baseline gap-10">
								<dt class="flex-none truncate w-32 text-sm"><?= htmlspecialchars($key); ?></dt>
								<dd class="px-2 flex-grow min-w-0 bg-gray-900/20">
									<div class="overflow-x-auto py-2 text-xs">
										<?= htmlspecialchars($value); ?>
									</div>
								</dd>
							</div>
						<?php endforeach; ?>
					</dl>

					<a id="session"></a>
					<h2 class="font-bold text-xs uppercase tracking-wider">
						Session
					</h2>
					<dl class="grid grid-cols-1 gap-2 ">
						<?php foreach ($_SESSION as $key => $value) : ?>
							<div class="flex items-baseline gap-10">
								<dt class="flex-none truncate w-32 text-sm"><?= htmlspecialchars($key); ?></dt>
								<dd class="px-2 flex-grow min-w-0 bg-gray-900/20">
									<div class="overflow-x-auto py-2 text-xs">
										<?php dd($value); ?>
									</div>
								</dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</div>
			</section>
		</div>
	</div>
</section>

<?php if (count($queries) > 0) : ?>
	<section class="px-10 pt-10 pb-8 space-y-4 relative container mx-auto bg-gray-800">
		<div class="z-10 relative -mt-14 flex items-center justify-center">
			<span class="px-2 py-1 bg-gray-900/70 whitespace-nowrap rounded-lg shadow-lg text-sm">
				Queries <?= count($queries); ?>
			</span>
		</div>

		<?php foreach ($queries as $query) : ?>
			<div class="bg-gray-900/20 rounded-lg overflow-x-hidden">
				<span class="divide-x divide-gray-600/10 inline-flex items-center relative w-full">
					<span class="px-2 py-1 space-x-1 inline-flex items-center bg-gray-900 rounded-tl-lg text-sm">
						<svg class="h-3 w-3 text-gray-500" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="stopwatch" class="svg-inline--fa fa-stopwatch fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
							<path fill="currentColor" d="M432 304c0 114.9-93.1 208-208 208S16 418.9 16 304c0-104 76.3-190.2 176-205.5V64h-28c-6.6 0-12-5.4-12-12V12c0-6.6 5.4-12 12-12h120c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12h-28v34.5c37.5 5.8 71.7 21.6 99.7 44.6l27.5-27.5c4.7-4.7 12.3-4.7 17 0l28.3 28.3c4.7 4.7 4.7 12.3 0 17l-29.4 29.4-.6.6C419.7 223.3 432 262.2 432 304zm-176 36V188.5c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12V340c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12z">
							</path>
						</svg>
						<span class="text-gray-500 whitespace-nowrap">
							<?= $query['executionTime']; ?> ms
						</span>
					</span>
					<span class="px-2 py-1 inline-flex items-center bg-gray-900 text-sm text-gray-500 whitespace-nowrap overflow-x-auto">
						<?= $query['effectedRows']; ?> Row(s)
					</span>
					<?php if ($query['failed']) : ?>
						<span class="px-2 py-1 inline-flex items-center bg-gray-900 text-sm text-red-500 whitespace-nowrap overflow-x-auto">
							<?= htmlspecialchars($query['error']); ?>
						</span>
					<?php endif; ?>
				</span>
				<pre><code class="language-sql text-xs"><?= htmlspecialchars($query['query']); ?></code></pre>
			</div>
		<?php endforeach; ?>
	</section>
<?php endif; ?>

<?php if (count($requests) > 0) : ?>
	<section class="px-10 pt-10 pb-8 space-y-4 relative container mx-auto bg-gray-800">
		<div class="z-10 relative -mt-14 flex items-center justify-center">
			<span class="px-2 py-1 bg-gray-900/70 whitespace-nowrap rounded-lg shadow-lg text-sm">
				Requests <?= count($requests); ?>
			</span>
		</div>

		<?php foreach ($requests as $request) : ?>
			<div class="bg-gray-900/20 rounded-lg overflow-x-hidden">
				<span class="divide-x divide-gray-600/10 inline-flex items-center relative w-full">
					<span class="px-2 py-1 space-x-1 inline-flex items-center bg-gray-900 text-gray-500 rounded-tl-lg text-sm">
						<?= htmlspecialchars($request->url); ?>
					</span>
					<span class="px-2 py-1 inline-flex items-center bg-gray-900 text-gray-500 text-sm <?= $request->httpStatusCode >= 400 ? 'text-red-500/40' : 'text-green-500/40' ?> whitespace-nowrap overflow-x-auto">
						<?= $request->httpStatusCode; ?>
					</span>
					<span class="px-2 py-1 space-x-1 inline-flex items-center bg-gray-900 text-gray-500 text-sm">
						Retries <?= $request->retries; ?>
					</span>
					<?php if ($request->error) : ?>
						<span class="px-2 py-1 inline-flex items-center bg-gray-900 text-sm text-red-500 whitespace-nowrap overflow-x-auto">
							<?= htmlspecialchars($request->error); ?>
						</span>
					<?php endif; ?>
				</span>
				<pre><code class="language-sql text-xs"><?= Debug::formatCurlRequest($request->url, $request->requestHeaders); ?></code></pre>
			</div>
		<?php endforeach; ?>
	</section>
<?php endif; ?>

<script>
	document.addEventListener('DOMContentLoaded', (event) => {
		document.querySelectorAll('pre code').forEach((el) => {
			hljs.highlightElement(el);
		});

		document.querySelectorAll('debug-trace-item[data-trace-file="<?= $errors[0]['data']->getFile(); ?>"]').forEach((element) => {
			element.addEventListener('mouseover', (event) => {
				$(`debug-code-preview code-preview-line[data-line="${event.currentTarget.getAttribute('data-trace-line')}"]`).addClass('bg-red-500\/10');
			});
			element.addEventListener('mouseleave', (event) => {
				$(`debug-code-preview code-preview-line[data-line="${event.currentTarget.getAttribute('data-trace-line')}"]`).removeClass('bg-red-500\/10');
			});
		});
	});
</script>