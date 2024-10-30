=== Contasimple ===
Contributors: contasimple
Tags: contasimple, invoicing, billing, accounting, taxes, facturacion, facturas, impuestos, contabilidad, tax, invoice
Requires at least: 3.8
Tested up to: 6.4.3
Requires PHP: 5.5
Stable tag: 1.30.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This module allows you to export all WooCommerce orders as invoices in Contasimple.

== Description ==

This plugin is an extension for your WooCommerce online shop that synchronizes automatically all generated orders to your Contasimple account.

Gain more visibility regarding your business state in real time and keep your accounting and taxes up to date.

= Key features =

* Automatically syncs new completed orders to your Contasimple account.
* You can easily export previous orders generated before the plugin installation with only a few clicks.
* Configure automatic or manual email sending to your customers with attached PDF invoices from Contasimple.
* Clients will be automatically created in Contasimple using WooCommerce checkout customer address information.
* Configure the format for sequential numbering invoice generation, allowing to create series with custom prefixes and/or the current year in 2 or 4 digits format (ex: INV-2018-00001 or #F180000001).
* Configure different numbering series to separate regular invoices, negative rectification invoices (also known as amendments, credit slips or refunds) and simplified invoices (also known as purchase receipts or tickets) as many countries have law regulations in this regard.

To learn more about the key benefits of the Contasimple platform as a whole, please check the main [Contasimple Website](https://www.contasimple.com/ "Billing, accounting and taxes web program for freelancers, companies, professionals and agencies.").

= Technical specifications =

* Requires at least WooCommerce 2.1 version. Upgrading to WooCommerce 3 or greater is strongly recommended.
* Initially developed for single-site installations. Multi-site support coming soon.
* Requires PHP cURL extension enabled to communicate with Contasimple's API servers. This is common, but you might need to check it with your hosting provider.

== Installation ==

This section describes how to install the plugin and get it working. In case of doubt, please have a look at the Screenshots section for further clarification.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Contasimple for WooCommerce, log in to your WordPress dashboard, navigate to the `Plugins` menu and click `Add New`.

In the search field type `Contasimple` and click `Search Plugins`. Once you’ve found our sync plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking `Install Now`.

= Manual installation =

The manual installation may be needed in certain circumstances, for example in case you have received from us a fresh new version of the plugin, not yet released on the official repository.

The traditional option is to decompress the .zip file provided by us and upload the `contasimple` folder to your WordPress `/wp-content/plugins` folder using an FTP client. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

Probably easier, you can also upload the zip file directly from the plugins section: navigate to the `Plugins` menu and click `Add New`, then `Upload Plugin` and locate the zip file on your PC (typically in your web browser download's folder). In case of error due to either restrictive file permissions or WordPress complaining about the plugin already existing, you might need to resort to the previous described method.

= Configuration =

Once installed, the first thing to do is to configure the plugin to be able to connect with your Contasimple account. If you do not have a Contasimple account yet, you can register now by [Clicking here](https://www.contasimple.com/alta-nuevo-usuario.aspx "Create a Contasimple account now!").

Assuming you are already registered and have configured your fiscal region and company settings, you will be able to generate a Contasimple 'APIKEY'. This key is unique to you and will grant your WooCommerce access to Contasimple's servers so that the sync process can happen.

= Step 1 =

To generate the APIKEY, you must log first into your account at [Contasimple Website](https://www.contasimple.com/) and then click on the Settings icon wheel on the top bar, locate the sidebar menu and scroll down to the `Other options` section and click `External Applications`. When the new section is loaded, scroll down to the bottom and you will see a `Authorization keys` section. Click on ``+ Add authorization key` and choose `WooCommerce`.

A new key will be generated, which will look like a long sequence of random characters, something like 'fcc6360446b04f199b715c58a9116957'. Select the key (but not the quote marks) with the help of the mouse and copy/paste it. You will need it during the next step.

= Step 2 =

Now that we have generated the APIKEY, we need to configure the plugin to use it. All Contasimple's plugin settings are handled by WooCommerce, since this is an integration for it. Therefore, you must navigate to `WooCommerce > Settings` and head to the `Integration` tab.

Since this is your first time using the plugin, you will be presented with the Configuration Wizard. Click on the `Link account` button. The first step will ask you for the previous generated APIKEY, paste the value on the text field and click `Log In`. You should see a success message and then you will be presented with a dialog to choose between your companies (just in case that you manage more than one) and you can also configure the correspondence between WooCommerce payment methods and Contasimple's.

Congratulations! You have already configured the basics of the module. You can now start syncing your invoices. For more detailed info about additional configuration settings, please see the FAQ section.

== Frequently Asked Questions ==

= How does the synchronization exactly work? =

Once the plugin has been successfully configured, every order that changes its state to 'Completed' will be queued to be automatically sent to Contasimple. The process might be triggered by many different means, for example, when an order is paid via a payment gateway, like Redsys, or when you manually mark an order as completed in the back-office, once you have verified a payment via bank transfer.

Most likely the order will be immediately sent to Contasimple when the order status changes. However, in case an invoice cannot be instantly sent, due to either a high number of incoming orders to process or for whatever other reason, the process will be resumed again each time an order reaches the shop, until the queue is empty and all orders are sent.

You can check the sync status for all completed orders at any moment on the `Invoices` section that the plugin will add to your WooCommerce menu entry.

= I already have orders in my shop. Will I be able to sync them? =

Yes. While the plugin will automatically handle all new orders of the shop, you can export all previous orders by navigating to the `WooCommerce > Invoices` section and clicking the `Create from previous orders` button.

You will be presented with a page that lists all completed orders that have not yet been queued for sync, this of course includes orders prior installing the plugin. You can check some or all of them and then pick the `Create` action from the actions dropdown list that appears on top.

Orders added this way will not be synced automatically right now, but they will be added to the sync queue. Hence you can let the plugin send them automatically when a new order is completed or, if you want to sync right now, you can always navigate back to the `Invoices` page and manually trigger the process by clicking the `Sync now` button.

Notice that in this screen you can filter invoices by fiscal periods. This might interest you in case that you want to add to sync all the invoices for an ongoing tax collection period, but do not want to do further action with already declared invoices from previous fiscal periods.

= How does the invoice numbering system work? =

In contrast to other eCommerce platforms, WooCommerce does not do sequential numbering from the get go, but uses WordPress internal post identification system. Sharing ID with blog post and many other types of content means no sequential numbering is possible by default.

To solve this, Contasimple will keep the count of your synced orders and will assign the next sequential number any time an invoice is successfully synced, based on the mask assigned to the corresponding invoice series.

Masks can be customized on the `WooCommerce > Settings > Integrations` page. There are three configurable masks for regular invoices, negative rectification invoices (for refunds) and simplified invoices (tickets) since many countries, like Spain, have fiscal laws that require to keep track of each type separately.

By default, each one is set at different values, so that you will be working with three different series for each type of scenario. However, you could play with this and set the same value for both regular invoices and simplified invoices, or even for refunds as well, to make them fall into the same series in case that you prefer it and your country does not have any particular requirement on this.

To know how to form the desired mask, please log into Contasimple website and go to `Settings > Invoices`, there you will find more information and examples.

= How does the invoice emailing feature work? =

This plugin uses WooCommerce built-in emailing system to deliver the invoice emails to your customers. If you go to `WooCommerce > Settings > Emails` you will see that you have a new type of email listed `Order invoice`. Like all WC email types, you can enable or disable it here and also change a few things, like the default subject or if you want it to be a plain text email or a fully fledged HTML template.

Default templates provided for the Contasimple invoice emails include only a brief message and the generated invoice PDF as a file attachment because the default WC `Completed Order` email template already has an item summary and we feel that it would be redundant to include this info again. However, if you want -and know how to- you could customize the templates to suit your needs.

Something to keep in mind is that, even if the invoice emails are set to enabled, only orders completed during the last 24 hours will be emailed automatically to your customers. We have set this restriction to avoid potential unwanted situations, like syncing all your previous year invoices just for historic purposes and then having all your customers emailed out of nowhere.

You can always send an invoice manually as many times as you consider it necessary to a certain customer, by going to the plugin `Invoices` section and clicking the email action button for the desired invoice.

= I already have a plugin that handles these or other invoicing feature, can I work with both? =

It really depends, but most likely no.

As described in a previous section, Contasimple handles invoice numbering sequence in a very specific way, which is unaware of other installed plugins and which, conversely, does not expect other plugins to hook into it. Therefore, if you have other plugins installed, most likely you will end generating duplicated invoices for your orders and with totally different numbering series.

If you finally decide to use the Contasimple plugin to generate legally compliant invoices for your orders, we strongly recommend to disable all other invoicing plugins that you might be trying or using during the past.

If you feel that we are missing some very cool feature present in other plugins that ties particularly to tax legislation, feel free to contact us to suggest it and we will consider it.

= I already synced previous orders and I see they have a strange 4 digit prefix in them. What does that mean? =

Previous orders added manually to the sync queue use the configured mask under Contasimple settings, but will also add a random prefix to it. This is not configurable and it is done as a safety mechanism to avoid breaking fiscal laws.

Most countries require invoice numbering series to be sequential but also to respect issuing order. Imagine that you install and configure the plugin and get a few incoming orders that get instantly synced to your Contasimple account, but then you decide to have a look at the existing orders for the whole current year and want to sync them as well. You would end with an incorrect issuing sequence.

This is why Contasimple adds a random prefix each time a manual invoice creation is performed. This means you can end with a few different batches of invoicing series, which might seem not ideal. However, most countries fiscal laws allow to have different invoicing series if it can be justified, as long as the issuing order for each series is respected.

= I understand the previous point, but I really, REALLY want to have a unique series for all my invoices. Is there any way I could achieve that? =

With some limitations, yes. Assuming you have still not synced any invoice to Contasimple or that you can delete in Contasimple Web the already uploaded ones so that you can start back from scratch, you can:

* (Optional) Disable your shop to avoid any orders to sync automatically during this process. WooCommerce does not allow this by default but there are plugins that allow you to.
* Set the desired numbering series format in `WooCommerce > Settings > Integrations > Contasimple` keeping in mind that 4 additional random numbers will be added first during the already existing orders sync process.
* Navigate to the `Create from previous orders`, mark as selected all the desired orders and click `Create` from the bulk actions drop-down control as described previously.
* Navigate back to `Invoices` and click the `Sync now` button.
* The page will reload with the sync results (might take some time).
* If all invoices are synced OK, you can proceed to next step. In case there were any errors present, they must be solved before continuing so that you do not miss any invoice (see the next FAQ section).
* Once all previous orders have been synced,  write down (or just copy/paste) the 4 random digits appended to all invoices.
* Finally, you can go back to the plugin settings and modify your numbering series mask format to add these 4 digits before the already defined masks. This way, all new incoming orders will respect the same series when their status changes to `Completed`. Just keep in mind that anytime that you add previous existing orders to the queue, the plugin will create a new series to avoid the aforementioned numbering sequence mismatch.

= My store sells internationally and allows payments with more than one currency (not just with translations). Is this supported? =

Unfortunately, no. Or at least not without some additional work from your side.

Contasimple only works with the currency configured in Contasimple Web for the desired fiscal region, since most countries mandate to declare taxes with its own currency. If you try to sync orders paid with a currency different than the one configured for your fiscal region, you will get an error.

Accounting conciliation due to currency conversion is out of the scope of the plugin and you should configure your multi-currency plugin to work internally with only one currency, like WooCommerce does by default, and then try to sync to Contasimple.

Note that, [in its documentation](https://docs.woocommerce.com/document/shop-currency/), WooCommerce also recommends to work with only one currency internally (as it does by default) and handle multiple currencies for the customers by showing estimated conversions via the [Currency Converter Widget](https://woocommerce.com/products/currency-converter-widget/).

= Is Wordpress Multisite (Network installation) supported? =

At the time of release we only support single-site mode. However, we are already working on bringing multisite support and it should be available soon.

= We get an Error 504 (Gateway Time Out) after a minute or so when trying to sync invoices. What's going on? =

Most web hosting servers have a certain timeout set to abort long-lasting processes in order to save resources. If you have a lot of invoices pending to sync and depending on your server configuration, you might not be in time to sync all of them at once. The process will abort after a certain time passes (sometimes as short as 30 seconds) and you will get the infamous 504 error.

If you have a dedicated VPS you might be able to configure Apache/Nginx timeout variable settings to greater values (ex: 5 minutes). You might be able to do so also on some premium shared web hosting services.

In any case, you can always restart the syncing process from the `Invoices` section (or just wait for a new order to be completed) and the syncing queue will resume, so you will be able to sync all invoices eventually. However, we would recommend a quality hosting service where a sysadmin can fine-tune the settings to suit your goals.

= There are invoices showing errors on the `Invoices` page. What can I do? =

Errors during the sync process might occur due to several reasons:

* Invalid invoicing data. This is the most common case. For example: a customer entered the NIF with an invalid format during the checkout process or VAT types applied are incorrect for your fiscal region.
* Server downtime either on your web server side or on Contasimple side, which will not allow to establish a connection. This might happen due to maintenance tasks or ISP provider shortage.
* Unexpected technical issues. For example:
    * A WooCommerce/WordPress version that has a bug or a deprecated/missing feature not accounted for, which might cause the plugin to break.
    * 3rd party plugins that might interfere with ours.
    * Very specific web server settings that cause incompatibilities (PHP unsupported version, Apache/Nginx restrictive policies, Javascript conflicting libraries or file system permissions).

If an error occurs due to invalid invoicing data or other well-known causes, Contasimple will inform you and you will either have to edit the order/customer details or configure your shop with the correct settings so that the invoice can be safely synced.

For example, if VAT rate changes occur the next year and your shop is not up to date when an order completes, you will receive an error during the sync process. You will see an `Invalid VAT type for your fiscal region` error and you will need to configure the VAT correctly for that specific region before being able to sync OK, which avoids potential fiscal issues and helps you improve your online business invoicing compliance with your country's fiscal law.

In case you see an `Unknown Error` it will mean that the error probably occurred due to technical difficulties during the syncing process. We would advise you to try the syncing process again later, just in case there was a temporary connection problem on either side. However, if the syncing fails systematically every time you try to sync some or all of your invoices, there might be a technical issue that is preventing the plugin to work correctly and will need further assistance. In that case, please contact us so that we can help you troubleshoot your issue.

Our team might request you to provide an error log file which will help us gathering crucial information to detect the root cause. To obtain this error file, you can either:

* Download it easily from the `WooCommerce > Settings > Integrations > Contasimple` page at the `Log` section. Please select the desired date when you found an issue and click the `Download` button.
* Get the file directly from your hosting via FTP or File Manager on your cPanel/Plesk admin panel or similar. The file will be located under the WordPress modules folder, more precisely at: `/wp-content/plugins/woocommerce-contasimple` and will have a name format similar to `contasimple_30-12-2017.log`.

= Some invoices are synced with different concept lines than the original order in WooCommerce, grouped by VAT type. Why it happens? =

Contasimple and WooCommerce represent differently the prices of the products and applied taxes. While Contasimple always works with two decimal places, WooCommerce allows you to configure the number of decimal places that we want to appear on the screen (by default they are two, as in Contasimple) but internally it works with six decimal places in almost all its calculations and only stores the numbers with two decimal places for the auto-calculated values of the total amount with and without taxes of the entire order.

This difference in the internal representation of decimal numbers causes that, on some occasions, when transferring the amounts of WooCommerce products to Contasimple the invoice total amount may not match with respect to the original order.

Let's see an example to understand it better:

Suppose that our store sells a product of which many units are usually sold per order at a price of 0.99 euros per unit, taxes already included. WooCommerce allows the option of entering the prices of the products with VAT already included, so that WooCommerce itself calculates the resulting tax base so that, once the VAT corresponding to the default country of the store has been applied, the final price of the unit is €0.99.

If a customer buys a single unit of our product in Spain and 21% VAT is applied, in this example the price that WooCommerce will calculate to adjust the tax base to the amount we want to invoice the user with taxes included will be 0.99 / 1.21 = 0.818181 euros. The amount of VAT applied to the product will therefore be the remaining (0.171818 euros). You can read more information about tax calculation on this official WooCommerce page (https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce).

As WooCommerce shows the prices on the screen already rounded to the default number of digits, in the shopping cart we would see 0.82 euros for the base and 0.17 euros for VAT, and a total of 0.99 euros. This type of order could be correctly synchronized with Contasimple because even while rounding the prices to two decimal places before adding the amounts, the result is the same (no error accumulates).

Now suppose that another user buys 100 units of the same product.

The calculation that WooCommerce performs internally to calculate the totals is as follows:

Unit Price: 0.818181
Units: 100
Total tax base: 81.818181 (81.82 is displayed on the screen)
Amount 21% VAT: 17.181818 (17.18 is displayed on the screen)
Total: 98.999999 (99 is displayed on the screen and 99.00 is also saved as the final amount)

As we can see, WooCommerce makes use of the 6 decimal places in all its calculations regardless of whether it shows rounded prices on the screen.

However, when the plugin tries to transfer these values to the Contasimple invoice, it first has to round all the components of the invoice line to two decimal places, and then carry out the calculations so, in the end, the invoice line in Contasimple becomes:

Unit price: 0.82 (already rounded)
Units: 100
Total tax base: 82.00
Amount 21% VAT: 17.22
Total: 99.22

If these amounts were ever to be synchronized, a difference of 22 cents would accumulate (18 cents would come from the price without taxes and 4 cents from the VAT) with respect to the original order due to the slight difference in rounding of decimals that occurs in the unit of the product. This difference is further aggravated if the order contains other products that also accumulate rounding errors.

Contasimple prioritizes at all times that the amounts transferred for the taxable base totals and VAT are accurate with respect to those of the original order to ensure that the user accounting is never out of balance and to avoid incorrect values to be ever declared to the Tax Agency. Therefore, the plugin can resort to the strategy of reorganizing the invoice lines in order to apply rounding at the end of the calculation and thus be able to minimize calculation errors and synchronize the invoice with the exact original amounts.

For example, if the previous invoice line is reformulated by taking the total price of 100 units and introducing it as a single unit, we can see that the new total amount in the invoice line in Contasimple matches the original order item:

Unit price: 81.82 (0.818181 * 100, rounded)
Units: 1
Total tax base: 81.82
21% VAT amount: 17.18
Total: 99.00

This invoice can be safely synchronized to Contasimple since it does not cause any accounting mismatches.

Note: Since version 1.8 of the plugin, all the information of the original order is transferred to the comments of each invoice line, so that the end customer will always see the information of the original order in WooCommerce and no details will be lost.

This situation can be avoided in the vast majority of cases by applying the following recommended settings to your WooCommerce store:

**1 - Configure WooCommerce to enter product prices without taxes.**

This is the default option and it is recommended to keep it this way unless it is imperative (for example, in the case of wanting to sell a product at a single price globally, regardless of the rates applied amongst different countries / fiscal regions).

To return to the default option you must go to the menu 'WooCommerce > Settings > Taxes > Prices with taxes included', select the option 'No, I will enter the prices without taxes' and click on 'Save the changes'.

In the event that you want your customers to view prices with VAT included regardless of whether they are entered without VAT in the store, there are two other settings in this same section that can help you: 'Show prices in the store' and 'Show prices in the cart and at checkout'. In both cases, select the option 'VAT included' if applicable. These two options do not affect the internal calculation of the amounts.

**2 -  Configure WooCommerce to apply rounding at line level.**

This is also the default option and it is recommended to keep it with the default value as it replicates more accurately the rounding system applied by Contasimple (for each field) and therefore makes it easier for the invoice to be synchronized to contain fewer rounding errors.

To return to the default option you must go to the menu 'WooCommerce > Settings > Taxes > Rounding' and uncheck the box 'Rounding of tax in the subtotal, instead of rounding for each line'.

**3 - Review the pricing policies of the existing products in the store.**

If you have the flexibility to define the prices of the products in your catalog, then we recommend that you use as few decimal places as possible to minimize the probability of rounding errors, trying never to use more than two decimal places. In other words, **prices must be set in such a way that the unit tax base is representable to two decimal places, without the need for rounding**.

If you had previously activated the setting to enter the prices of products with taxes already included and you change it to work in the recommended way, you must bear in mind that the change will apply only to the new products that you register from now on and to new orders placed in the store. Therefore, you should review the existing products and edit the prices taking into account that VAT will now be applied later during the purchase process.


= Why are some invoices associated with a customer called Multiple customers instead of their original customer? =

This happens as long as the user who makes the purchase in the store has not filled in the NIF field (or the equivalent field in the fiscal region that you have configured in your company in Contasimple).

In Contasimple, every client must have the NIF field filled in, since it is mandatory for the client to be eligible as the target of a fiscally valid invoice.

When a WooCommerce order is synchronized to Contasimple, the plugin creates an invoice and associates it with the customer with the same NIF that exists in Contasimple, and if it does not exist it tries to create it from the data provided in the filled out 'Billing details' fields by the user in the WooCommerce purchase form.

By default, WooCommerce does not include a field to enter the 'Tax Identifier / NIF' during the purchase process, so the plugin creates it during its installation, however, filling in this field is not mandatory during the purchase process, since many customers of the online store may be individuals who do not want the order invoice and who do not want to provide this information either.

This scenario would be equivalent to making a purchase in a physical store, in which the customer can request an invoice and to do so provide all their data (including the fical identifier / NIF) to the seller, or they can choose to take the sales ticket as proof of the transaction (also known as a simplified invoice), which identifies the seller and the products sold but not the buyer.

However, when synchronizing that sale order with Contasimple, it requires that a tax identifier or customer NIF is specified, therefore, in the event that the customer has not provided a value, the plugin ignores the fields filled in by the user in the WooCommerce checkout form and instead associates the order with a fictitious customer called 'Multiple customers', equating that invoice to a purchase receipt.

These invoices are treated by the program as if they were sales tickets, and are synchronized in a separate series, which can be configured in the 'Settings' menu of the Contasimple plugin for WooCommerce.

In case your online commerce is only 'B2B' or you simply want to always request the NIF field from all your clients, since version 1.9 of the plugin you can configure the NIF field to be mandatory, so that all clients must enter it in order to finalize the purchase process. In this way you can prevent invoices from being synchronized without information from your customers.

You will find this setting in the plugin settings section (WooCommerce > Settings > Integration > Contasimple). You just have to check the box 'NIF / Company identifier mandatory' and save the changes.

If the customer that will be used to group sales orders to customers without a VAT number has not been previously configured, the plugin will try to configure it automatically when synchronizing the first order, and assigns it by default the name 'Multiple customers' and the fictitious VAT number '00000000X'. If you already had a client registered in Contasimple previously with this NIF, when synchronizing the invoice you will be informed that said client cannot be generated and the synchronization will fail until you configure it manually from the Contasimple website. You can create said client manually, change its name or even the NIF from the Configuration -> Taxes screen of the Contasimple website, in the 'Invoices to unidentified clients' section.

== Screenshots ==

1. Settings screen. Linking you Contasimple account (step 1).
2. Settings screen. Entering your APIKEY (step 2).
3. Settings screen. Picking your company (step 3).
4. Settings screen. Picking your payment methods (step 4).
5. Settings screen. Linked account info summary and unlinking options (if reconfiguration is needed).
6. Settings screen. Numbering series format masks for regular invoices, negative invoices (refunds) and simplified invoices (tickets).
7. Settings screen. Error log file downloading for customized troubleshooting with Contasimple technical team.
8. Invoices screen. Empty screen showing there are no new completed orders since activation. Previous orders can be imported by clicking the `Create from previous orders`.
9. Import screen. Selecting the desired fiscal period.
10. Import screen. Selecting all filtered orders at once via the global checkbox control. Select `Create` from the dropdown list and hit `Apply` to start.
11. Import screen. Success message after orders are imported. You can go back to `Invoices` to see the pending sync queue.
12. Invoices screen. Pending invoices. Will be automatically synced once a new order completes, or you can manually `Sync pending now` to trigger the process immediately.
13. Invoices screen. All invoices are synced successfully. You can perform actions on the right (open in Contasimple Web, Download PDF and send email with PDF to customer).
14. Invoices screen. Sending an invoice PDF to a customer manually.

== Changelog ==

= 1.30.0 =
* Fixes not being able to create invoices from previous orders if HPOS is enabled.

= 1.29.0 =
* Some bug fixes and code improvements.

= 1.28.0 =
* New: Compatibility with HPOS.
* Some bug fixes and code improvements.

= 1.27.0 =
* Some bug fixes and code improvements.

= 1.26.0 =
* Fixes synchronization issues in some edge-cases (regarding partial refunds and rounding issues).
* Improvements in invoice concurrency sync control.

= 1.25.0 =
* Added setting to enable/disable concurrency control checks.
* Some bug fixes and code improvements.

= 1.24.0 =
* Some bug fixes and code improvements.

= 1.23.0 =
* Some bug fixes and code improvements.

= 1.22.0 =
* Updated integration with the external VIES validation service.
* Some bug fixes and code improvements.

= 1.21.0 =
* Some bug fixes and code improvements.

= 1.20.0 =
* Some bug fixes and code improvements.

= 1.19.0 =
* New: You can now configure the plugin to sync the invoices when orders enter the default 'On-Hold' status.

= 1.18.0 =
* Some bug fixes and code improvements.

= 1.17.0 =
* Some bug fixes and code improvements.

= 1.16.0 =
* New: The plugin now uses the in-built Contasimple numbering series feature.

= 1.15.0 =
* Some bug fixes and code improvements.

= 1.14.0 =
* Some bug fixes and code improvements.

= 1.13.0 =
* New: You can now customize the text that will be displayed in the NIF field.
* Some bug fixes and code improvements.

= 1.12.0 =
* Some bug fixes and code improvements.

= 1.11.0 =
* New: You can now configure the plugin to sync the invoices when orders enter the 'Processing' status. Until now, invoices could only be synced when the orders were marked as 'Completed'.
* Some bug fixes and code improvements.

= 1.10.0 =
* Some bug fixes and code improvements.

= 1.9.0 =
* New: You can now set the NIF / company identifier field to be required during the checkout process (configurable via the settings section).

= 1.8.0 =
* New: In those invoices that need to be grouped by VAT percentages due to rounding issues, you will now see a description of the original items in the advanced notes for each invoice line.
* Fixes the 'Missed schedule' issue during invoice sync in some installations depending on how the timezone and server time is set.
* Fixes an issue syncing an invoice if the value of a tax rate is changed after the order has been created.

= 1.7.0 =
* New: Discount coupons are now reflected in the Contasimple invoice (only in WooCommerce 3.2 or greater).
* New: You can now sync more than one partial refund for a given order (does not apply to the import from previous orders feature).
* New: You can now sync previously failed invoices individually (beware though, they will use a different numeric series).
* New: You can now opt to pass the product SKU to the invoice line concept (configurable via the settings section).
* Fixes errors with the latest PHP 7.4 version.
* Some other bug fixes and code improvements.

= 1.6.0 =
* New: Displaying of customer billing address and NIF / company number in the invoice sync page.
* New: Support for intracommunitary invoices for customers that have a valid VIES number.
* Some bug fixes and code improvements.

= 1.5.0 =
* Fixes an error where the plugin would disconnect due to lack of connection or incompatibility issues.

= 1.4.2 =
* New: Order fees are now synchronized automatically.
* New: Possibility to remove pending invoices from sync queue.
* New: Sync attempts while in error state fixed to 3 (on automatic sync) and the error email will only be sent once.
* Many fixes and code improvements regarding the sync process and invoice amounts calculations.

= 1.4.1 =
* State/province from customer checkout address is now synced to Contasimple.
* Other bug fixes and code improvements.

= 1.4.0 =
* New: Supports the special regime for additional VAT (RE) and IRPF taxes (Spain only).
* New: Emails the shop owner when an order synchronization fails.
* New: Allows the synchronization process to keep syncing incoming orders even if the synchronization of a previous order failed.
* Fixes compatibility issue with PHP version 7.3
* Fixes issue syncing invoices with negative amounts (refunds)
* Other minor bug fixes.

= 1.3.1 =
* Minor bug fixes.

= 1.3.0 =
* Updated plugin to support the latest multiuser feature on Contasimple.
* Updated plugin to natively support the equivalence surcharge tax.
* Other minor bug fixes.

= 1.0.4 =
* Minor bug fixes.

= 1.0.3 =
* Minor bug fixes.

= 1.0.2 =
* Mandatory update to adapt to Contasimple API changes in country settings.

= 1.0.1 =
* Fixes bug during plugin activation if server PHP version is older than 5.6.

= 1.0 =
* Initial release of the plugin.

== Upgrade Notice ==

= 1.0 =
Just released to the WordPress repository.
