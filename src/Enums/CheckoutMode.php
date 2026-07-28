<?php

namespace Byl\Laravel\Enums;

enum CheckoutMode: string
{
    case Payment = 'payment';
    case Subscription = 'subscription';
}
