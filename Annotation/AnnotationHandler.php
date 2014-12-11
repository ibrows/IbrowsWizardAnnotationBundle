<?php

namespace Ibrows\Bundle\WizardAnnotationBundle\Annotation;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class AnnotationHandler
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var AnnotationBag[]
     */
    protected $annotationBags = array();

    /**
     * @var Wizard[]
     */
    protected $annotations = array();

    /**
     * @var Wizard
     */
    protected $currentActionAnnotation;

    /**
     * @var FilterControllerEvent|null
     */
    protected $lastFilterControllerEvent = null;

    /**
     * @var array|null
     */
    protected $lastAnnotationBags = null;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return bool
     */
    public function recompute()
    {
        if (null === $this->lastFilterControllerEvent || null === $this->lastAnnotationBags) {
            return false;
        }

        $this->handle($this->lastFilterControllerEvent, $this->lastAnnotationBags, true);
        return true;
    }

    /**
     * @param FilterControllerEvent $event
     * @param array $annotationBags
     * @param bool $recompute
     * @throws \InvalidArgumentException
     */
    public function handle(FilterControllerEvent $event, array $annotationBags, $recompute = false)
    {
        if (false === $recompute) {
            $this->lastFilterControllerEvent = $event;
            $this->lastAnnotationBags = $annotationBags;
        }

        $this->setAnnotations($annotationBags);

        $controllerArray = $event->getController();
        $controller = $controllerArray[0];

        $hasFoundCurrentAction = false;
        $hasInvalidActionFound = false;

        foreach ($this->annotationBags as $annotationBag) {
            $annotation = $annotationBag->getAnnotation();

            $validationMethodName = $annotation->getValidationMethod();
            if ($validationMethodName) {
                if (!method_exists($controller, $validationMethodName)) {
                    throw new \InvalidArgumentException(sprintf('Validationmethod %s:%s() not found', get_class($controller), $validationMethodName));
                }
                $validation = $controller->$validationMethodName();
            } else {
                $validation = true;
            }

            if ($validation !== true && $validation != Wizard::REDIRECT_STEP_BACK && !$validation instanceof Response) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Validationmethod %s:%s() should only return true, %s or a Response Object, "%s" (%s) given',
                        get_class($controller),
                        $validationMethodName,
                        Wizard::REDIRECT_STEP_BACK,
                        $validation,
                        gettype($validation)
                    )
                );
            }

            if (!$hasFoundCurrentAction) {
                switch (true) {
                    case $validation === Wizard::REDIRECT_STEP_BACK:
                        if ($recompute === false) {
                            $this->redirectByUrl($event, $this->getPrevStepUrl($annotation));
                        }
                        return;
                        break;
                    case ($validation instanceof Response):
                        if ($recompute === false) {
                            $this->redirectByResponse($event, $validation);
                        }
                        return;
                        break;
                }
            }

            if ($annotation->isCurrentMethod()) {
                $hasFoundCurrentAction = true;
            }

            $isValid = $validation === true && !$hasInvalidActionFound;
            if (!$isValid) {
                $hasInvalidActionFound = true;
            }

            $annotation->setIsValid($isValid);
        }
    }

    /**
     * @return Wizard|null
     */
    public function getLastValidAnnotation()
    {
        $lastAnnotation = null;

        foreach ($this->annotationBags as $annotationBag) {
            $annotation = $annotationBag->getAnnotation();
            if (!$annotation->isValid()) {
                return $lastAnnotation;
            }

            $lastAnnotation = $annotation;
        }

        return null;
    }

    /**
     * @return Wizard[]
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * @param AnnotationBag[] $annotationBags
     */
    protected function setAnnotations(array $annotationBags)
    {
        usort(
            $annotationBags,
            function (AnnotationBag $a, AnnotationBag $b) {
                return $a->getAnnotation()->getNumber() > $b->getAnnotation()->getNumber();
            }
        );

        $this->annotationBags = array_values($annotationBags);

        $annotations = array();
        foreach ($annotationBags as $annotationBag) {
            $annotations[] = $annotationBag->getAnnotation();
        }

        $this->annotations = $annotations;
    }

    /**
     * @return Wizard[]
     */
    public function getVisibleAnnotations()
    {
        $annotations = array();
        foreach ($this->getAnnotations() as $annotation) {
            if ($annotation->isVisible()) {
                $annotations[] = $annotation;
            }
        }
        return $annotations;
    }

    /**
     * @return Wizard[]
     */
    public function getVisibleOrValidAnnotations()
    {
        $annotations = array();
        foreach ($this->getAnnotations() as $annotation) {
            if ($annotation->isVisible() or $annotation->isValid()) {
                $annotations[] = $annotation;
            }
        }
        return $annotations;
    }

    /**
     * @return Wizard[]
     */
    public function getVisibleAndValidAnnotations()
    {
        $annotations = array();
        foreach ($this->getAnnotations() as $annotation) {
            if ($annotation->isVisible() && $annotation->isValid()) {
                $annotations[] = $annotation;
            }
        }
        return $annotations;
    }

    /**
     * @return Wizard[]
     */
    public function getValidAnnotations()
    {
        $annotations = array();
        foreach ($this->getAnnotations() as $annotation) {
            if ($annotation->isValid()) {
                $annotations[] = $annotation;
            }
        }
        return $annotations;
    }

    /**
     * @param string $name
     * @return Wizard
     * @throws \InvalidArgumentException
     */
    public function getAnnotationByName($name)
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation->getName() == $name) {
                return $annotation;
            }
        }

        throw new \InvalidArgumentException('WizardStep with name "' . $name . '" not found');
    }

    /**
     * @param int $number
     * @return Wizard
     * @throws \InvalidArgumentException
     */
    public function getAnnotationByNumber($number)
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation->getNumber() == $number) {
                return $annotation;
            }
        }

        throw new \InvalidArgumentException('Annotation with number ' . $number . ' not found');
    }

    /**
     * @param Wizard $annotation
     * @return string
     */
    public function getNextStepUrl(Wizard $annotation = null)
    {
        if (!$annotation) {
            $annotation = $this->getCurrentActionAnnotation();
        }

        $number = $annotation->getNumber() + 1;
        return $this->getStepUrl($this->getAnnotationByNumber($number));
    }

    /**
     * @param Wizard $annotation
     * @return string
     */
    public function getPrevStepUrl(Wizard $annotation = null)
    {
        if (!$annotation) {
            $annotation = $this->getCurrentActionAnnotation();
        }

        $number = $annotation->getNumber() - 1;
        return $this->getStepUrl($this->getAnnotationByNumber($number));
    }

    /**
     * @return Wizard
     * @throws \RuntimeException
     */
    public function getCurrentActionAnnotation()
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation->isCurrentMethod()) {
                return $annotation;
            }
        }

        throw new \RuntimeException("No current action annotation found");
    }

    /**
     * @param Wizard $annotation
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getStepUrl(Wizard $annotation = null)
    {
        if (!$annotation) {
            $annotation = $this->getCurrentActionAnnotation();
        }

        foreach ($annotation->getAnnotationBag()->getAnnotations() as $annotation) {
            if ($annotation instanceof Route) {
                return $this->router->generate($annotation->getName());
            }
        }

        throw new \InvalidArgumentException("No route found for Step " . $annotation->getName());
    }

    /**
     * @param FilterControllerEvent $event
     * @param $url
     */
    protected function redirectByUrl(FilterControllerEvent $event, $url)
    {
        $event->setController(
            function () use ($url) {
                return new RedirectResponse($url);
            }
        );
    }

    /**
     * @param FilterControllerEvent $event
     * @param Response $response
     */
    protected function redirectByResponse(FilterControllerEvent $event, Response $response)
    {
        $event->setController(
            function () use ($response) {
                return $response;
            }
        );
    }
}