<?php

namespace App\Helper;

use FOS\RestBundle\Request\RequestBodyParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

// fixes https://github.com/FriendsOfSymfony/FOSRestBundle/issues/1712

final class RequestBodyPopulateParamConverter implements ParamConverterInterface
{
    public function __construct(private readonly RequestBodyParamConverter $bodyParamConverter)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = (array) $configuration->getOptions();

        if (isset($options['attribute_to_populate']) && is_string($options['attribute_to_populate'])) {
            $attribute = $options['attribute_to_populate'];
            $obj = $request->attributes->get($attribute);
            if (!is_object($obj)) {
                $this->throwException(new \InvalidArgumentException("Argument {$attribute} was not found. Forgot to call other ParamConverter first?"), $configuration);
            }
            $options['deserializationContext']['object_to_populate'] = $obj;
            $configuration->setOptions($options);
        }

        return $this->bodyParamConverter->apply($request, $configuration);
    }

    private function throwException(\Exception $exception, ParamConverter $configuration): void
    {
        if ($configuration->isOptional()) {
            return;
        }
        throw $exception;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        return $this->bodyParamConverter->supports($configuration);
    }
}
