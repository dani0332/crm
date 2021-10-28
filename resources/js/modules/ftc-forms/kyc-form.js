

import React from "react";
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import ManageListFormView from "../../components/list-view/manage-list-form-view";
import KycAMLForm from "./verify-aml";
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import 'react-tabs/style/react-tabs.css';

export default function KycForm(props) {
    const override = {
        title: 'KYC documents',
        access: {
            read: ['pa', 'advisor' , 'admin', 'invoicing', 'production_approval_manager'],
            write: [],
            update: [],
            delete: []
        }
    }
    const filter = props.filter

    return(
        <div className="col-md-12">
        <Tabs>
            <TabList>
                <Tab>KYC</Tab>
                <Tab>KYC Documents</Tab>
                <Tab>Request Advisor</Tab>
                <Tab>AML</Tab>
            </TabList>
            <TabPanel>
                <ManageListFormView form={{ form: 'carQuoteKyc',  view_mode: 'list', action_type: 'list' , context: 'car_quote_snap', multi: false, filter: filter }} />
            </TabPanel>
            <TabPanel>
                <ManageListFormView form={{ form: 'leadAttachment' , view_mode: 'list',action_type: 'list', context: 'car_quote_snap',  filter: filter, override: override}} />
            </TabPanel>
            <TabPanel>
                <ManageListFormView form={{ form: 'carQuoteKycStatus' , view_mode: 'list',action_type: 'list', context: 'car_quote_snap',  filter: filter}} />
            </TabPanel>
            <TabPanel>
                <KycAMLForm form={{filter: filter}} />
            </TabPanel>
        </Tabs>
       </div>
    )
}
