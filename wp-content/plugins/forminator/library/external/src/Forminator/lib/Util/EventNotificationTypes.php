<?php

namespace Forminator\Stripe\Util;

class EventNotificationTypes
{
    const v2EventMapping = [
        // The beginning of the section generated from our OpenAPI spec
        \Forminator\Stripe\Events\V1BillingMeterErrorReportTriggeredEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V1BillingMeterErrorReportTriggeredEventNotification::class,
        \Forminator\Stripe\Events\V1BillingMeterNoMeterFoundEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V1BillingMeterNoMeterFoundEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountClosedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountClosedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountCreatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountCreatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountLinkReturnedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountLinkReturnedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountPersonCreatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountPersonCreatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountPersonDeletedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountPersonDeletedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreAccountPersonUpdatedEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreAccountPersonUpdatedEventNotification::class,
        \Forminator\Stripe\Events\V2CoreEventDestinationPingEventNotification::LOOKUP_TYPE => \Forminator\Stripe\Events\V2CoreEventDestinationPingEventNotification::class,
    ];
}
