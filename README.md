WooCommerce Insightly Integration
==========================

Creates/updates Opportunity using data from WooCommerce orders when an order is created or saved.
Creates/updates a contact based on the billing information on the order.
Links opportunity to contact
Sets opportunity category and pipeline based on WooCommerce category mappings (also allows for specific product mappings for uncategorized products and a default category.
Sends order update emails to insightly mailbox (specific to each opportunity)

Different people may want their integration to work differently. So, I'm leaving this as a public repo for others to use as a starting point.
##Requirements

Composer

##Installation

After unzipping or cloning, run the following in Terminal (Mac/Linux) or gitbash (Windows):

composer update

This will download the Insightly API PHP wrapper
##Deploy

Zip the plugin folder and upload like any other plugin through the WordPress Dashboard or upload the folder and contents to your wp-content/plugins directory.

##Setup

A few items are required before using this.

The options panel is found under WooCommerce>Insightly

First, you'll need your Insightly API key, found in your Insightly User Settings under "API KEY"

Next, you should set your Opportunity Name Format - you can use placeholders from the order's post meta keys. The most likely used options are displayed:

%order_number%
%billing_first_name%
%billing_last_name%
%billing_email%
%billing_country%
%billing_city%
%billing_state%
%billing_postcode%
%payment_method_title%
%insightly_category%

Finally, you should set your insightly mailbox template. This will vary by account, but is typically something like this {username}-O{opportunity_id}-{account_specific_key}@mailbox.insight.ly

So, generally, navigate to one of the opportunities on the account you want to integrate with, copy the "link email address" and replace the opportunity id with %order_opportunity%