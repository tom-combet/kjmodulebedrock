<?php
/**
 * Copyright since 2019 Kaudaj
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Kaudaj\Module\ModuleBedrock\Controller\Admin;

use PrestaShop\PrestaShop\Core\Domain\Tab\Command\UpdateTabStatusByClassNameCommand;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\DemoRestricted;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ConfigurationController
 *
 * @ModuleActivated(moduleName="kjmodulebedrock", redirectRoute="admin_module_manage")
 */
class ConfigurationController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted(['read'], request.get('_legacy_controller'))",
     *     message="You do not have permission to access this."
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $preferencesFormDataHandler = $this->getPreferencesFormHandler();

        /** @var FormInterface $preferencesForm */
        $preferencesForm = $preferencesFormDataHandler->getForm();

        return $this->render('@Modules/kjmodulebedrock/views/templates/back/components/layouts/configuration.html.twig', [
            'preferences_form' => $preferencesForm->createView(),
        ]);
    }

    /**
     * @AdminSecurity(
     *      "is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *      message="You do not have permission to update this.",
     *      redirectRoute="admin_module_bedrock_configuration"
     * )
     *
     * @DemoRestricted(redirectRoute="preferences_configuration")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @throws \LogicException
     */
    public function processPreferencesFormAction(Request $request)
    {
        return $this->processForm(
            $request,
            $this->getPreferencesFormHandler(),
            'Preferences'
        );
    }

    /**
     * Process form.
     *
     * @param Request $request
     * @param FormHandlerInterface $formHandler
     * @param string $hookName
     *
     * @return RedirectResponse
     */
    private function processForm(Request $request, FormHandlerInterface $formHandler, string $hookName)
    {
        $this->dispatchHook(
            'actionModuleBedrock' . get_class($this) . 'PostProcess' . $hookName . 'Before',
            ['controller' => $this]
        );

        $this->dispatchHook(
            'actionModuleBedrock' . get_class($this) . 'PostProcessBefore',
            ['controller' => $this]
        );

        $form = $formHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $saveErrors = $formHandler->save($data);

            if (0 === count($saveErrors)) {
                $this->getCommandBus()->handle(
                    new UpdateTabStatusByClassNameCommand(
                        'AdminShopGroup',
                        $this->configuration->getBoolean('PS_MULTISHOP_FEATURE_ACTIVE')
                    )
                );

                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
            } else {
                $this->flashErrors($saveErrors);
            }
        }

        return $this->redirectToRoute('admin_module_bedrock_configuration');
    }

    /**
     * @return FormHandlerInterface
     */
    private function getPreferencesFormHandler()
    {
        return $this->get('kaudaj.module.modulebedrock.form.preferences_configuration_form_data_handler');
    }
}
