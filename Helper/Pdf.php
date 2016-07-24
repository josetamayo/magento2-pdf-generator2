<?php
/**
 * EaDesgin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eadesign.ro so we can send you a copy immediately.
 *
 * @category    custom_ext_code
 * @copyright   Copyright (c) 2008-2016 EaDesign by Eco Active S.R.L.
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Eadesigndev\Pdfgenerator\Helper;

use Eadesigndev\Pdfgenerator\Model\Template\Processor;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;

class Pdf extends AbstractHelper
{
    CONST PAPER_ORI = [
        1 => 'P',
        2 => 'L'
    ];

    protected $_invoice;

    protected $_template;

    /**
     * @var IdentityInterface
     */
    protected $identityContainer;

    /**
     * @var
     */
    public $_mPDF;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var TemplateFactory
     */
    private $_templateFactory;

    /**
     * @var Renderer
     */
    protected $addressRenderer;

    /**
     * @var TemplateFactory
     */
    private $processor;

    /**
     * Pdf constructor.
     * @param Context $context
     * @param Eapdf $_mPDF
     * @param Processor $_processor
     * @param Renderer $addressRenderer
     * @param PaymentHelper $paymentHelper
     * @param InvoiceIdentity $identityContainer
     * @param Processor $_templateFactory
     */
    public function __construct(
        Context $context,
        Processor $_processor,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        InvoiceIdentity $identityContainer,
        Processor $_templateFactory

    )
    {
        $this->processor = $_templateFactory;
        $this->paymentHelper = $paymentHelper;
        $this->identityContainer = $identityContainer;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context);
    }

    public function setInvoice(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $this->_invoice = $invoice;
        return $this;
    }

    public function setTemplate(\Eadesigndev\Pdfgenerator\Model\Pdfgenerator $template)
    {
        $this->_template = $template;
        return $this;
    }


    public function template2Pdf()
    {

        $invoice = $this->_invoice;
        $templateModel = $this->_template;
        $order = $invoice->getOrder();

        $html = $this->_transport($order, $invoice, $templateModel);

        return $this->_eapdfSettings($html, $templateModel);
    }

    /**
     * @param $html
     * @return string
     */
    private function _eapdfSettings($html, $templateModel)
    {
        if (!$templateModel->getTemplateCustomForm()) {
            $pdf = new \mPDF(
                $mode = '',
                $format = 'A4',
                $default_font_size = 0,
                $default_font = '',
                $mgl = $templateModel->getTemplateCustomL(),
                $mgr = $templateModel->getTemplateCustomR(),
                $mgt = $templateModel->getTemplateCustomT(),
                $mgb = $templateModel->getTemplateCustomB(),
                $mgh = 9,
                $mgf = 9,
                $orientation = 'L'
            );
        }


        if ($templateModel->getTemplateCustomForm()) {
            $pdf = new \mPDF(
                '',
                [
                    $templateModel->getTemplateCustomW(),
                    $templateModel->getTemplateCustomH()
                ]
            );
        }


        $pdf->WriteHTML($html);
        $pdfToOutput = $pdf->Output('', 'S');

        return $pdfToOutput;
    }

    /**
     * @param $order
     * @param $invoice
     * @param $templateModel
     * @return string
     */
    private function _transport($order, $invoice, $templateModel)
    {
        $transport = [
            'order' => $order,
            'invoice' => $invoice,
            'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order)
        ];

        $processor = $this->processor;

        $processor->setVariables($transport);
        $processor->setTemplate($templateModel);


        $text = $processor->processTemplate();
        return $text;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    protected function getPaymentHtml(\Magento\Sales\Model\Order $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return null
     */
    protected function getFormattedShippingAddress(\Magento\Sales\Model\Order $order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    protected function getFormattedBillingAddress(\Magento\Sales\Model\Order $order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }


}