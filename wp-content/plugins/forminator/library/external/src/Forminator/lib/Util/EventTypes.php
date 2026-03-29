<?php

namespace Forminator\Stripe\Util;

class EventTypes
{
    const v2EventMapping = [
        // The beginning of the section generated from our OpenAPI spec
        \Forminator\Stripe\Events\V1BillingMeterErrorReportTriggeredEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V1BillingMeterErrorReportTriggeredEvent::class,
        \Forminator\Stripe\Events\V1BillingMeterNoMeterFoundEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V1BillingMeterNoMeterFoundEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountClosedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountClosedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountCreatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountCreatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountLinkReturnedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountLinkReturnedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountPersonCreatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountPersonCreatedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountPersonDeletedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountPersonDeletedEvent::class,
        \Forminator\Stripe\Events\V2CoreAccountPersonUpdatedEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountPersonUpdatedEvent::class,
        \Forminator\Stripe\Events\V2CoreEventDestinationPingEvent::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreEventDestinationPingEvent::class,
    ];
}
