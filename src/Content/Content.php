<?php

declare(strict_types=1);

namespace Framework\Content;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Content
{
    /**
     * @var string relative path to all views
     */
    private string $viewPath;

    /**
     * @var string Keeps track of all
     */
    private string $template = '404';

    /**
     * @var string|bool Keeps track of layout(head, global structure, footer)
     */
    private string|bool $layout = '';

    /**
     * @var string|bool Keeps track of an default template
     */
    private string|bool $defaultLayout = false;

    /**
     * @var string Keeps track of page title
     */
    private string $title = '';

    /**
     * @var array Keeps track of all data to extract for all views
     */
    private array $data = [];

    /**
     * @param string|null $viewPath
     * @param string|bool $defaultLayout
     */
    public function __construct(?string $viewPath = null, string|bool $defaultLayout = false)
    {
        $this->viewPath = rtrim(
            $viewPath ?: SERVER_ROOT.'/../templates',
            '/'
        ).'/';
        $this->defaultLayout = $defaultLayout;
    }

    /**
     * This function sets the main template that will be rendered within the layout file with:
     * content()->renderTemplate();.
     *
     * @param string $template
     * @param array  $data
     *
     * @return self
     */
    public function template(string $template, array $data = []): self
    {
        // merge old data into deeper view
        $this->data = array_merge($this->data, $data);
        // set template as content wrapper
        $this->template = $template;

        // return self
        return $this;
    }

    /**
     * This function sets the layout type, this wil make an file path like: include{type}Content.php.
     *
     * @param string|bool $layout
     *
     * @return Content
     */
    public function layout(string|bool $layout): self
    {
        // set layout
        $this->layout = $layout;

        // return self
        return $this;
    }

    /**
     * This function renders an view/file with data extracted.
     *
     * @param string $view
     * @param array  $data
     *
     * @return self
     */
    public function view(string $view, array $data = []): self
    {
        // merge old data into deeper view
        $this->data = array_merge($this->data, $data);

        // kijk of een template part bestaat
        if (file_exists($view = $this->viewPath.$view.'.php')) {
            // compact array
            extract($this->data);

            // require file
            require $view;
        }

        // return self
        return $this;
    }

    /**
     * This function sets the content title, this needs to be done when you set the template.
     *
     * @param string $title
     *
     * @return self
     */
    public function title(string $title): self
    {
        // set title
        $this->title = $title;

        // return self
        return $this;
    }

    /**
     * Get content title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Listen if there is an template set.
     *
     * @return void
     */
    public function listen(): void
    {
        if ($this->layout !== false && $this->layout === '' && $this->defaultLayout) {
            $this->layout($this->defaultLayout);
        }
        // check if content wrapper exists
        if (file_exists($path = $this->viewPath."{$this->layout}.php")) {
            // require wrapper template
            require_once $path;
        }
    }

    /**
     * This renders the given template with extracted data.
     *
     * @return void
     */
    public function renderTemplate(): void
    {
        // view main template
        $this->view($this->template);
        // reset template
        $this->template = '';
    }
}
