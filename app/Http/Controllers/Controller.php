<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Resolve and validate a FormRequest whether invoked via HTTP or directly with a Request instance.
     *
     * @template T of \Illuminate\Foundation\Http\FormRequest
     * @param \Illuminate\Http\Request $request
     * @param class-string<T> $class
     * @return T
     */
    protected function resolveFormRequest(\Illuminate\Http\Request $request, string $class)
    {
        if ($request instanceof $class) {
            return $request;
        }

        /** @var T $formRequest */
        $formRequest = $class::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app(\Illuminate\Routing\Redirector::class));
        $formRequest->validateResolved();

        return $formRequest;
    }
}
