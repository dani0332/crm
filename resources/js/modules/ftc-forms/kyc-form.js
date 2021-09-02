

import React, {   } from "react";
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import ManageListFormView from "../../components/list-view/manage-list-form-view";
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import 'react-tabs/style/react-tabs.css';
export default function KycForm(props) {
    const override = {
        title: 'KYC documents',
        access: {
            read: ['pa', 'advisor' , 'admin', 'invoicing'],
            write: [],
            update: [],
            delete: []
        }
    }

    const leadRequestOverride = {
        title: 'Assign User',
        access: {
            read: ['pa','advisor' , 'admin', 'invoicing' ],
            write: [ ],
            update: [ ],
            delete: [ ]
        },
        fields: {
            first_name: {
                type:'text',
                label:'First Name',
                field:'first_name',
                defaultValue:'hello',
                rules: {required: true},
                access: {
                    read: ['pa', 'advisor', 'admin', 'invoicing'],
                    write: [],
                    update: [],
                },
            },
        }
    }
    const filter = props.filter

    return(
        <div className="col-md-12">
        <Tabs>
            <TabList>
                <Tab>KYC Documents</Tab>
                <Tab>Request Advisor</Tab>
                {/* <Tab>Assign</Tab> */}
            </TabList>
            <TabPanel>
                <ManageListFormView form={{ form: 'leadAttachment' , view_mode: 'list',action_type: 'list', context: 'car_quote_snap',  filter: filter, override: override}} />
            </TabPanel>
            <TabPanel>
                <ManageListFormView form={{ form: 'carQuoteKycStatus' , view_mode: 'list',action_type: 'list', context: 'car_quote_snap',  filter: filter}} />
            </TabPanel>
            {/* <TabPanel>
                <AssignUser filter={filter} email={email} />
            </TabPanel> */}
        </Tabs>
        </div>
    )
}
