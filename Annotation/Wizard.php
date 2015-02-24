<?php

namespace Ibrows\Bundle\WizardAnnotationBundle\Annotation;

/**
 * @Annotation
 */
class Wizard
{
    const REDIRECT_STEP_BACK = 'stepBack';

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $number;

    /**
     * @var string
     */
    public $validationMethod;

    /**
     * @var bool
     */
    public $visible = true;

    /**
     * @var bool
     */
    protected $isValid;

    /**
     * @var AnnotationBag
     */
    protected $annotationBag;

    /**
     * @var bool
     */
    protected $isCurrentMethod = false;

    /**
     * @var bool
     */
    protected $isFirst = false;

    /**
     * @var bool
     */
    protected $isLast = false;

    /**
     * @param bool $flag
     */
    public function setIsCurrentMethod($flag = true)
    {
        $this->isCurrentMethod = (bool)$flag;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @return bool
     */
    public function isCurrentMethod()
    {
        return $this->isCurrentMethod;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getValidationMethod()
    {
        return $this->validationMethod;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @param bool $flag
     */
    public function setIsValid($flag = true)
    {
        $this->isValid = (bool)$flag;
    }

    /**
     * @return bool
     */
    public function isFirst()
    {
        return $this->isFirst;
    }

    /**
     * @param bool $flag
     */
    public function setIsFirst($flag = true)
    {
        $this->isFirst = (bool)$flag;
    }

    /**
     * @return bool
     */
    public function isLast()
    {
        return $this->isLast;
    }

    /**
     * @param bool $flag
     */
    public function setIsLast($flag = true)
    {
        $this->isLast = (bool)$flag;
    }

    /**
     * @param AnnotationBag $annotationBag
     */
    public function setAnnotationBag(AnnotationBag $annotationBag)
    {
        $this->annotationBag = $annotationBag;
    }

    /**
     * @return AnnotationBag
     */
    public function getAnnotationBag()
    {
        return $this->annotationBag;
    }
}