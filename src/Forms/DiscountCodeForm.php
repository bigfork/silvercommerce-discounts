<?php

namespace SilverCommerce\Discounts\Forms;

use Exception;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Control\RequestHandler;
use SilverCommerce\Discounts\DiscountFactory;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\ShoppingCart\ShoppingCartFactory;

/**
 * A simple form to add a discount to an estimate via the code.
 */
class DiscountCodeForm extends Form
{
    /**
     * ID of the estimate the discount will be applied to.
     *
     * @var Int
     */
    protected $estimate_id;

    public function __construct(
        RequestHandler $controller = null,
        $name = self::DEFAULT_NAME,
        Estimate $estimate
    ) {
        $this->estimate_id = $estimate->ID;

        $fields = FieldList::create(
            TextField::create(
                "DiscountCode",
                _t("Discounts.HaveDiscountCode", "Do you have a Discount Code")
            )->setAttribute(
                "placeholder",
                _t("Discounts.EnterDiscountCode", "Enter discount code here")
            )
        );

        $actions = FieldList::create(
            FormAction::create(
                'doAddDiscount',
                _t('ShoppingCart.Add', 'Add')
            )->addExtraClass('btn btn-info')
        );

        $validator = new RequiredFields(
            [
                "DiscountCode"
            ]
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);

        $this->setTemplate(
            __NAMESPACE__ . 'Includes\\'. __CLASS__
        );
    }

    /**
     * retrieve the estimate via the id.
     *
     * @return Estimate
     */
    public function getEstimate()
    {
        return Estimate::get()->byID($this->estimate_id);
    }

    /**
     * Action that will find a discount based on the code
     *
     * @param type $data
     * @param type $form
     */
    public function doAddDiscount($data, $form)
    {
        try {
            $code_to_search = $data['DiscountCode'];
            $estimate = $this->getEstimate();
            DiscountFactory::create($code_to_search, $estimate)->applyDiscountToEstimate();
        } catch (Exception $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
        }

        return $this->getRequestHandler()->redirectBack();
    }
}
