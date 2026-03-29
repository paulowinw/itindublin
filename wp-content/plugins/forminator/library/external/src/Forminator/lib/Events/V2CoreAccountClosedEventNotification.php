<?php

// File generated from our OpenAPI spec
namespace Forminator\Stripe\Events;

/**
 * @property \Stripe\RelatedObject $related_object Object containing the reference to API resource relevant to the event
 */
class V2CoreAccountClosedEventNotification extends \Forminator\Stripe\V2\Core\EventNotification
{
    const LOOKUP_TYPE = 'v2.core.account.closed';
    public $related_object;
    /**
     * Retrieves the full event object from the API. Make an API request on every call.
     *
     * @return V2CoreAccountClosedEvent
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     */
    public function fetchEvent()
    {
        return parent::fetchEvent();
    }
    /**
     * Retrieves the related object from the API. Make an API request on every call.
     *
     * @return \Stripe\V2\Core\Account
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     */
    public function fetchRelatedObject()
    {
        return parent::fetchRelatedObject();
    }
}
