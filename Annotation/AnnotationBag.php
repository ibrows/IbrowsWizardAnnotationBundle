<?php

namespace Ibrows\Bundle\WizardAnnotationBundle\Annotation;

class AnnotationBag
{
    /**
     * @var Wizard
     */
    protected $annotation;

    /**
     * @var array
     */
    protected $annotations;

    /**
     * @param Wizard $annotation
     * @param array  $annotations
     */
    public function __construct(Wizard $annotation, array $annotations)
    {
        $this->annotation = $annotation;
        $this->annotations = $annotations;
    }

    /**
     * @return Wizard
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }
}
