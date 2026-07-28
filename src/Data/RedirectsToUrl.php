<?php

namespace Byl\Laravel\Data;

use Byl\Laravel\Exceptions\ConfigurationException;
use Illuminate\Http\RedirectResponse;

/**
 * Byl-ийн hosted хуудас (checkout, нэхэмжлэх, billing portal) руу шууд
 * redirect хийх боломж — controller-оос объектыг өөрийг нь `return` хийхэд
 * хэрэглэгч тухайн хуудсанд шилжинэ.
 */
trait RedirectsToUrl
{
    protected function redirectToUrl(): RedirectResponse
    {
        if (blank($this->url)) {
            throw new ConfigurationException(
                sprintf('%s объектод url байхгүй тул redirect хийх боломжгүй.', static::class)
            );
        }

        return new RedirectResponse($this->url);
    }
}
