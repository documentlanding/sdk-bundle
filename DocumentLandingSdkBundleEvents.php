<?php

namespace DocumentLanding\SdkBundle;

final class DocumentLandingSdkBundleEvents
{

    /**
     * documentlanding.new_lead is sent on new lead.
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\NewLeadEvent instance.
     *
     * @var string
     */     
    const NEW_LEAD = 'documentlanding.new_lead';

    /**
     * documentlanding.updated_lead is sent on a lead update.
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\UpdatedLeadEvent instance.
     *
     * @var string
     */     
    const UPDATED_LEAD = 'documentlanding.updated_lead';

    /**
     * documentlanding.api_request is sent on request to api methods.
     *
     * Implementation may have additional requirements.
     * An example of an optional validation is the Document Landing IP Range(s).
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\ApiRequestEvent instance.
     *
     * @var string
     */     
    const API_REQUEST = 'documentlanding.api_request';

    /**
     * documentlanding.refresh_token_request is sent upon receiving a refresh token
     * request from Document Landing. This occurs approximately once an hour.
     *
     * If the developer sets an access_token in config.yml, that access_token
     * will always be used. This is just fine.  The call to refresh will
     * simply always return this value.
     *
     * Alternatively setting access_token to "~" in config.yml indicates the intention 
     * to fully use RefreshTokenRequestEvent. With RefreshTokenRequestEvent, the developer
     * attachs the "new" access_token in another Bundle, which is then returned to 
     * Document Landing.
     *
     * Maximum length of the access_token is 255 characters.
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\RefreshTokenRequestEvent instance.
     *
     * @var string
     */     
    const REFRESH_TOKEN_REQUEST = 'documentlanding.refresh_token_request';

    /**
     * documentlanding.load_lead is sent on each attempt to load a lead.
     * It is also sent on a new lead (no id) to ensure the email address isn't already captured.
     * Such a lead is switched from new to existing in the response.
     *
     * Document Landing attempts to load a lead when "email" is present in the query string.
     * No lead data is exposed, but no previously filled fields are presented the lead.
     *
     * This event allows a remap of the email field name in the search criteria array.
     * 
     * The event also passes other query string parameters from original request.
     * Those can be also added to the search criteria. This is an advanced use case.
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\ApiRequestEvent instance.
     *
     * @var string
     */     
    const LOAD_LEAD = 'documentlanding.load_lead';
    
    
    /**
     * documentlanding.pre_update_schema is sent when an external repository initiated schema sync.
     *
     * Much here is left to the judgement of the developer.
     *
     * Assumed tasks include backing up the database to desired location.
     *
     * Setting any Error message will Abort the schema update.
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\PreUpdateSchemaEvent instance.
     *
     * @var string
     */  
    const PRE_UPDATE_SCHEMA = 'documentlanding.pre_update_schema';

    /**
     * documentlanding.post_update_schema after the SDK entity and database are completely synced.
     *
     * Assumed tasks include populating the database with existing lead data.
     * To assist this effort, the entity lead class is attached to the event.
     *
     * The event listener receives an
     * DocumentLanding\SdkBundle\Event\PostUpdateSchemaEvent instance.
     *
     * @var string
     */  
    const POST_UPDATE_SCHEMA = 'documentlanding.post_update_schema';

}