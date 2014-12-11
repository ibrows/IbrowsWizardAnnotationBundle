<?php

namespace Ibrows\Bundle\WizardAnnotationBundle\Twig;

use Ibrows\Bundle\WizardAnnotationBundle\Annotation\AnnotationHandler as Wizard;

class TwigExtension extends \Twig_Extension
{
    /**
     * @var Wizard
     */
    protected $wizard;

    /**
     * @param Wizard $wizard
     * @return TwigExtension
     */
    public function setWizard(Wizard $wizard)
    {
        $this->wizard = $wizard;
        return $this;
    }

    /**
     * @return array
     */
    public function getFunctions(){
        return array(
            'getWizard' => new \Twig_Function_Method($this, 'getWizard')
        );
    }
    
    /**
     * @return Wizard
     */
    public function getWizard()
    {
        return $this->wizard;
    }
    
    /**
     * @return string
     */
    public function getName() {
        return 'ibrows_wizard_extension';
    }
}
