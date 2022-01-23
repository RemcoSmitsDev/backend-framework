<?php

use Framework\Debug\Debug;

$trace = $error->getTrace();

array_shift($trace);

?>
<div class="mt-4">
	<h3 class="text-sm">Traces</h3>
	<debug-traces class="block min-w-full mt-2 px-4 divide-y divide-gray-700/30 border-dashed border-2 border-gray-700/30 overflow-hidden">
		<?php foreach (array_reverse($trace) as $traceItem) : ?>
			<debug-trace-item class="block min-w-full py-2 overflow-hidden" data-trace-file="<?= $traceItem['file'] ?? $error->getFile(); ?>" data-trace-line="<?= $traceItem['line'] ?? $error->getLine(); ?>">
				<span class="w-full block text-xs whitespace-nowrap overflow-x-auto">
					<a href="<?= Debug::getCodeEditorUrl($traceItem['file'] ?? $error->getFile(), $traceItem['line'] ?? $error->getLine()); ?>">
						<?= Debug::chooseName($traceItem['class'] ?? '--not-found--', basename($traceItem['file'] ?? $error->getFile())); ?>:<?= $traceItem['line'] ?? $error->getLine(); ?>
					</a>
				</span>
				<span class="font-semibold block text-xs">Function <span class="text-sm">`<?= $traceItem['function']; ?>`</span></span>
			</debug-trace-item>
		<?php endforeach; ?>
	</debug-traces>
</div>