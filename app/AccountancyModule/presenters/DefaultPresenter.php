<?php

declare(strict_types=1);

namespace App\AccountancyModule;

class DefaultPresenter extends BasePresenter
{
    /**
     * pouze přesměrovává na jiný presenter
     */
    protected function startup(): void
    {
        parent::startup();
        $this->redirect('Event:Default:');
    }
}
