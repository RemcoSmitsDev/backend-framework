<?php

namespace Framework\Content;

class Content
{
    /**
     * @var string $viewPath relative path to all views
     */
    private string $viewPath;

    /**
     * @var string $template Keeps track of all
     */
    public string $template = '404';

    /**
     * @var string $layout Keeps track of layout(head, global structure, footer)
     */
    public string $layout = '';

    /**
     * @var string $title Keeps track of page title
     */
    public string $title = '';

    /**
     * @var array Keeps track of all data to extract for all views
     */
    private array $data = [];


    public function __construct(string $viewPath = null)
    {
        $this->viewPath = $viewPath ?: SERVER_ROOT . '/../templates/';
    }

    /**
     * This function sets the main template that will be rendered within the layout file with:
     * content()->renderTemplate();
     * @param string $template
     * @param array $data
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
     * This function sets the layout type, this wil make an file path like: include{type}Content.php
     * @param string $type
     * 
     */

    public function layout(string $layout): self
    {
        // set layout
        $this->layout = $layout;

        // return self
        return $this;
    }

    /**
     * This function renders an view/file with data extracted
     * @param string $view
     * @param array $data
     * @return self
     */

    public function view(string $view, array $data = []): self
    {
        // merge old data into deeper view
        $this->data = array_merge($this->data, $data);

        // kijk of een template part bestaat
        if (file_exists($view = $this->viewPath . $view . '.php')) {
            // compact array
            extract($this->data);

            // require file
            require($view);
        }

        // return self
        return $this;
    }

    /**
     * This function sets the content title, this needs to be done when you set the template
     * @param string $title
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
     * Get content title
     * @return string
     */

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Listen if there is an template set
     * @return void
     */

    public function listen(): void
    {
        // check if content wrapper exists
        if (file_exists($path = $this->viewPath . "{$this->layout}.php")) {
            // require wrapper template
            require_once($path);
        }
    }

    /**
     * This renders the given template with extracted data
     * @return void
     */

    public function renderTemplate(): void
    {
        // view main template
        $this->view($this->template);
        // reset template
        $this->template = '';
        // reset content wrapper type
        $this->layout('');
    }
}
