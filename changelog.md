# Log of changes for SilverCommerce/Discounts module

## 1.0.0

* First initial release

## 1.0.1

* Use order subtotal for fixed rate discount

## 1.0.3

* Added description to MinOrder field
* Show discount type after creation
* Move CMF fields into `beforeCMSFields` hook

## 2.0.0

* Add multi-code support to discounts
* Add ability to limit uses on codes
* Improve display of discounts in admin
* Commenting/Documentation improvements

## 2.0.1

* Fix issues with Free Postage not being applied correctly

## 2.0.2

* Fix error getting valid discount code
* Add discount usage report

## 2.0.3

* Error fix with discount code usage

## 2.0.4

* Fix error when saving discount code on newer PHP versions

## 2.0.5

* Add overwrite to discount factory allowing discounts to be automatically replaced when a new one is added

## 2.0.6

* Ensure discount is available when called from `AppliedDiscount`