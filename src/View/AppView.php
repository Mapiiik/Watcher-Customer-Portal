<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;
use Override;

/**
 * @property \App\View\Helper\FormatHelper $Format
 */
class AppView extends View
{
    /**
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->addHelper('Format');
        // labels around the fields, and no inline markup the content security policy would refuse;
        // a field twice as wide as the others in a row of the form carries the class wide
        $this->loadHelper('Form', [
            'templates' => [
                'inputContainer' => '<div class="field {{type}}{{required}}{{wide}}">{{content}}</div>',
                'inputContainerError' =>
                    '<div class="field {{type}}{{required}}{{wide}} error">{{content}}{{error}}</div>',
                'error' => '<div class="error-message" id="{{id}}">{{content}}</div>',
            ],
        ]);
    }
}
