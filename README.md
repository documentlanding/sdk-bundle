Document Landing SDK
====================

Welcome to the Document Landing SDK - a Symfony2 bundle that you
can use as the basis for your custom integration.

Reasons to use the SDK include integrating with a lead management
system other than Salesforce, and/or using sophisticated methods
to tie multiple systems or lead funnel activities together.

Be sure to watch this repository, and sign up for the newsletter 
at DocumentLanding.com to stay up on things that might affect you.


Technical Overview
==================

If you like Symfony2 and wish to be best positioned for future 
updates, leave DocumentLanding\SdkBundle unmodified. The expected 
customization is to extend the bundle and subscribe listeners to 
events, following the DocumentLanding\SdkDemoBundle example.

It is of course true that your implementation need simply respond
as Document Landing expects, and to that end this SDK may 
prove useful to the purpose of building in your preferred framework.

To be clear, Document Landing presently only maintains a Symfony2 SDK.


Getting Started: Option 1
=========================

If you are new to Symfony2, or want to quickly setup an all-inclusive 
virtual machine, go with this option.

This is the recommended way to observe the SdkDemoBundle in action.

[Setup SDK Demo with Vagrant + Ngrok](https://github.com/documentlanding/sdk-vagrant/blob/master/README.md)


Getting Started: Option 2
=========================

If you are familiar with Symfony2, merge the following changes into 
indicated files of your project before running composer update. Note 
that inclusion of DocumentLanding\SdkDemoBundle is not necessary, but
it is worth a look to accelerate your development.

composer.json
-------------
```
    "require": {
        "documentlanding/sdk-bundle": "dev-master",
        "documentlanding/sdk-demo-bundle": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/documentlanding/sdk-bundle.git"
        },
        {
            "type": "git",
            "url": "https://github.com/documentlanding/sdk-demo-bundle.git"
        }
    ]
```

app/config/config.yml
---------------------
```
sdk:
    api_key: ThisTokenIsNotSoSecretChangeIt
    lead_class: ~
    # lead_class: DocumentLanding\SdkDemoBundle\Entity\Lead
    receipt_email: ~
    audit: ~
```

app/config/routing.yml
----------------------
```
documentlanding_sdk:
    resource: "@SdkBundle/Controller/"
    type:     annotation

documentlanding_sdk_demo:
    resource: "@SdkDemoBundle/Controller/"
    type:     annotation
```

app/AppKernel.php
-----------------
```php
    new DocumentLanding\SdkBundle\SdkBundle(),
    new DocumentLanding\SdkDemoBundle\SdkDemoBundle()
```


Customizing Fields and Validations
==================================

All public and protected properties of SdkDemoBundle/Entity/Lead are sent
to Document Landing.  Don't forget the Setter/Getter methods.

The field type, validations, and possible values are all defined in the
Lead Entity file as Annotations.

The path to your your Lead Entity is set in app/config/config.yml

```
sdk:
    lead_class: DocumentLanding\SdkDemoBundle\Entity\Lead
```


Integrating Your SDK Fields with Another Service
================================================

In config.yml, set sdk.lead_class to "~"

```
sdk:
    lead_class: ~
```

You can now post field data to http://your-domain/api/lead/schema.  This will
cause the SDK to create a Lead Entity matching your post.

The format of this data is JSON, with the contained properties patterned after 
a condensed version of Salesforce conventions.  This will be further documented 
soon, but here's a quick visual of the expected payload. Supported field types 
are string, integer, picklist, multipicklist, boolean, and textarea.  Other types 
are mapped to string. See the /DocumentLanding/Services/SDKManager->setLeadSchema(...) 
for more information, and reach out if you would like to see another field type 
receive particular support.

```
{
    "fields" : [

        {
            "defaultValue" : null,
            "label" : "Last Name",
            "length" : 80,
            "name" : "LastName",
            "nillable" : 0,
            "type" : "string"
        },

        {
            "defaultValue" : null, 
            "label" : "Salutation",
            "length" : 40,
            "name" : "Salutation",
            "nillable" : 1,
            "picklistValues" : [
                {
                    "active" : 1,
                    "defaultValue" : null,
                    "label" : "Mr.",
                    "value" : "Mr."
                },
                {
                    "active" : 1,
                    "defaultValue" : null,
                    "label" : "Ms.",
                    "value" : "Ms."
                }
            ],
            "type" : "picklist"
        },

        {
            "defaultValue" : null,
            "label" : "Email",
            "length" : 80,
            "name" : "Email",
            "nillable" : 0,
            "type" : "email"
        },

        {
            "defaultValue" : 1,
            "label" : "I would like your Newsletter",
            "length" : 0,
            "name" : "Newsletter",
            "nillable" : 1,
            "type" : "boolean"
		}

    ]

)
```


Customizing Event Listeners
===========================

Event listeners are the recommended place to integrate with other systems
(and the main reason this SDK exists).

The Service DocumentLanding/SdkDemoBundle/Service/SdkDemoManager implements 
a listener function for all Events associated with the SDK.


Do Not Delete Lead Data
=======================

Use a field called "DeletedAt" or similar to "delete" your Lead objects.
Don't remove rows from the lead table in the database.

Document Landing stores the ID of your leads, which this SDK responds
with following the first creation or update of a Lead. Subsequent "Lead 
enhancements" of your leads via "progressive gates" depend upon this 
handshake.  

If Document Landing provides an ID associated with a row you have fully 
deleted, the lead enhancement is simply dropped.

If the ID is reassigned by your database to another lead, different 
people will combine as a single Lead.  That will happen if you empty 
the database and restart auto-increment.