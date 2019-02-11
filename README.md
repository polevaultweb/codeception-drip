Codeception Drip
==========

A Drip email marketing module for Codeception.

## Installation
You need to add the repository into your composer.json file

```bash
    composer require --dev polevaultweb/codeception-drip
```

## Usage

You can use this module as any other Codeception module, by adding 'Mailtrap' to the enabled modules in your Codeception suite configurations.

### Add Mailtrap to your list of modules

```yml
modules:
    enabled:
        - Drip
 ```  

### Setup the configuration variables

```yml
    config:
        Drip:
            api_key: '%DRIP_API_KEY%'
            ccount_id: '%DRIP_ACCOUNT_ID%'
 ```     
 
Update Codeception build
  
  ```bash
  codecept build
  ```
  
### Supports

* getActiveCampaignsForSubscriber
* deleteSubscriber

And assertions

* seeCustomFieldForSubscriber
* seeTagsForSubscriber
* cantSeeTagsForSubscriber
* seeCampaignsForSubscriber

### Usage

```php
$I = new AcceptanceTester( $scenario );

$I->seeTagsForSubscriber( 'john@gmail.com', array( 'customer', 'product-x' ) );
$I->seeCampaignsForSubscriber( 'john@gmail.com', array( 12345, 67890 ) );

```

