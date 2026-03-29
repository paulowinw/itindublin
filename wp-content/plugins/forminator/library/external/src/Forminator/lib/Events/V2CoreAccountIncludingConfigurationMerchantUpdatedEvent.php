<?php

// File generated from our OpenAPI spec
namespace Forminator\Stripe\Events;

/**
 * @property \Stripe\RelatedObject $related_object Object containing the reference to API resource relevant to the event
 */
class V2CoreAccountIncludingConfigurationMerchantUpdatedEvent extends \Forminator\Stripe\V2\Core\Event
{
    const LOOKUP_TYPE = 'v2.core.account[configuration.merchant].updated';
    /**
     * Retrieves the related object from the API. Make an API request on every call.
     *
     * @return \Stripe\V2\Core\Account
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     */
    public function fetchRelatedObject()
    {
        $apiMode = \Forminator\Stripe\Util\Util::getApiMode($this->related_object->url);
        list($object, $options) = $this->_request('get', $this->related_object->url, [], ['stripe_context' => $this->context], [], $apiMode);
        return \Forminator\Stripe\Util\Util::convertToStripeObject($object, $options, $apiMode);
    }
}
