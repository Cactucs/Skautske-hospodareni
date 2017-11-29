<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/forms
 * @author     Jan Skrasek
 */

namespace App;

use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Forms\Controls;
use Nette\Forms\Form;
use Nette;
use Nette\Utils\Html;


/**
 * FormRenderer for Bootstrap 3 framework.
 * @author   Jan Skrasek
 * @author   David Grudl
 */
class FormRenderer extends DefaultFormRenderer
{
    /** @var Controls\Button|NULL */
    public $primaryButton = NULL;

    /** @var bool */
    private $controlsInit = FALSE;

    private $inline = FALSE;

    public function __construct($inline = FALSE)
    {
        if ($inline) {
            $this->wrappers['controls']['container'] = NULL;
            $this->wrappers['pair']['container'] = NULL;
            $this->wrappers['pair']['.error'] = NULL;
            $this->wrappers['control']['container'] = NULL;
            $this->wrappers['label']['container'] = NULL;
            $this->wrappers['control']['description'] = NULL;
            $this->wrappers['control']['errorcontainer'] = NULL;

            $this->inline = $inline;
        } else {
            $this->wrappers['controls']['container'] = NULL;
            $this->wrappers['pair']['container'] = 'div class=form-group';
            $this->wrappers['pair']['.error'] = 'has-error';
            $this->wrappers['control']['container'] = 'div class=col-sm-9';
            $this->wrappers['label']['container'] = 'div class="col-sm-3 control-label"';
            $this->wrappers['control']['description'] = 'span class=help-block';
            $this->wrappers['control']['errorcontainer'] = 'span class=help-block';
        }
    }


    public function renderBegin() : string
    {
        $this->controlsInit();
        return parent::renderBegin();
    }


    public function renderEnd() : string
    {
        $this->controlsInit();
        return parent::renderEnd();
    }


    public function renderBody() : string
    {
        $this->controlsInit();
        return parent::renderBody();
    }


    public function renderControls($parent) : string
    {
        $this->controlsInit();
        return parent::renderControls($parent);
    }


    public function renderPair(Nette\Forms\IControl $control) : string
    {
        $this->controlsInit();
        return parent::renderPair($control);
    }


    public function renderPairMulti(array $controls) : string
    {
        $this->controlsInit();
        return parent::renderPairMulti($controls);
    }


    public function renderLabel(Nette\Forms\IControl $control) : Html
    {
        $this->controlsInit();
        return parent::renderLabel($control);
    }


    public function renderControl(Nette\Forms\IControl $control) : Html
    {
        $this->controlsInit();
        return parent::renderControl($control);
    }

    private function controlsInit(): void
    {
        if ($this->controlsInit) {
            return;
        }

        $this->controlsInit = TRUE;
        $this->form->getElementPrototype()->addClass($this->inline ? 'form-inline' : 'form-horizontal');
        foreach ($this->form->getControls() as $control) {
            if ($control instanceof Controls\Button) {
                $markAsPrimary = $control === $this->primaryButton || (!isset($this->primaryButton) && empty($usedPrimary) && $control->parent instanceof Form);
                if ($markAsPrimary) {
                    $class = 'btn btn-primary';
                    $usedPrimary = TRUE;
                } else {
                    $class = 'btn btn-default';
                }
                $control->getControlPrototype()->addClass($class);

            } elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control');

            } elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
                if ($control->getSeparatorPrototype()->getName() !== '') {
                    $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
                } else {
                    $control->getItemLabelPrototype()->addClass($control->getControlPrototype()->type . '-inline');
                }
            }
        }
    }

}
