<?php

use Framework\Debug\Debug;

?>
<div>
	<span class="font-semibold block text-sm">Message `<?= $error->getMessage(); ?>`</span>
	<span class="block text-sm">
		File:
		<a href="<?= Debug::getCodeEditorUrl($error->getFile(), $error->getLine()); ?>" title="<?= $error->getFile(); ?>">
			<?= basename($error->getFile()); ?>:<?= $error->getLine(); ?>
		</a>
	</span>
	<?php if (!empty($error->getTrace())) { ?>
		<?php content()->view('error/trace', compact('error')); ?>
	<?php } ?>
</div>