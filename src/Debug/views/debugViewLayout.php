<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Debug page</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			theme: {
				extend: {
					colors: {
						clifford: '#da373d',
					}
				}
			}
		}
	</script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.4.0/styles/default.min.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.7.2/highlight.min.js"></script>
	<style>
		code {
			background-color: transparent !important;
		}

		*:not(body)::-webkit-scrollbar {
			display: none;
		}

		*:not(body) {
			-ms-overflow-style: none;
			/* IE and Edge */
			scrollbar-width: none;
			/* Firefox */
		}

		pre code span {
			color: rgb(229, 231, 235);
		}

		pre code.language-sql {
			color: rgb(229, 231, 235);
		}

		.hljs-comment,
		.hljs-quote {
			--tw-text-opacity: 1;
			color: rgb(156 163 175 / var(--tw-text-opacity));
		}

		.hljs-comment.hljs-doctag {
			--tw-text-opacity: 1;
			color: rgb(209 213 219 / var(--tw-text-opacity));
		}

		.hljs-doctag,
		.hljs-keyword,
		.hljs-formula,
		.hljs-name {
			--tw-text-opacity: 1;
			color: rgb(248 113 113 / var(--tw-text-opacity));
		}

		.hljs-attr,
		.hljs-section,
		.hljs-selector-tag,
		.hljs-deletion,
		.hljs-function.hljs-keyword,
		.hljs-literal {
			--tw-text-opacity: 1;
			color: rgb(139 92 246 / var(--tw-text-opacity));
		}

		.hljs-string,
		.hljs-regexp,
		.hljs-addition,
		.hljs-attribute,
		.hljs-meta-string {
			--tw-text-opacity: 1;
			color: rgb(96 165 250 / var(--tw-text-opacity));
		}

		.hljs-built_in,
		.hljs-class .hljs-title,
		.hljs-template-tag,
		.hljs-template-variable {
			--tw-text-opacity: 1;
			color: rgb(249 115 22 / var(--tw-text-opacity));
		}

		.hljs-type,
		.hljs-selector-class,
		.hljs-selector-attr,
		.hljs-selector-pseudo,
		.hljs-number,
		.hljs-string.hljs-subst {
			--tw-text-opacity: 1;
			color: rgb(52 211 153 / var(--tw-text-opacity));
		}

		.hljs-symbol,
		.hljs-bullet,
		.hljs-link,
		.hljs-meta,
		.hljs-selector-id,
		.hljs-title,
		.hljs-variable,
		.hljs-operator {
			--tw-text-opacity: 1;
			color: rgb(129 140 248 / var(--tw-text-opacity));
		}

		.hljs-title,
		.hljs-strong {
			font-weight: 700;
		}

		.hljs-emphasis {
			font-style: italic;
		}

		.hljs-link {
			-webkit-text-decoration-line: underline;
			text-decoration-line: underline;
		}
	</style>
</head>

<body class="my-10 space-y-10 bg-gray-900 text-white">
	<?php content()->renderTemplate(); ?>
</body>

</html>