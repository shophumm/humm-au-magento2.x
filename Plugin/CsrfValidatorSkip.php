<?php
/**
 * Created by PhpStorm.
 * User: dev-mac
 * Date: 13/10/20
 * Time: 4:14 PM
 */

namespace Humm\HummPaymentGateway\Plugin;


class CsrfValidatorSkip
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        /* Magento 2.1.x, 2.2.x */
        if ($request->getModuleName() == 'humm') {
            return; // Skip CSRF check
        }
        /* Magento 2.3.x */
        if (strpos($request->getOriginalPathInfo(), 'humm\/checkout\/success') !== false) {
            return; // Skip CSRF check
        }
        $proceed($request, $action);
    }
}