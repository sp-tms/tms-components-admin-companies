<?php

namespace Apps\Tms\Components\Companies;

use Apps\Tms\Packages\Adminltetags\Traits\DynamicTable;
use Apps\Tms\Packages\Companies\Companies;
use System\Base\BaseComponent;

class CompaniesComponent extends BaseComponent
{
    use DynamicTable;

    protected $companiesPackage;

    public function initialize($onlyActivityLogs = false)
    {
        $this->companiesPackage = $this->usePackage(Companies::class);

        $this->setActivityLogsPackage($this->companiesPackage, 'companies/activitylogs');

        if ($onlyActivityLogs) {
            return;
        }

        $this->setNotificationPackage($this->companiesPackage);
    }

    /**
     * @acl(name=view)
     */
    public function viewAction()
    {
        if (isset($this->getData()['id'])) {
            if (isset($this->getData()['businesstype']) && $this->getData()['businesstype'] !== 'organisations') {
                $organisations = $this->companiesPackage->getCompaniesByBusinessType();

                $this->view->organisations = $organisations;
            }

            if ($this->getData()['id'] != 0) {
                $this->companiesPackage->useMutex(true);

                $company = $this->companiesPackage->getCompany((int) $this->getData()['id']);

                if (!$company) {
                    return $this->throwIdNotFound();
                }

                if ($company['business_type'] !== 'organisations' &&
                    !isset($this->view->organisations)
                ) {
                    $organisations = $this->companiesPackage->getCompaniesByBusinessType();

                    $this->view->organisations = $organisations;
                }

                $this->view->company = $company;
            }

            $this->view->pick('companies/view');

            return;
        }

        $controlActions =
            [
                'actionsToEnable'       =>
                [
                    'edit'      => 'companies',
                    'remove'    => 'companies/remove'
                ]
            ];

        $conditions = [];
        $conditions['order'] = 'name asc';

        $replaceColumns =
            function ($dataArr) {
                if ($dataArr && is_array($dataArr) && count($dataArr) > 0) {
                    foreach ($dataArr as &$data) {
                        $data['business_type'] = ucfirst($data['business_type']);
                    }
                }

                return $dataArr;
            };

        $this->generateDTContent(
            $this->companiesPackage,
            'companies/view',
            $conditions,
            ['name', 'business_type', 'company_phone_1', 'company_phone_2', 'company_email'],
            true,
            ['name', 'business_type', 'company_phone_1', 'company_phone_2', 'company_email'],
            $controlActions,
            ['business_type' => 'type', 'company_phone_1' => 'phone #1', 'company_phone_2' => 'phone #2', 'company_email' => 'email'],
            $replaceColumns,
            'name'
        );

        $this->view->pick('companies/list');
    }

    /**
     * @acl(name=add)
     * @notification(name=add)
     */
    public function addAction()
    {
        $this->requestIsPost();

        $this->companiesPackage->addCompany($this->postData());

        $this->addResponse(
            $this->companiesPackage->packagesData->responseMessage,
            $this->companiesPackage->packagesData->responseCode
        );
    }

    /**
     * @acl(name=update)
     * @notification(name=update)
     */
    public function updateAction()
    {
        $this->requestIsPost();

        $this->companiesPackage->useMutex(true);

        $this->companiesPackage->updateCompany($this->postData());

        $this->addResponse(
            $this->companiesPackage->packagesData->responseMessage,
            $this->companiesPackage->packagesData->responseCode
        );
    }

    /**
     * @acl(name=remove)
     * @notification(name=remove)
     */
    public function removeAction()
    {
        $this->requestIsPost();

        $this->companiesPackage->removeCompany($this->postData());

        $this->addResponse(
            $this->companiesPackage->packagesData->responseMessage,
            $this->companiesPackage->packagesData->responseCode
        );

        $this->setNotificationPackage();

        if ($this->companiesPackage->packagesData->responseCode === 0) {
            $this->addToNotification('remove', 'Archived company ' . $this->companiesPackage->packagesData->last['name'], null, $this->companiesPackage->packagesData->last);
        }
    }

    public function getCompanyAction()
    {
        $this->requestIsPost();

        $this->companiesPackage->getCompany((int) $this->postData()['company_id']);

        $this->addResponse(
            $this->companiesPackage->packagesData->responseMessage,
            $this->companiesPackage->packagesData->responseCode,
            $this->companiesPackage->packagesData->responseData ?? []
        );
    }
}