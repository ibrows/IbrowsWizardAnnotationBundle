<?php

namespace Ibrows\Bundle\WizardAnnotationBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AnnotationDriver
{
    /**
     * @var AnnotationHandler
     */
    protected $annotationHandler;

    /**
     * @var string
     */
    protected $annotationClassName;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param Reader $reader
     * @param AnnotationHandler $annotationHandler
     * @param $annotationClassName
     */
    public function __construct(Reader $reader, AnnotationHandler $annotationHandler, $annotationClassName)
    {
        $this->reader = $reader;
        $this->annotationHandler = $annotationHandler;
        $this->annotationClassName = $annotationClassName;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $controllerArray = $event->getController();
        if (!is_array($controllerArray)) {
            return;
        }

        $controller = $controllerArray[0];
        $methodName = $controllerArray[1];

        $controllerReflection = new \ReflectionClass($controller);
        // Current Method is not part of wizard
        if (!$this->reader->getMethodAnnotation($controllerReflection->getMethod($methodName), $this->annotationClassName)) {
            return;
        }
        $annotationBags = $this->getMethodAnnotationBags($controllerReflection, $methodName);

        $this->annotationHandler->handle($event, $annotationBags);
    }

    /**
     * @param \ReflectionClass $controller
     * @param $currentMethodName
     *
     * @return array
     */
    protected function getMethodAnnotationBags(\ReflectionClass $controller, $currentMethodName)
    {
        $bags = array();

        foreach ($controller->getMethods() as $methodReflection) {
            /** @var Wizard $annotation */
            $annotation = $this->reader->getMethodAnnotation($methodReflection, $this->annotationClassName);

            if ($annotation) {
                if ($methodReflection->getName() == $currentMethodName) {
                    $annotation->setIsCurrentMethod(true);
                }

                $bag = new AnnotationBag(
                    $annotation,
                    $this->reader->getMethodAnnotations($methodReflection)
                );
                $annotation->setAnnotationBag($bag);

                $bags[] = $bag;
            }
        }

        return $bags;
    }
}
